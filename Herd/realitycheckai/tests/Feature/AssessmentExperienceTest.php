<?php

use App\Models\Answer;
use App\Models\AssessmentResult;
use App\Models\Question;
use App\Models\User;
use App\Services\OllamaService;

use function Pest\Laravel\actingAs;

it('renders the improved student dashboard for authenticated users', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'user_type' => 'user',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response
        ->assertOk()
        ->assertSee('Student Dashboard')
        ->assertSee('AI Program Assessment')
        ->assertSee('AI Quiz Generator');
});

it('requires data privacy consent before taking the assessment', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'user_type' => 'user',
    ]);

    $this->actingAs($user)->get(route('assessment.show'))
        ->assertRedirect(route('assessment.consent'));

    $this->actingAs($user)->post(route('assessment.submit'), [])
        ->assertRedirect(route('assessment.consent'));

    expect(AssessmentResult::query()->count())->toBe(0);

    $this->actingAs($user)->post(route('assessment.consent.store'), ['privacy_consent' => '1'])
        ->assertRedirect(route('assessment.show'));

    expect($user->fresh()->privacy_consented_at)->not->toBeNull();
});

it('submits the assessment and persists a structured recommendation result', function () {
    $user = User::factory()->consented()->create([
        'email_verified_at' => now(),
        'user_type' => 'user',
    ]);

    $questions = collect([
        ['question_text' => 'I enjoy solving logical problems.', 'category' => 'skills'],
        ['question_text' => 'I am interested in technology.', 'category' => 'interest'],
        ['question_text' => 'I am detail-oriented.', 'category' => 'personality'],
    ])->map(fn (array $data) => Question::query()->create($data));

    $this->app->instance(OllamaService::class, new class extends OllamaService
    {
        public function analyze($answers): array
        {
            return [
                'recommended_program' => [
                    'code' => 'BSIT',
                    'name' => 'Bachelor of Science in Information Technology',
                ],
                'confidence' => 91,
                'fit_level' => 'Strong Fit',
                'reason' => 'The student shows strong technology and practical problem-solving alignment.',
                'strengths' => ['Technology interest', 'Problem solving', 'Detail orientation'],
                'development_areas' => ['Compare BSIT and BSCS subjects'],
                'feedback' => 'BSIT is a strong match based on the answers.',
                'evidence' => ['Technology interest - Strongly agree'],
                'next_steps' => ['Review BSIT curriculum'],
                'alternative_programs' => [],
                'source' => 'ollama',
            ];
        }
    });

    $payload = $questions
        ->mapWithKeys(fn (Question $question) => ['question_'.$question->id => 5])
        ->all();

    $response = $this->actingAs($user)->post(route('assessment.submit'), $payload);

    $response
        ->assertRedirect(route('results.index'))
        ->assertSessionHas('ai_feedback.recommended_program.code', 'BSIT')
        ->assertSessionHas('assessment_summary');

    expect(Answer::query()->where('user_id', $user->id)->count())->toBe(3);

    $result = AssessmentResult::query()->where('user_id', $user->id)->first();

    expect($result)->not->toBeNull()
        ->and($result->program_code)->toBe('BSIT')
        ->and($result->source)->toBe('ollama')
        ->and((float) $result->confidence)->toBe(91.0)
        ->and($result->strongest_trait)->toBeString()
        ->and($result->result['recommended_program']['code'] ?? null)->toBe('BSIT')
        ->and($result->category_summary)->toBeArray();
});

it('inverts answers for negatively-worded questions using the reverse flag', function () {
    $user = User::factory()->consented()->create([
        'email_verified_at' => now(),
        'user_type' => 'user',
    ]);

    $positive = Question::query()->create([
        'question_text' => 'I enjoy solving technical problems.',
        'category' => 'technical',
    ]);

    $negative = Question::query()->create([
        'question_text' => 'I avoid tasks involving technology.',
        'category' => 'technical',
        'is_reverse' => true,
    ]);

    $this->app->instance(OllamaService::class, new class extends OllamaService
    {
        public function analyze($answers): array
        {
            return [
                'recommended_program' => ['code' => 'BSIT', 'name' => 'Bachelor of Science in Information Technology'],
                'confidence' => 80,
                'source' => 'ollama',
            ];
        }
    });

    $this->actingAs($user)->post(route('assessment.submit'), [
        'question_'.$positive->id => 2,
        'question_'.$negative->id => 2,
    ]);

    $answers = Answer::query()
        ->where('user_id', $user->id)
        ->pluck('answer', 'question_id');

    expect($answers[$positive->id])->toBe(2)
        ->and($answers[$negative->id])->toBe(4);
});

it('keeps a retake history of assessment recommendations', function () {
    $user = User::factory()->consented()->create([
        'email_verified_at' => now(),
        'user_type' => 'user',
    ]);

    $question = Question::query()->create([
        'question_text' => 'I am interested in technology.',
        'category' => 'interest',
    ]);

    $this->app->instance(OllamaService::class, new class extends OllamaService
    {
        public function analyze($answers): array
        {
            static $run = 0;

            $run++;

            return [
                'recommended_program' => [
                    'code' => $run === 1 ? 'BSCS' : 'BSIT',
                    'name' => $run === 1 ? 'Bachelor of Science in Computer Science' : 'Bachelor of Science in Information Technology',
                ],
                'confidence' => $run === 1 ? 74 : 88,
                'source' => 'ollama',
            ];
        }
    });

    actingAs($user)->post(route('assessment.submit'), ['question_'.$question->id => 4]);
    actingAs($user)->post(route('assessment.submit'), ['question_'.$question->id => 5]);

    $results = $user->assessmentResults()->get();

    expect($results)->toHaveCount(2)
        ->and($results->first()->program_code)->toBe('BSIT')
        ->and($results->last()->program_code)->toBe('BSCS');

    $response = $this->actingAs($user)->get(route('results.index'));

    $response
        ->assertOk()
        ->assertSee('Assessment History')
        ->assertSee('BSCS');
});

it('flags straight-lined response patterns on the results page', function () {
    $user = User::factory()->consented()->create([
        'email_verified_at' => now(),
        'user_type' => 'user',
    ]);

    $questions = collect([
        ['question_text' => 'I enjoy solving logical problems.', 'category' => 'skills'],
        ['question_text' => 'I am interested in technology.', 'category' => 'interest'],
        ['question_text' => 'I am detail-oriented.', 'category' => 'personality'],
    ])->map(fn (array $data) => Question::query()->create($data));

    $this->app->instance(OllamaService::class, new class extends OllamaService
    {
        public function analyze($answers): array
        {
            return [
                'recommended_program' => ['code' => 'BSIT', 'name' => 'Bachelor of Science in Information Technology'],
                'confidence' => 70,
                'source' => 'ollama',
            ];
        }
    });

    $payload = $questions
        ->mapWithKeys(fn (Question $question) => ['question_'.$question->id => 3])
        ->all();

    actingAs($user)->post(route('assessment.submit'), $payload);

    $result = AssessmentResult::query()->where('user_id', $user->id)->first();

    expect($result->response_flags)->toContain('uniform_responses');

    $this->actingAs($user)->get(route('results.index'))
        ->assertOk()
        ->assertSee('Response pattern notice');
});

it('restricts assessment analytics to administrators', function () {
    $student = User::factory()->consented()->create([
        'email_verified_at' => now(),
        'user_type' => 'user',
    ]);

    $admin = User::factory()->admin()->consented()->create([
        'email_verified_at' => now(),
    ]);

    AssessmentResult::query()->create([
        'user_id' => $student->id,
        'program_code' => 'BSIT',
        'program_name' => 'Bachelor of Science in Information Technology',
        'confidence' => 85.5,
        'source' => 'ollama',
        'completed_at' => now(),
    ]);

    $this->actingAs($student)->get(route('admin.analytics'))
        ->assertForbidden();

    $this->actingAs($admin)->get(route('admin.analytics'))
        ->assertOk()
        ->assertSee('Assessment Analytics')
        ->assertSee('BSIT')
        ->assertSee($student->name);

    $this->actingAs($admin)->get(route('admin.analytics.programs'))
        ->assertOk()
        ->assertJsonPath('programs.0.program_code', 'BSIT');
});
