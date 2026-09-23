<?php

use App\Jobs\GenerateQuizFromMaterial;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\AnswerEvaluationService;
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
        'difficulty' => 'easy',
    ]);

    $identification = $quiz->questions()->create([
        'type' => QuizQuestion::TYPE_IDENTIFICATION,
        'question' => 'Identify the main processing component of a computer.',
        'correct_answer' => 'Central Processing Unit',
        'difficulty' => 'medium',
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
        ->and($attempt->identification_score)->toBe(1);
});
