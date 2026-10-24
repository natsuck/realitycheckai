<?php

namespace App\Services;

use App\Exceptions\OpenAIRequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use JsonException;
use RuntimeException;
use Throwable;

class QuizGeneratorService
{
    public function __construct(private readonly OpenAIService $openai) {}

    /**
     * @return array{mcqs: array<int, array{question: string, choices: array<int, string>, answer: string, explanation: string}>, flashcards: array<int, array{front: string, back: string}>, confidence: float}
     */
    public function generate(string $documentText): array
    {
        $attempts = (int) config('services.openai.quiz_json_retries', 3);
        $numPredict = (int) config('services.openai.quiz_max_tokens', 6000);
        $model = (string) config('services.openai.models.quiz', 'gpt-4o-mini');
        $cacheKey = $this->cacheKey($documentText, $numPredict, $model);
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
                $lastResponse = $this->openai->generate(
                    $prompt,
                    [
                        'temperature' => 0.1,
                        'max_tokens' => $numPredict,
                        'schema_name' => 'quiz',
                    ],
                    $model,
                    false,
                    $this->quizSchema()
                );

                $validated = $this->validate($this->decodeJson($lastResponse));

                $this->cacheQuiz($cacheKey, $validated);

                return $validated;
            } catch (OpenAIRequestException $exception) {
                Log::error('AI quiz generation could not reach the configured provider.', [
                    'attempt' => $attempt,
                    'message' => $exception->getMessage(),
                ]);

                throw $exception;
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
    private function cacheKey(string $documentText, int $numPredict, string $model): string
    {
        return 'quiz_gen:mcq-flashcards-v2:'.hash('sha256', $model.'|0.1|'.$numPredict.'|'.$documentText);
    }

    private function cacheQuiz(string $cacheKey, array $quiz): void
    {
        $ttl = (int) config('services.openai.cache_ttl', 604800);

        if ($ttl <= 0) {
            return;
        }

        Cache::put($cacheKey, $quiz, now()->addSeconds($ttl));
    }

    /**
     * JSON schema for OpenAI structured outputs. Enforces exact counts,
     * four choices per MCQ, and separate flashcard front/back content.
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
                'explanation' => ['type' => 'string'],
            ],
            'required' => ['question', 'choices', 'answer', 'explanation'],
        ];

        $flashcardItem = [
            'type' => 'object',
            'properties' => [
                'front' => ['type' => 'string'],
                'back' => ['type' => 'string'],
            ],
            'required' => ['front', 'back'],
        ];

        return [
            'type' => 'object',
            'properties' => [
                'confidence' => ['type' => 'number'],
                'mcqs' => [
                    'type' => 'array',
                    'minItems' => 15,
                    'maxItems' => 15,
                    'items' => $mcqItem,
                ],
                'flashcards' => [
                    'type' => 'array',
                    'minItems' => 10,
                    'maxItems' => 10,
                    'items' => $flashcardItem,
                ],
            ],
            'required' => ['mcqs', 'flashcards'],
        ];
    }

    private function buildPrompt(string $documentText): string
    {
        return <<<PROMPT
You are an academic quiz generator for a thesis-grade education system.

Generate exactly:
- 15 multiple choice questions for a graded quiz
- 10 flashcards for a separate study mode

Rules:
- Questions and answers must come ONLY from the provided document.
- Do not use outside knowledge.
- The exact item counts above are mandatory; never return fewer items.
- If the document is concise, test different details or aspects that it explicitly supports, without adding outside facts.
- MCQ choices must contain exactly 4 clear choices.
- The MCQ answer must exactly match one of the choices.
- Include a brief explanation grounded in the document for every MCQ answer.
- Each flashcard front should ask about one important concept; its back should provide a concise answer.
- Do not repeat questions or flashcard fronts.
- Flashcards should reinforce the document without copying an MCQ word-for-word.
- Keep questions and flashcard fronts under 25 words.
- Keep every MCQ choice concise and unambiguous.
- Never include difficulty labels or extra fields.
- Return STRICT JSON only. Do not include Markdown, prose, comments, or code fences.

Required JSON shape:
{
  "confidence": 0,
  "mcqs": [
    {
      "question": "",
      "choices": ["", "", "", ""],
      "answer": "",
      "explanation": ""
    }
  ],
  "flashcards": [
    {
      "front": "",
      "back": ""
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
Return STRICT JSON only with exactly 15 MCQs and exactly 10 flashcards. These counts are mandatory; do not return fewer items.
Every MCQ answer must exactly match one of its four choices and include a short document-grounded explanation.
Each flashcard must include a concise front and back.
If the document is concise, test different explicitly stated details or aspects rather than omitting items or adding outside facts.
Do not include difficulty labels, identification questions, or any extra fields.

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
     * @return array{mcqs: array<int, array{question: string, choices: array<int, string>, answer: string, explanation: string}>, flashcards: array<int, array{front: string, back: string}>, confidence: float}
     */
    private function validate(array $data): array
    {
        if (! isset($data['mcqs'], $data['flashcards']) || ! is_array($data['mcqs']) || ! is_array($data['flashcards'])) {
            throw new RuntimeException('Quiz JSON must include mcqs and flashcards arrays.');
        }

        if (count($data['mcqs']) !== 15 || count($data['flashcards']) !== 10) {
            throw new RuntimeException(sprintf(
                'Quiz JSON must contain exactly 15 MCQs and 10 flashcards; received %d MCQs and %d flashcards.',
                count($data['mcqs']),
                count($data['flashcards'])
            ));
        }

        $mcqs = [];
        $questionTexts = [];

        foreach (array_values($data['mcqs']) as $mcq) {
            if (! is_array($mcq)) {
                throw new RuntimeException('Each MCQ must be an object.');
            }

            $question = $this->requiredString($mcq['question'] ?? null, 'MCQ question');
            $normalizedQuestion = $this->normalize($question);

            if (isset($questionTexts[$normalizedQuestion])) {
                throw new RuntimeException('MCQ questions must be distinct.');
            }

            $questionTexts[$normalizedQuestion] = true;
            $choices = array_values(array_filter(array_map('trim', $mcq['choices'] ?? [])));

            if (count($choices) !== 4) {
                throw new RuntimeException('Each MCQ must include exactly 4 choices.');
            }

            if (count(array_unique(array_map($this->normalize(...), $choices))) !== 4) {
                throw new RuntimeException('Each MCQ must include four distinct choices.');
            }

            $answer = $this->normalizeMcqAnswer($mcq['answer'] ?? '', $choices);

            $mcqs[] = [
                'question' => $question,
                'choices' => $choices,
                'answer' => $answer,
                'explanation' => $this->requiredString($mcq['explanation'] ?? null, 'MCQ explanation'),
            ];
        }

        $flashcards = [];
        $flashcardFronts = [];

        foreach (array_values($data['flashcards']) as $item) {
            if (! is_array($item)) {
                throw new RuntimeException('Each flashcard must be an object.');
            }

            $front = $this->requiredString($item['front'] ?? null, 'Flashcard front');
            $normalizedFront = $this->normalize($front);

            if (isset($flashcardFronts[$normalizedFront])) {
                throw new RuntimeException('Flashcard fronts must be distinct.');
            }

            $flashcardFronts[$normalizedFront] = true;
            $flashcards[] = [
                'front' => $front,
                'back' => $this->requiredString($item['back'] ?? null, 'Flashcard back'),
            ];
        }

        return [
            'confidence' => $this->confidence($data['confidence'] ?? 85),
            'mcqs' => $mcqs,
            'flashcards' => $flashcards,
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
