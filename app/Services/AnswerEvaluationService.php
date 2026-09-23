<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use JsonException;
use RuntimeException;
use Throwable;

class AnswerEvaluationService
{
    public function __construct(private readonly OpenAIService $openai) {}

    /**
     * @return array{is_correct: bool, confidence: float, explanation: string}
     */
    public function evaluate(string $question, string $correctAnswer, ?string $studentAnswer): array
    {
        $studentAnswer = trim((string) $studentAnswer);

        if ($studentAnswer === '') {
            return [
                'is_correct' => false,
                'confidence' => 100.0,
                'explanation' => 'No answer was provided.',
            ];
        }

        $prompt = $this->buildPrompt($question, $correctAnswer, $studentAnswer);
        $attempts = (int) config('services.openai.answer_json_retries', 2);
        $lastError = '';

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $response = $this->openai->generate(
                    $prompt,
                    [
                        'temperature' => 0.0,
                        'max_tokens' => 300,
                        'schema_name' => 'answer_evaluation',
                    ],
                    (string) config('services.openai.models.answer', 'gpt-4o-mini'),
                    false,
                    $this->evaluationSchema()
                );

                return $this->validate($this->decodeJson($response));
            } catch (Throwable $exception) {
                $lastError = $exception->getMessage();

                Log::warning('AI answer evaluation failed validation.', [
                    'attempt' => $attempt,
                    'message' => $lastError,
                ]);
            }
        }

        Log::warning('Falling back to deterministic identification checking.', [
            'message' => $lastError,
        ]);

        return $this->fallbackEvaluate($correctAnswer, $studentAnswer);
    }

    /**
     * BATCH EVALUATION
     *
     * Grades many identification answers in a single AI request instead of
     * one request per answer. Answers that are empty or exactly match the
     * key after normalization are resolved deterministically and never
     * reach the model, so a typical submission costs zero or one AI call.
     *
     * @param  array<int, array{key: int|string, question: string, correct_answer: string, student_answer: ?string}>  $items
     * @return array<int|string, array{is_correct: bool, confidence: float, explanation: string}> keyed by item key
     */
    public function evaluateBatch(array $items): array
    {
        $results = [];
        $pending = [];
        $keys = [];

        foreach ($items as $item) {
            $key = $item['key'];
            $studentAnswer = trim((string) ($item['student_answer'] ?? ''));

            if ($studentAnswer === '') {
                $results[$key] = [
                    'is_correct' => false,
                    'confidence' => 100.0,
                    'explanation' => 'No answer was provided.',
                ];

                continue;
            }

            if ($this->normalize($item['correct_answer']) === $this->normalize($studentAnswer)) {
                $results[$key] = [
                    'is_correct' => true,
                    'confidence' => 100.0,
                    'explanation' => 'The answer exactly matches after normalization.',
                ];

                continue;
            }

            $keys[] = $key;
            $pending[] = $item;
        }

        if ($pending !== []) {
            foreach ($this->evaluatePendingWithAi($pending) as $index => $evaluation) {
                $results[$keys[$index]] = $evaluation;
            }
        }

        return $results;
    }

    /**
     * @param  array<int, array{question: string, correct_answer: string, student_answer: string}>  $pending
     * @return array<int, array{is_correct: bool, confidence: float, explanation: string}> aligned with $pending indexes
     */
    private function evaluatePendingWithAi(array $pending): array
    {
        $prompt = $this->buildBatchPrompt($pending);
        $attempts = (int) config('services.openai.answer_json_retries', 2);

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $response = $this->openai->generate(
                    $prompt,
                    [
                        'temperature' => 0.0,
                        'max_tokens' => 600,
                        'schema_name' => 'answer_batch_evaluation',
                    ],
                    (string) config('services.openai.models.answer', 'gpt-4o-mini'),
                    false,
                    $this->batchSchema()
                );

                return $this->mapBatchResponse($response, $pending);
            } catch (Throwable $exception) {
                Log::warning('AI batch evaluation failed.', [
                    'attempt' => $attempt,
                    'message' => $exception->getMessage(),
                    'item_count' => count($pending),
                ]);
            }
        }

        return collect($pending)
            ->map(fn (array $item) => $this->fallbackEvaluate(
                $item['correct_answer'],
                $item['student_answer']
            ))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{question: string, correct_answer: string, student_answer: string}>  $pending
     */
    private function buildBatchPrompt(array $pending): string
    {
        $itemBlock = collect($pending)
            ->map(fn (array $item, int $index) => sprintf(
                "[%d] Question: %s\n    Correct answer: %s\n    Student answer: %s",
                $index,
                $item['question'],
                $item['correct_answer'],
                $item['student_answer']
            ))
            ->implode("\n");

        return <<<PROMPT
You are an academic answer checker.

Task:
Compare each student's identification answer with its correct answer.

Rules:
- Mark correct when the two answers are semantically equivalent.
- Accept expanded forms, abbreviations, harmless word order differences, and minor grammar differences.
- Reject answers that are vague, contradictory, or missing the key concept.
- Grade every item independently and echo its given index in each result.
- Return STRICT JSON only.

JSON shape:
{"results": [{"index": 0, "is_correct": true, "confidence": 0, "explanation": ""}]}

Items to grade:
{$itemBlock}
PROMPT;
    }

    /**
     * @param  array<int, array{question: string, correct_answer: string, student_answer: string}>  $pending
     * @return array<int, array{is_correct: bool, confidence: float, explanation: string}>
     */
    private function mapBatchResponse(string $response, array $pending): array
    {
        $decoded = $this->decodeJson($response);

        if (! isset($decoded['results']) || ! is_array($decoded['results'])) {
            throw new RuntimeException('Batch evaluation JSON must include a results array.');
        }

        $byIndex = [];

        foreach ($decoded['results'] as $row) {
            if (! is_array($row) || ! array_key_exists('index', $row)) {
                continue;
            }

            $index = (int) $row['index'];

            if ($index < 0 || $index >= count($pending) || ! array_key_exists('is_correct', $row)) {
                continue;
            }

            $byIndex[$index] = [
                'is_correct' => filter_var($row['is_correct'], FILTER_VALIDATE_BOOL),
                'confidence' => $this->confidence($row['confidence'] ?? 70),
                'explanation' => trim((string) ($row['explanation'] ?? 'AI semantic evaluation completed.')),
            ];
        }

        return collect($pending)
            ->map(fn (array $item, int $index) => $byIndex[$index]
                ?? $this->fallbackEvaluate($item['correct_answer'], $item['student_answer']))
            ->values()
            ->all();
    }

    /**
     * JSON schema for OpenAI structured outputs on single evaluations.
     *
     * @return array<string, mixed>
     */
    private function evaluationSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'is_correct' => ['type' => 'boolean'],
                'confidence' => ['type' => 'number'],
                'explanation' => ['type' => 'string'],
            ],
            'required' => ['is_correct'],
        ];
    }

    /**
     * JSON schema for OpenAI structured outputs on batch evaluations.
     *
     * @return array<string, mixed>
     */
    private function batchSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'results' => [
                    'type' => 'array',
                    'minItems' => 1,
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'index' => ['type' => 'integer'],
                            'is_correct' => ['type' => 'boolean'],
                            'confidence' => ['type' => 'number'],
                            'explanation' => ['type' => 'string'],
                        ],
                        'required' => ['index', 'is_correct'],
                    ],
                ],
            ],
            'required' => ['results'],
        ];
    }

    private function buildPrompt(string $question, string $correctAnswer, string $studentAnswer): string
    {
        return <<<PROMPT
You are an academic answer checker.

Task:
Compare the student's identification answer with the correct answer.

Rules:
- Mark correct if the student's answer is semantically equivalent.
- Accept expanded forms, abbreviations, harmless word order differences, and minor grammar differences.
- Reject answers that are vague, contradictory, or missing the key concept.
- Return STRICT JSON only.

JSON shape:
{
  "is_correct": true,
  "confidence": 0,
  "explanation": ""
}

Question: {$question}
Correct answer: {$correctAnswer}
Student answer: {$studentAnswer}
PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $response): array
    {
        $json = trim($response);
        $json = preg_replace('/^```(?:json)?\s*/i', '', $json) ?? $json;
        $json = preg_replace('/\s*```$/', '', $json) ?? $json;

        $start = strpos($json, '{');
        $end = strrpos($json, '}');

        if ($start === false || $end === false || $end <= $start) {
            throw new RuntimeException('AI response did not contain JSON.');
        }

        $json = substr($json, $start, $end - $start + 1);
        $json = preg_replace('/,\s*([}\]])/', '$1', $json) ?? $json;

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Malformed evaluation JSON: '.$exception->getMessage(), previous: $exception);
        }

        if (! is_array($decoded)) {
            throw new RuntimeException('Decoded answer evaluation is not an object.');
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{is_correct: bool, confidence: float, explanation: string}
     */
    private function validate(array $data): array
    {
        if (! array_key_exists('is_correct', $data)) {
            throw new RuntimeException('Evaluation JSON must include is_correct.');
        }

        return [
            'is_correct' => filter_var($data['is_correct'], FILTER_VALIDATE_BOOL),
            'confidence' => $this->confidence($data['confidence'] ?? 80),
            'explanation' => trim((string) ($data['explanation'] ?? 'AI semantic evaluation completed.')),
        ];
    }

    /**
     * @return array{is_correct: bool, confidence: float, explanation: string}
     */
    private function fallbackEvaluate(string $correctAnswer, string $studentAnswer): array
    {
        $expected = $this->normalize($correctAnswer);
        $actual = $this->normalize($studentAnswer);

        if ($expected === $actual) {
            return [
                'is_correct' => true,
                'confidence' => 90.0,
                'explanation' => 'The answer exactly matches after normalization.',
            ];
        }

        if ($expected !== '' && str_contains($actual, $expected)) {
            return [
                'is_correct' => true,
                'confidence' => 75.0,
                'explanation' => 'The answer contains the required concept.',
            ];
        }

        $distance = levenshtein($expected, $actual);
        $length = max(strlen($expected), strlen($actual), 1);
        $similarity = 1 - ($distance / $length);

        if ($similarity >= 0.82) {
            return [
                'is_correct' => true,
                'confidence' => 65.0,
                'explanation' => 'The answer is highly similar to the expected answer.',
            ];
        }

        return [
            'is_correct' => false,
            'confidence' => 55.0,
            'explanation' => 'The answer does not sufficiently match the expected concept.',
        ];
    }

    private function confidence(mixed $value): float
    {
        $confidence = is_numeric($value) ? (float) $value : 80.0;

        return max(0.0, min(100.0, $confidence));
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }
}
