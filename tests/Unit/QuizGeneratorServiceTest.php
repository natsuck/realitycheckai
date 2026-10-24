<?php

use App\Exceptions\OpenAIRequestException;
use App\Services\OpenAIService;
use App\Services\QuizGeneratorService;
use Illuminate\Support\Facades\Cache;

it('generates 15 explained mcqs and 10 separate flashcards without difficulty labels', function () {
    config(['services.openai.quiz_json_retries' => 1]);
    Cache::flush();

    $response = json_encode([
        'confidence' => 91,
        'mcqs' => array_map(fn (int $index): array => [
            'question' => 'What is software quality assurance '.$index.'?',
            'choices' => ['A planned process', 'A marketing plan', 'A hiring method', 'A budget category'],
            'answer' => 'A planned process',
            'explanation' => 'The material describes SQA as planned quality activities.',
        ], range(1, 15)),
        'flashcards' => array_map(fn (int $index): array => [
            'front' => 'What does concept '.$index.' describe?',
            'back' => 'A distinct quality concept from the learning material.',
        ], range(1, 10)),
    ], JSON_THROW_ON_ERROR);

    $this->instance(OpenAIService::class, new class($response) extends OpenAIService
    {
        public function __construct(private readonly string $response) {}

        public function generate(string $prompt, array $options = [], ?string $model = null, bool $jsonMode = false, ?array $schema = null): string
        {
            expect($prompt)->toContain('15 multiple choice questions')
                ->and($prompt)->toContain('separate study mode')
                ->and($prompt)->toContain('Never include difficulty labels');

            return $this->response;
        }
    });

    $quiz = app(QuizGeneratorService::class)->generate('Software quality assurance material.');

    expect($quiz['mcqs'])->toHaveCount(15)
        ->and($quiz['flashcards'])->toHaveCount(10)
        ->and($quiz['mcqs'][0]['answer'])->toBe('A planned process')
        ->and($quiz['mcqs'][0]['explanation'])->toContain('planned quality activities')
        ->and($quiz['flashcards'][0])->toBe([
            'front' => 'What does concept 1 describe?',
            'back' => 'A distinct quality concept from the learning material.',
        ])
        ->and($quiz['mcqs'][0])->not->toHaveKey('difficulty');
});

it('does not retry quiz repair prompts when the OpenAI API cannot be reached', function () {
    config(['services.openai.quiz_json_retries' => 3]);
    $calls = (object) ['count' => 0];

    $this->instance(OpenAIService::class, new class($calls) extends OpenAIService
    {
        public function __construct(private readonly object $calls) {}

        public function generate(string $prompt, array $options = [], ?string $model = null, bool $jsonMode = false, ?array $schema = null): string
        {
            $this->calls->count++;
            throw new OpenAIRequestException('Unable to reach the OpenAI API. Check internet/DNS connectivity.');
        }
    });

    expect(fn () => app(QuizGeneratorService::class)->generate('Source material.'))
        ->toThrow(OpenAIRequestException::class, 'internet/DNS connectivity')
        ->and($calls->count)->toBe(1);
});

it('retries incomplete quiz output with an explicit item-count correction', function () {
    config([
        'services.openai.quiz_json_retries' => 2,
        'services.openai.quiz_max_tokens' => 6000,
    ]);
    Cache::flush();

    $makeQuiz = fn (int $mcqCount): string => json_encode([
        'confidence' => 85,
        'mcqs' => array_map(fn (int $index): array => [
            'question' => 'Question '.$index,
            'choices' => ['Correct '.$index, 'Option B', 'Option C', 'Option D'],
            'answer' => 'Correct '.$index,
            'explanation' => 'Supported by the source material.',
        ], range(1, $mcqCount)),
        'flashcards' => array_map(fn (int $index): array => [
            'front' => 'Flashcard '.$index,
            'back' => 'A source-grounded answer.',
        ], range(1, 10)),
    ], JSON_THROW_ON_ERROR);

    $responses = [$makeQuiz(14), $makeQuiz(15)];
    $calls = (object) ['count' => 0, 'repairPrompt' => null];

    $this->instance(OpenAIService::class, new class($responses, $calls) extends OpenAIService
    {
        public function __construct(private readonly array $responses, private readonly object $calls) {}

        public function generate(string $prompt, array $options = [], ?string $model = null, bool $jsonMode = false, ?array $schema = null): string
        {
            expect($options['max_tokens'])->toBe(6000);

            if ($this->calls->count === 1) {
                $this->calls->repairPrompt = $prompt;
            }

            return $this->responses[$this->calls->count++];
        }
    });

    $quiz = app(QuizGeneratorService::class)->generate('Source material with enough detail.');

    expect($quiz['mcqs'])->toHaveCount(15)
        ->and($quiz['flashcards'])->toHaveCount(10)
        ->and($calls->count)->toBe(2)
        ->and($calls->repairPrompt)->toContain('received 14 MCQs and 10 flashcards')
        ->and($calls->repairPrompt)->toContain('exactly 15 MCQs and exactly 10 flashcards');
});
