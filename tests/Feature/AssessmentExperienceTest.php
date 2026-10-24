<?php

use App\Models\Answer;
use App\Models\AssessmentResult;
use App\Models\Question;
use App\Models\User;
use App\Services\AssessmentScoringService;
use App\Services\OpenAIService;
use Database\Seeders\QuestionSeeder;

use function Pest\Laravel\actingAs;

it('seeds the 150-item instrument with distinct interest and aptitude sections', function () {
    $this->seed(QuestionSeeder::class);

    $questions = Question::query()
        ->where('instrument_version', Question::CURRENT_INSTRUMENT_VERSION)
        ->orderBy('item_number')
        ->get();

    expect($questions)->toHaveCount(150)
        ->and($questions->pluck('item_number')->all())->toBe(range(1, 150))
        ->and($questions->where('section', 'interest'))->toHaveCount(90)
        ->and($questions->where('section', 'verbal'))->toHaveCount(20)
        ->and($questions->where('section', 'quantitative'))->toHaveCount(20)
        ->and($questions->where('section', 'analytical'))->toHaveCount(20)
        ->and($questions->where('riasec_type', 'R'))->toHaveCount(15)
        ->and($questions->firstWhere('item_number', 1)->question_text)
        ->toBe($questions->firstWhere('item_number', 3)->question_text)
        ->and($questions->firstWhere('item_number', 93)->correct_answer)->toBe(1)
        ->and($questions->firstWhere('item_number', 106)->correct_answer)->toBe(2)
        ->and($questions->firstWhere('item_number', 109)->correct_answer)->toBe(1)
        ->and($questions->firstWhere('item_number', 129)->correct_answer)->toBe(3)
        ->and($questions->where('question_type', 'multiple_choice')->every(fn (Question $question) => count($question->choices) === 4))
        ->toBeTrue();

    $scores = app(AssessmentScoringService::class)->score(
        $questions->map(fn (Question $question): array => [
            'question' => $question,
            'answer' => $question->question_type === 'likert' ? 4 : $question->correct_answer,
        ])
    );

    expect($scores['interest']['R']['average'])->toBe(4.0)
        ->and($scores['aptitude']['verbal']['correct'])->toBe(20)
        ->and($scores['aptitude']['quantitative']['percentage'])->toBe(100.0)
        ->and($scores['aptitude']['analytical']['total'])->toBe(20);
});

it('requires all 150 responses and stores versioned score profiles and individual attempt responses', function () {
    $this->seed(QuestionSeeder::class);

    $user = User::factory()->consented()->create([
        'email_verified_at' => now(),
        'user_type' => 'user',
    ]);

    $questions = Question::query()
        ->where('instrument_version', Question::CURRENT_INSTRUMENT_VERSION)
        ->orderBy('item_number')
        ->get();

    $startedAt = now()->subMinutes(5)->toDateTimeString();
    $assessmentResponse = $this->actingAs($user)
        ->withSession(['assessment_started_at' => $startedAt])
        ->get(route('assessment.show'))
        ->assertOk()
        ->assertSee('Question')
        ->assertSee('Strongly Dislike')
        ->assertSee('Strongly Like')
        ->assertSee('Select the best answer.')
        ->assertSee('A business has three possible solutions');

    $this->actingAs($user)->get(route('assessment.show'))
        ->assertOk()
        ->assertSessionHas('assessment_started_at', $startedAt);

    expect(substr_count($assessmentResponse->getContent(), 'name="question_'))->toBe(150);

    $this->app->instance(OpenAIService::class, new class extends OpenAIService
    {
        public function analyzeAssessmentProfile(array $scoreBreakdown): array
        {
            $matches = [
                ['code' => 'BSIT', 'name' => 'Bachelor of Science in Information Technology', 'reason' => 'Strong technology interests.', 'evidence' => ['Investigative average: 4.5 / 5'], 'explore' => 'Compare the program curriculum.'],
                ['code' => 'BSCS', 'name' => 'Bachelor of Science in Computer Science', 'reason' => 'Related interests.', 'evidence' => [], 'explore' => 'Review sample courses.'],
                ['code' => 'BSAIS', 'name' => 'Bachelor of Science in Accounting Information System', 'reason' => 'Related interests.', 'evidence' => [], 'explore' => 'Ask about accounting subjects.'],
                ['code' => 'BSTM', 'name' => 'Bachelor of Science in Tourism Management', 'reason' => 'Related interests.', 'evidence' => [], 'explore' => 'Explore tourism careers.'],
                ['code' => 'BSHM', 'name' => 'Bachelor of Science in Hospitality Management', 'reason' => 'Related interests.', 'evidence' => [], 'explore' => 'Explore hospitality careers.'],
            ];

            return [
                'recommended_program' => [
                    'code' => 'BSIT',
                    'name' => 'Bachelor of Science in Information Technology',
                ],
                'confidence' => 0,
                'fit_level' => 'Exploratory AI match',
                'reason' => 'Interest and aptitude scores are reported separately.',
                'strengths' => ['Investigative interest is strongest in this profile.'],
                'development_areas' => [],
                'feedback' => 'Discuss this profile with a school counselor.',
                'evidence' => [],
                'next_steps' => ['Review the profile with a school counselor.'],
                'program_matches' => $matches,
                'alternative_programs' => [],
                'source' => 'openai',
                'disclaimer' => 'Exploratory guidance only. The student retains full choice.',
            ];
        }
    });

    $partialPayload = [
        'question_'.$questions->first()->id => 4,
    ];

    $this->actingAs($user)->post(route('assessment.submit'), $partialPayload)
        ->assertSessionHasErrors('question_'.$questions->last()->id);

    $this->actingAs($user)->get(route('assessment.show'))
        ->assertOk()
        ->assertSee("We couldn't submit your assessment:", false)
        ->assertSee('question '.$questions->last()->item_number.' field is required');

    expect(AssessmentResult::query()->where('user_id', $user->id)->count())->toBe(0);

    $payload = $questions->mapWithKeys(fn (Question $question): array => [
        'question_'.$question->id => $question->question_type === 'likert'
            ? 4
            : $question->correct_answer,
    ])->all();

    $this->actingAs($user)->post(route('assessment.submit'), $payload)
        ->assertRedirect(route('results.index'));

    $result = AssessmentResult::query()->where('user_id', $user->id)->firstOrFail();

    expect($result->instrument_version)->toBe(Question::CURRENT_INSTRUMENT_VERSION)
        ->and($result->program_code)->toBe('BSIT')
        ->and($result->score_breakdown['interest']['R']['items'])->toBe(15)
        ->and($result->score_breakdown['aptitude']['verbal']['correct'])->toBe(20)
        ->and($result->answers()->count())->toBe(150)
        ->and($result->answers()->where('is_correct', true)->count())->toBe(60);

    $this->actingAs($user)->post(route('assessment.submit'), $payload)
        ->assertRedirect(route('results.index'));

    $attempts = AssessmentResult::query()->where('user_id', $user->id)->get();

    expect($attempts)->toHaveCount(2)
        ->and($attempts->every(fn (AssessmentResult $attempt) => $attempt->answers()->count() === 150))
        ->toBeTrue();

    $this->actingAs($user)->get(route('results.index'))
        ->assertOk()
        ->assertSee('Career Interest and Aptitude Profile')
        ->assertSee('Exploratory suggestions—not a placement decision')
        ->assertSee('AI-generated program mapping')
        ->assertSee('sessionStorage.removeItem')
        ->assertSee('Programs to Explore')
        ->assertSee('Your interests, goals, and decision come first')
        ->assertSee('20 / 20');
});

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

    $this->actingAs($user)->get(route('assessment.consent'))
        ->assertOk()
        ->assertSee('90 career-interest responses')
        ->assertSee('60 scholastic aptitude responses')
        ->assertSee('OpenAI when selected')
        ->assertSee('Authorized school administrators can see identifiable recent submissions');

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

    $this->app->instance(OpenAIService::class, new class extends OpenAIService
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
                'source' => 'openai',
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
        ->and($result->source)->toBe('openai')
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

    $this->app->instance(OpenAIService::class, new class extends OpenAIService
    {
        public function analyze($answers): array
        {
            return [
                'recommended_program' => ['code' => 'BSIT', 'name' => 'Bachelor of Science in Information Technology'],
                'confidence' => 80,
                'source' => 'openai',
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

it('allows submitting the assessment with some questions unanswered', function () {
    $user = User::factory()->consented()->create([
        'email_verified_at' => now(),
        'user_type' => 'user',
    ]);

    $questions = collect([
        ['question_text' => 'I enjoy solving logical problems.', 'category' => 'skills'],
        ['question_text' => 'I am interested in technology.', 'category' => 'interest'],
        ['question_text' => 'I am detail-oriented.', 'category' => 'personality'],
    ])->map(fn (array $data) => Question::query()->create($data));

    $this->app->instance(OpenAIService::class, new class extends OpenAIService
    {
        public function analyze($answers): array
        {
            return [
                'recommended_program' => ['code' => 'BSIT', 'name' => 'Bachelor of Science in Information Technology'],
                'confidence' => 80,
                'source' => 'openai',
            ];
        }
    });

    $answered = $questions
        ->take(2)
        ->mapWithKeys(fn (Question $question) => ['question_'.$question->id => 5])
        ->all();

    $response = $this->actingAs($user)->post(route('assessment.submit'), $answered);

    $response->assertRedirect(route('results.index'));

    expect(Answer::query()->where('user_id', $user->id)->count())->toBe(2);

    $result = AssessmentResult::query()->where('user_id', $user->id)->first();

    expect($result)->not->toBeNull()
        ->and($result->program_code)->toBe('BSIT')
        ->and($result->category_summary)->toBeArray();
});

it('rejects submitting the assessment without any answers', function () {
    $user = User::factory()->consented()->create([
        'email_verified_at' => now(),
        'user_type' => 'user',
    ]);

    Question::query()->create([
        'question_text' => 'I enjoy solving logical problems.',
        'category' => 'skills',
    ]);

    $this->actingAs($user)->post(route('assessment.submit'), [])
        ->assertRedirect()
        ->assertSessionHasErrors('assessment');

    expect(AssessmentResult::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and(Answer::query()->where('user_id', $user->id)->count())->toBe(0);
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

    $this->app->instance(OpenAIService::class, new class extends OpenAIService
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
                'source' => 'openai',
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

    $this->app->instance(OpenAIService::class, new class extends OpenAIService
    {
        public function analyze($answers): array
        {
            return [
                'recommended_program' => ['code' => 'BSIT', 'name' => 'Bachelor of Science in Information Technology'],
                'confidence' => 70,
                'source' => 'openai',
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
        'source' => 'openai',
        'completed_at' => now(),
    ]);
    AssessmentResult::query()->create([
        'user_id' => $student->id,
        'program_code' => 'BSCS',
        'program_name' => 'Bachelor of Science in Computer Science',
        'confidence' => 0,
        'source' => 'openai',
        'instrument_version' => Question::CURRENT_INSTRUMENT_VERSION,
        'completed_at' => now(),
    ]);

    $this->actingAs($student)->get(route('admin.analytics'))
        ->assertForbidden();

    $this->actingAs($admin)->get(route('admin.analytics'))
        ->assertOk()
        ->assertSee('Assessment Analytics')
        ->assertSee('BSIT')
        ->assertSee('BSCS')
        ->assertSee('85.5%')
        ->assertSee($student->name);

    $this->actingAs($admin)->get(route('admin.analytics.programs'))
        ->assertOk()
        ->assertJsonCount(2, 'programs')
        ->assertJsonPath('programs.0.program_code', 'BSIT')
        ->assertJsonPath('programs.1.program_code', 'BSCS');
});
