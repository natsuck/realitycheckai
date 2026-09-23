<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use JsonException;
use RuntimeException;
use Throwable;

class QuizGeneratorService
{
    public function __construct(private readonly OllamaService $ollama) {}

    /**
     * @return array{mcqs: array<int, array<string, mixed>>, identification: array<int, array<string, mixed>>, confidence: float}
     */
    public function generate(string $documentText): array
    {
        $attempts = (int) config('services.ollama.quiz_json_retries', 3);
        $numPredict = (int) config('services.ollama.quiz_num_predict', 2500);
        $cacheKey = $this->cacheKey($documentText, $numPredict);
        $prompt = $this->buildPrompt($documentText);
        $lastError = 'Unknown AI generation error.';
        $lastResponse = '';

        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            Log::info('AI quiz generation served from cache.', [
                'cache_key' => $cacheKey,
            ]);

            return $cached;
        }

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $lastResponse = $this->ollama->generate(
                    $prompt,
                    [
                        'temperature' => 0.1,
                        'num_predict' => $numPredict,
                    ],
                    null,
                    false,
                    $this->quizSchema()
                );

                $validated = $this->validate($this->decodeJson($lastResponse));

                $this->cacheQuiz($cacheKey, $validated);

                return $validated;
            } catch (Throwable $exception) {
                $lastError = $exception->getMessage();

                Log::warning('AI quiz JSON validation failed.', [
                    'attempt' => $attempt,
                    'message' => $lastError,
                    'response_preview' => mb_substr($lastResponse, 0, 500),
                ]);

                $prompt = $this->buildRepairPrompt($documentText, $lastResponse, $lastError);
            }
        }

        throw new RuntimeException('AI quiz generation failed after validation retries: '.$lastError);
    }

    /**
     * Cache key for a validated quiz. Keyed by model, decoding settings,
     * and the exact document text so re-uploading the same material skips
     * the multi-second LLM call entirely.
     */
    private function cacheKey(string $documentText, int $numPredict): string
    {
        $model = (string) config('services.ollama.model', 'llama3');

        return 'quiz_gen:'.hash('sha256', $model.'|0.1|'.$numPredict.'|'.$documentText);
    }

    private function cacheQuiz(string $cacheKey, array $quiz): void
    {
        $ttl = (int) config('services.ollama.cache_ttl', 604800);

        if ($ttl <= 0) {
            return;
        }

        Cache::put($cacheKey, $quiz, now()->addSeconds($ttl));
    }

    /**
     * JSON schema for Ollama structured outputs. Enforces exact counts
     * (10 MCQs, 5 identification items), 4 choices per MCQ, and the
     * difficulty enum at decode time so malformed responses are nearly
     * impossible and validation retries almost never trigger.
     *
     * @return array<string, mixed>
     */
    private function quizSchema(): array
    {
        $mcqItem = [
            'type' => 'object',
            'properties' => [
                'question' => ['type' => 'string'],
                'choices' => [
                    'type' => 'array',
                    'minItems' => 4,
                    'maxItems' => 4,
                    'items' => ['type' => 'string'],
                ],
                'answer' => ['type' => 'string'],
                'difficulty' => [
                    'type' => 'string',
                    'enum' => ['easy', 'medium', 'hard'],
                ],
            ],
            'required' => ['question', 'choices', 'answer', 'difficulty'],
        ];

        $identificationItem = [
            'type' => 'object',
            'properties' => [
                'question' => ['type' => 'string'],
                'answer' => ['type' => 'string'],
                'difficulty' => [
                    'type' => 'string',
                    'enum' => ['easy', 'medium', 'hard'],
                ],
            ],
            'required' => ['question', 'answer', 'difficulty'],
        ];

        return [
            'type' => 'object',
            'properties' => [
                'confidence' => ['type' => 'number'],
                'mcqs' => [
                    'type' => 'array',
                    'minItems' => 10,
                    'maxItems' => 10,
                    'items' => $mcqItem,
                ],
                'identification' => [
                    'type' => 'array',
                    'minItems' => 5,
                    'maxItems' => 5,
                    'items' => $identificationItem,
                ],
            ],
            'required' => ['mcqs', 'identification'],
        ];
    }

    private function buildPrompt(string $documentText): string
    {
        return <<<PROMPT
You are an academic quiz generator for a thesis-grade education system.

Generate exactly:
- 10 multiple choice questions
- 5 identification questions

Rules:
- Questions and answers must come ONLY from the provided document.
- Do not use outside knowledge.
- Avoid hallucinations. If the document does not support a question, do not ask it.
- Mix easy, medium, and hard difficulty.
- MCQ choices must contain exactly 4 clear choices.
- The MCQ answer must exactly match one of the choices.
- Identification answers must be concise exact answers from the document.
- Keep every question under 20 words.
- Keep every MCQ choice under 10 words.
- Keep identification answers between 1 and 5 words.
- Never include explanations, notes, or extra fields. Short answers only.
- Return STRICT JSON only. Do not include Markdown, prose, comments, or code fences.

Required JSON shape:
{
  "confidence": 0,
  "mcqs": [
    {
      "question": "",
      "choices": ["", "", "", ""],
      "answer": "",
      "difficulty": "easy"
    }
  ],
  "identification": [
    {
      "question": "",
      "answer": "",
      "difficulty": "medium"
    }
  ]
}

DOCUMENT:
{$documentText}
PROMPT;
    }

    private function buildRepairPrompt(string $documentText, string $badResponse, string $error): string
    {
        $preview = mb_substr($badResponse, 0, 2500);

        return <<<PROMPT
Your previous quiz response was invalid and could not be saved.

Validation error:
{$error}

Invalid response preview:
{$preview}

Regenerate the quiz using ONLY the document below.
Return STRICT JSON only with exactly 10 MCQs and 5 identification questions.
Every MCQ answer must exactly match one of its four choices.
Each question must include difficulty as easy, medium, or hard.
Keep questions under 20 words, choices under 10 words, and identification answers between 1 and 5 words.
Do not include explanations or any extra fields.

DOCUMENT:
{$documentText}
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
            throw new RuntimeException('AI response did not contain a JSON object.');
        }

        $json = substr($json, $start, $end - $start + 1);
        $json = preg_replace('/,\s*([}\]])/', '$1', $json) ?? $json;

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Malformed JSON: '.$exception->getMessage(), previous: $exception);
        }

        if (! is_array($decoded)) {
            throw new RuntimeException('Decoded quiz JSON is not an object.');
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{mcqs: array<int, array<string, mixed>>, identification: array<int, array<string, mixed>>, confidence: float}
     */
    private function validate(array $data): array
    {
        if (! isset($data['mcqs'], $data['identification']) || ! is_array($data['mcqs']) || ! is_array($data['identification'])) {
            throw new RuntimeException('Quiz JSON must include mcqs and identification arrays.');
        }

        if (count($data['mcqs']) !== 10 || count($data['identification']) !== 5) {
            throw new RuntimeException('Quiz JSON must contain exactly 10 MCQs and 5 identification questions.');
        }

        $mcqs = [];

        foreach (array_values($data['mcqs']) as $index => $mcq) {
            if (! is_array($mcq)) {
                throw new RuntimeException('Each MCQ must be an object.');
            }

            $question = $this->requiredString($mcq['question'] ?? null, 'MCQ question');
            $choices = array_values(array_filter(array_map('trim', $mcq['choices'] ?? [])));

            if (count($choices) !== 4) {
                throw new RuntimeException('Each MCQ must include exactly 4 choices.');
            }

            $answer = $this->normalizeMcqAnswer($mcq['answer'] ?? '', $choices);

            $mcqs[] = [
                'question' => $question,
                'choices' => $choices,
                'answer' => $answer,
                'difficulty' => $this->difficulty($mcq['difficulty'] ?? null, $index),
            ];
        }

        $identification = [];

        foreach (array_values($data['identification']) as $index => $item) {
            if (! is_array($item)) {
                throw new RuntimeException('Each identification question must be an object.');
            }

            $identification[] = [
                'question' => $this->requiredString($item['question'] ?? null, 'Identification question'),
                'answer' => $this->requiredString($item['answer'] ?? null, 'Identification answer'),
                'difficulty' => $this->difficulty($item['difficulty'] ?? null, $index + 10),
            ];
        }

        return [
            'confidence' => $this->confidence($data['confidence'] ?? 85),
            'mcqs' => $mcqs,
            'identification' => $identification,
        ];
    }

    private function requiredString(mixed $value, string $label): string
    {
        if (! is_string($value) || trim($value) === '') {
            throw new RuntimeException($label.' is required.');
        }

        return trim($value);
    }

    /**
     * @param  array<int, string>  $choices
     */
    private function normalizeMcqAnswer(mixed $answer, array $choices): string
    {
        $answer = trim((string) $answer);
        $letterMap = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3];
        $upper = strtoupper($answer);

        if (isset($letterMap[$upper])) {
            return $choices[$letterMap[$upper]];
        }

        foreach ($choices as $choice) {
            if ($this->normalize($choice) === $this->normalize($answer)) {
                return $choice;
            }
        }

        throw new RuntimeException('MCQ answer must exactly match one of the choices.');
    }

    private function difficulty(mixed $value, int $index): string
    {
        $value = strtolower(trim((string) $value));

        if (in_array($value, ['easy', 'medium', 'hard'], true)) {
            return $value;
        }

        return ['easy', 'medium', 'hard'][$index % 3];
    }

    private function confidence(mixed $value): float
    {
        $confidence = is_numeric($value) ? (float) $value : 85.0;

        return max(0.0, min(100.0, $confidence));
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }
}
