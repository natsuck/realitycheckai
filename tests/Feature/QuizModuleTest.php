<?php

use App\Jobs\GenerateQuizFromMaterial;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\AnswerEvaluationService;
use App\Services\QuizGeneratorService;
use App\Services\TextExtractionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

it('queues quiz generation from an uploaded learning material', function () {
    Storage::fake('local');
    Queue::fake();

    $user = User::factory()->create();
    $file = UploadedFile::fake()->createWithContent(
        'networking.txt',
        str_repeat('Networking connects devices and allows data exchange across systems. ', 20)
    );

    $response = $this->actingAs($user)->post(route('quizzes.store'), [
        'title' => 'Networking Basics',
        'material' => $file,
    ]);

    $quiz = Quiz::query()->first();

    $response->assertRedirect(route('quizzes.show', $quiz));
    expect($quiz)
        ->not->toBeNull()
        ->user_id->toBe($user->id)
        ->title->toBe('Networking Basics')
        ->status->toBe(Quiz::STATUS_QUEUED);

    Storage::disk('local')->assertExists($quiz->source_file);
    Queue::assertPushed(GenerateQuizFromMaterial::class);
});

it('saves generated mcqs and flashcards separately and makes the quiz ready to attempt', function () {
    $user = User::factory()->create();
    $quiz = Quiz::query()->create([
        'user_id' => $user->id,
        'title' => 'Software Quality',
        'source_file' => 'uploads/quality.txt',
        'status' => Quiz::STATUS_QUEUED,
    ]);

    $generated = [
        'confidence' => 90,
        'mcqs' => array_map(fn (int $index): array => [
            'question' => 'Question '.$index,
            'choices' => ['Correct', 'Option B', 'Option C', 'Option D'],
            'answer' => 'Correct',
            'explanation' => 'This is supported by the source.',
        ], range(1, 15)),
        'flashcards' => array_map(fn (int $index): array => [
            'front' => 'Front '.$index,
            'back' => 'Back '.$index,
        ], range(1, 10)),
    ];

    $extractor = new class extends TextExtractionService
    {
        public function extract(string $storedPath): string
        {
            return 'Extracted source material for quiz generation.';
        }
    };
    $generator = new class($generated) extends QuizGeneratorService
    {
        public function __construct(private readonly array $generated) {}

        public function generate(string $documentText): array
        {
            return $this->generated;
        }
    };

    (new GenerateQuizFromMaterial($quiz->id))->handle($extractor, $generator);

    $quiz->refresh();

    expect($quiz->status)->toBe(Quiz::STATUS_READY)
        ->and($quiz->questions)->toHaveCount(15)
        ->and($quiz->questions->every(fn (QuizQuestion $question) => $question->isMcq() && $question->explanation !== null))
        ->toBeTrue()
        ->and($quiz->flashcards)->toHaveCount(10);
});

it('allows only the owner to download the uploaded learning material', function () {
    Storage::fake('local');
    Storage::disk('local')->put('uploads/material.txt', 'Sample learning material content.');

    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    $quiz = Quiz::query()->create([
        'user_id' => $owner->id,
        'title' => 'Material Quiz',
        'source_file' => 'uploads/material.txt',
        'original_filename' => 'material.txt',
        'status' => Quiz::STATUS_READY,
    ]);

    $this->actingAs($intruder)->get(route('quizzes.material', $quiz))->assertForbidden();

    $this->actingAs($owner)->get(route('quizzes.material', $quiz))
        ->assertOk()
        ->assertDownload('material.txt');
});

it('scores quiz attempts with automatic mcq and semantic identification checking', function () {
    $user = User::factory()->create();

    $quiz = Quiz::query()->create([
        'user_id' => $user->id,
        'title' => 'Computer Fundamentals',
        'source_file' => 'uploads/computer.txt',
        'status' => Quiz::STATUS_READY,
    ]);

    $mcq = $quiz->questions()->create([
        'type' => QuizQuestion::TYPE_MCQ,
        'question' => 'What does CPU stand for?',
        'choices' => ['Central Processing Unit', 'Computer Personal Unit', 'Central Program Utility', 'Control Processing User'],
        'correct_answer' => 'Central Processing Unit',
        'explanation' => 'The CPU is the computer component that executes instructions.',
    ]);

    $identification = $quiz->questions()->create([
        'type' => QuizQuestion::TYPE_IDENTIFICATION,
        'question' => 'Identify the main processing component of a computer.',
        'correct_answer' => 'Central Processing Unit',
    ]);

    $this->app->bind(AnswerEvaluationService::class, fn () => new class extends AnswerEvaluationService
    {
        public function __construct() {}

        public function evaluate(string $question, string $correctAnswer, ?string $studentAnswer): array
        {
            return [
                'is_correct' => str_contains(strtolower((string) $studentAnswer), 'central processing unit'),
                'confidence' => 92.0,
                'explanation' => 'The student answer is semantically equivalent.',
            ];
        }

        public function evaluateBatch(array $items): array
        {
            return collect($items)
                ->mapWithKeys(fn (array $item): array => [
                    $item['key'] => [
                        'is_correct' => str_contains(strtolower((string) ($item['student_answer'] ?? '')), 'central processing unit'),
                        'confidence' => 92.0,
                        'explanation' => 'The student answer is semantically equivalent.',
                    ],
                ])
                ->all();
        }
    });

    $this->actingAs($user)->get(route('quizzes.take', $quiz))->assertOk();

    $response = $this->actingAs($user)->post(route('quizzes.attempts.store', $quiz), [
        'answers' => [
            $mcq->id => 'Central Processing Unit',
            $identification->id => 'CPU means central processing unit',
        ],
    ]);

    $attempt = QuizAttempt::query()->first();

    $response->assertRedirect(route('quiz-attempts.show', $attempt));
    expect((float) $attempt->score)->toBe(100.0)
        ->and($attempt->correct_count)->toBe(2)
        ->and($attempt->mcq_score)->toBe(1)
        ->and($attempt->identification_score)->toBe(1)
        ->and($attempt->answers()->where('question_id', $mcq->id)->value('ai_explanation'))
        ->toBe('The CPU is the computer component that executes instructions.')
        ->and($attempt->feedback)->toContain('MCQ correct: 1/1. Identification correct: 1/1.');
});

it('requires an attempt before question review and reveals answers after the first attempt', function () {
    $user = User::factory()->create();
    $quiz = Quiz::query()->create([
        'user_id' => $user->id,
        'title' => 'Software Quality',
        'source_file' => 'uploads/quality.txt',
        'status' => Quiz::STATUS_READY,
    ]);
    $quiz->questions()->create([
        'type' => QuizQuestion::TYPE_MCQ,
        'question' => 'Which activity detects software errors?',
        'choices' => ['Review', 'Marketing', 'Hiring', 'Budgeting'],
        'correct_answer' => 'Review',
        'explanation' => 'Reviews are appraisal activities that detect errors.',
    ]);
    $quiz->flashcards()->create([
        'front' => 'What are appraisal costs?',
        'back' => 'Costs of activities performed to detect software errors.',
    ]);

    $this->actingAs($user)->get(route('quizzes.show', $quiz))
        ->assertOk()
        ->assertSee('Take the quiz before reviewing answers')
        ->assertDontSee('Which activity detects software errors?')
        ->assertDontSee('Answer key:')
        ->assertDontSee('Reviews are appraisal activities');

    $this->actingAs($user)->get(route('quizzes.take', $quiz))
        ->assertOk()
        ->assertDontSee('Answer key:')
        ->assertDontSee('Reviews are appraisal activities');

    $this->actingAs($user)->get(route('quizzes.flashcards', $quiz))
        ->assertOk()
        ->assertSee('Study mode')
        ->assertSee('Not graded')
        ->assertSee('Reveal answer');

    $this->actingAs($user)->get(route('quizzes.take', $quiz))->assertOk();
    $firstAttempt = $this->actingAs($user)->post(route('quizzes.attempts.store', $quiz), [
        'answers' => [$quiz->questions->first()->id => 'Marketing'],
    ]);
    $firstAttempt->assertRedirect(route('quiz-attempts.show', QuizAttempt::query()->firstOrFail()));

    $this->actingAs($user)->get($firstAttempt->headers->get('Location'))
        ->assertOk()
        ->assertSee('>Correct Answer</p>', false)
        ->assertSee('bg-rose-100 text-rose-700')
        ->assertSee('Reviews are appraisal activities');

    $this->actingAs($user)->get(route('quizzes.show', $quiz))
        ->assertOk()
        ->assertSee('Study Flashcards')
        ->assertSee('Answer key:')
        ->assertSee('Reviews are appraisal activities');

    $this->actingAs($user)->get('/quiz-analytics')->assertNotFound();
});

it('keeps the first attempt in order and randomizes retakes while keeping the current take stable', function () {
    $user = User::factory()->create();
    $quiz = Quiz::query()->create([
        'user_id' => $user->id,
        'title' => 'Randomized Questions',
        'source_file' => 'uploads/randomized.txt',
        'status' => Quiz::STATUS_READY,
    ]);

    foreach (range(1, 15) as $number) {
        $quiz->questions()->create([
            'type' => QuizQuestion::TYPE_MCQ,
            'question' => 'Question '.$number,
            'choices' => ['Correct '.$number, 'Option B', 'Option C', 'Option D'],
            'correct_answer' => 'Correct '.$number,
        ]);
    }

    $extractOrder = function ($response): array {
        preg_match_all('/<h2[^>]*>\\s*\\d+\\.\\s*Question (\\d+)\\s*<\\/h2>/', $response->getContent(), $matches);

        return $matches[1];
    };

    $firstAttempt = $this->actingAs($user)->get(route('quizzes.take', $quiz))->assertOk();
    $firstOrder = $extractOrder($firstAttempt);
    $refreshedFirstAttempt = $this->actingAs($user)->get(route('quizzes.take', $quiz))->assertOk();

    expect($firstOrder)->toBe(array_map('strval', range(1, 15)))
        ->and($extractOrder($refreshedFirstAttempt))->toBe($firstOrder);

    $this->actingAs($user)->post(route('quizzes.attempts.store', $quiz), [
        'answers' => $quiz->questions()->get()->mapWithKeys(fn (QuizQuestion $question): array => [
            $question->id => 'Option B',
        ])->all(),
    ])->assertRedirect();

    $retake = $this->actingAs($user)->get(route('quizzes.take', $quiz))->assertOk();
    $retakeOrder = $extractOrder($retake);
    $refreshedRetake = $this->actingAs($user)->get(route('quizzes.take', $quiz))->assertOk();

    expect($retakeOrder)->toHaveCount(15)
        ->and($retakeOrder)->not->toBe($firstOrder)
        ->and($extractOrder($refreshedRetake))->toBe($retakeOrder);
});

it('requires a started quiz, complete answers before timeout, and valid mcq choices', function () {
    $user = User::factory()->create();
    $quiz = Quiz::query()->create([
        'user_id' => $user->id,
        'title' => 'Validated Submission',
        'source_file' => 'uploads/validated.txt',
        'status' => Quiz::STATUS_READY,
    ]);
    $question = $quiz->questions()->create([
        'type' => QuizQuestion::TYPE_MCQ,
        'question' => 'Which option is valid?',
        'choices' => ['A', 'B', 'C', 'D'],
        'correct_answer' => 'A',
    ]);

    $this->actingAs($user)->post(route('quizzes.attempts.store', $quiz), [
        'answers' => [$question->id => 'A'],
    ])->assertRedirect(route('quizzes.take', $quiz))
        ->assertSessionHas('status');

    $this->actingAs($user)->get(route('quizzes.take', $quiz))->assertOk();
    $this->actingAs($user)->post(route('quizzes.attempts.store', $quiz), [
        'answers' => [],
    ])->assertSessionHasErrors('answers.'.$question->id);

    $this->actingAs($user)->post(route('quizzes.attempts.store', $quiz), [
        'answers' => [$question->id => 'Not an available choice'],
    ])->assertSessionHasErrors('answers.'.$question->id);

    expect(QuizAttempt::query()->where('quiz_id', $quiz->id)->count())->toBe(0);
});

it('accepts a partial quiz submission only after the server-side time limit expires', function () {
    $user = User::factory()->create();
    $quiz = Quiz::query()->create([
        'user_id' => $user->id,
        'title' => 'Timed Submission',
        'source_file' => 'uploads/timed.txt',
        'status' => Quiz::STATUS_READY,
    ]);
    $question = $quiz->questions()->create([
        'type' => QuizQuestion::TYPE_MCQ,
        'question' => 'Which option is valid?',
        'choices' => ['A', 'B', 'C', 'D'],
        'correct_answer' => 'A',
    ]);
    $sessionKey = 'quiz_attempt_started_at_'.$quiz->id;

    $this->actingAs($user)
        ->withSession([$sessionKey => now()->subMinutes(11)->timestamp])
        ->get(route('quizzes.take', $quiz))
        ->assertOk()
        ->assertSee('00:00');

    $response = $this->actingAs($user)->post(route('quizzes.attempts.store', $quiz), [
        'answers' => [],
    ]);

    $attempt = QuizAttempt::query()->where('quiz_id', $quiz->id)->firstOrFail();
    $response->assertRedirect(route('quiz-attempts.show', $attempt));
    expect($attempt->correct_count)->toBe(0)
        ->and($attempt->answers)->toHaveCount(1)
        ->and($attempt->answers->first()->user_answer)->toBe('');
});
