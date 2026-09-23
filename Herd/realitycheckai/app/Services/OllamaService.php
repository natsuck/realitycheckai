<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;
use RuntimeException;
use Throwable;

class OllamaService
{
    /**
     * @param  array<string, mixed>|null  $schema  Optional JSON schema for
     *                                             Ollama structured outputs.
     *                                             Constrains decoding so the
     *                                             model cannot emit invalid
     *                                             JSON (removes retry loops).
     */
    public function generate(string $prompt, array $options = [], ?string $model = null, bool $jsonMode = false, ?array $schema = null): string
    {
        $baseUrl = rtrim((string) config('services.ollama.base_url', 'http://localhost:11434'), '/');
        $timeout = (int) config('services.ollama.timeout', 90);
        $retries = (int) config('services.ollama.retries', 2);
        $model ??= (string) config('services.ollama.model', 'llama3');

        $payload = [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => false,

            // Keeps the model resident between requests so idle periods do
            // not trigger a full model reload on the next generation.
            'keep_alive' => (string) config('services.ollama.keep_alive', '30m'),

            'options' => array_replace($this->defaultOptions(), $options),
        ];

        if ($schema !== null) {
            $payload['format'] = $schema;
        } elseif ($jsonMode) {
            $payload['format'] = 'json';
        }

        try {
            $request = Http::timeout($timeout);

            if ($retries > 0) {
                $request = $request->retry($retries, 1000);
            }

            $response = $request->post($baseUrl.'/api/generate', $payload);

            if ($response->failed()) {
                Log::warning('Ollama returned an unsuccessful response.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new RuntimeException('Ollama returned HTTP '.$response->status().'.');
            }

            $data = $response->json();
            $answer = trim((string) ($data['response'] ?? ''));

            if ($answer === '') {
                throw new RuntimeException('Ollama returned an empty response.');
            }

            return $answer;
        } catch (Throwable $exception) {
            Log::error('Ollama request failed.', [
                'model' => $model,
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Ollama request failed: '.$exception->getMessage(), previous: $exception);
        }
    }

    /**
     * Base decoding options shared by every request. num_thread / num_gpu
     * are only sent when explicitly configured so default Ollama behavior
     * is untouched; callers may always override via $options.
     *
     * @return array<string, mixed>
     */
    private function defaultOptions(): array
    {
        $options = [
            'temperature' => (float) config('services.ollama.temperature', 0.2),
            'num_ctx' => (int) config('services.ollama.num_ctx', 8192),
        ];

        foreach (['num_thread' => 'services.ollama.num_thread', 'num_gpu' => 'services.ollama.num_gpu'] as $option => $key) {
            $value = config($key);

            if (is_numeric($value) && (int) $value > 0) {
                $options[$option] = (int) $value;
            }
        }

        return $options;
    }

    /**
     * @return array<string, mixed>
     */
    public function analyze($answers): array
    {
        $answerRows = $this->normalizeAnswerRows($answers);
        $answerJson = json_encode($answerRows, JSON_PRETTY_PRINT);

        $prompt = <<<PROMPT
You are an academic program recommendation engine for a Laravel thesis system.

Analyze the student's Likert-scale assessment answers and recommend the strongest matching academic program based on the assessment evidence.

Available programs:
- BSCS: Bachelor of Science in Computer Science
- BSIT: Bachelor of Science in Information Technology
- BSAIS: Bachelor of Science in Accounting Information System
- BSHM: Bachelor of Science in Hospitality Management
- BSTM: Bachelor of Science in Tourism Management
- ACT: Associate in Computer Technology

Rules:
- Use only the submitted assessment answers.
- Do not invent grades, personal background, or achievements.
- If multiple programs have similar alignment, explain why and include them as alternatives.
- Keep explanations student-friendly and academically appropriate.
- The recommendation is guidance-based and should not be treated as the sole basis for enrollment decisions.
- Return STRICT JSON only. Do not include markdown, prose, or code fences.

Required JSON shape:
{
  "recommended_program": {
    "code": "BSIT",
    "name": "Bachelor of Science in Information Technology"
  },
  "confidence": 0,
  "fit_level": "Strong Fit",
  "reason": "",
  "strengths": ["", "", ""],
  "development_areas": ["", ""],
  "feedback": "",
  "evidence": ["", "", ""],
  "next_steps": ["", "", ""],
  "alternative_programs": [
    {
      "code": "BSCS",
      "name": "Bachelor of Science in Computer Science",
      "reason": ""
    }
  ]
}

Student assessment answers:
{$answerJson}
PROMPT;

        try {
            $response = $this->generate(
                $prompt,
                [
                    'temperature' => 0.15,
                    'num_predict' => 1200,
                ],
                null,
                false,
                $this->recommendationSchema()
            );

            return $this->normalizeRecommendation(
                $this->decodeJsonObject($response),
                $answerRows
            );
        } catch (Throwable $exception) {
            // Structured outputs can fail on very old Ollama builds that do
            // not understand schema formats. Retry once in plain JSON mode
            // before giving up and using the deterministic recommender.
            try {
                $response = $this->generate(
                    $prompt,
                    [
                        'temperature' => 0.15,
                        'num_predict' => 1200,
                    ],
                    null,
                    true
                );

                return $this->normalizeRecommendation(
                    $this->decodeJsonObject($response),
                    $answerRows
                );
            } catch (Throwable $retryException) {
                Log::warning('Structured AI assessment failed. Falling back to deterministic recommendation.', [
                    'message' => $retryException->getMessage(),
                ]);

                return $this->fallbackRecommendation($answerRows, $retryException->getMessage());
            }
        }
    }

    /**
     * JSON schema constraining Ollama structured outputs for assessment
     * recommendations. Required fields are kept minimal so generation never
     * fails hard; normalizeRecommendation() fills every remaining default.
     *
     * @return array<string, mixed>
     */
    private function recommendationSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'recommended_program' => [
                    'type' => 'object',
                    'properties' => [
                        'code' => ['type' => 'string'],
                        'name' => ['type' => 'string'],
                    ],
                    'required' => ['code'],
                ],
                'confidence' => ['type' => 'number'],
                'fit_level' => ['type' => 'string'],
                'reason' => ['type' => 'string'],
                'strengths' => ['type' => 'array', 'items' => ['type' => 'string']],
                'development_areas' => ['type' => 'array', 'items' => ['type' => 'string']],
                'feedback' => ['type' => 'string'],
                'evidence' => ['type' => 'array', 'items' => ['type' => 'string']],
                'next_steps' => ['type' => 'array', 'items' => ['type' => 'string']],
                'alternative_programs' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'code' => ['type' => 'string'],
                            'name' => ['type' => 'string'],
                            'reason' => ['type' => 'string'],
                        ],
                        'required' => ['code'],
                    ],
                ],
            ],
            'required' => ['recommended_program', 'confidence', 'reason'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeAnswerRows(mixed $answers): array
    {
        if (is_string($answers)) {
            try {
                $decoded = json_decode($answers, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                return [];
            }

            return is_array($decoded)
                ? array_values($decoded)
                : [];
        }

        return is_array($answers)
            ? array_values($answers)
            : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonObject(string $response): array
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
            throw new RuntimeException(
                'Malformed AI recommendation JSON: '.$exception->getMessage(),
                previous: $exception
            );
        }

        if (! is_array($decoded)) {
            throw new RuntimeException('AI recommendation JSON must be an object.');
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $answerRows
     * @return array<string, mixed>
     */
    private function normalizeRecommendation(array $data, array $answerRows): array
    {
        $programs = $this->programCatalog();

        $program = $data['recommended_program'] ?? [];

        $code = is_array($program)
            ? strtoupper(trim((string) ($program['code'] ?? '')))
            : $this->extractProgramCode((string) $program);

        if (! array_key_exists($code, $programs)) {
            $code = $this->fallbackProgramCode($answerRows);
        }

        $alternatives = collect($data['alternative_programs'] ?? [])
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item) use ($programs): array {
                $alternativeCode = strtoupper(trim((string) ($item['code'] ?? '')));

                return [
                    'code' => array_key_exists($alternativeCode, $programs)
                        ? $alternativeCode
                        : 'BSIT',

                    'name' => $programs[$alternativeCode]
                        ?? $programs['BSIT'],

                    'reason' => trim(
                        (string) (
                            $item['reason']
                            ?? 'Related fit based on the assessment pattern.'
                        )
                    ),
                ];
            })
            ->take(2)
            ->values()
            ->all();

        return [
            'recommended_program' => [
                'code' => $code,
                'name' => $programs[$code],
            ],

            'confidence' => $this->clampConfidence(
                $data['confidence'] ?? 78
            ),

            'fit_level' => trim(
                (string) ($data['fit_level'] ?? 'Good Fit')
            ),

            'reason' => trim(
                (string) (
                    $data['reason']
                    ?? 'The recommendation is based on assessment trait alignment.'
                )
            ),

            'strengths' => $this->stringList(
                $data['strengths'] ?? [],
                3,
                [
                    'Learning readiness',
                    'Career alignment',
                    'Program compatibility',
                ]
            ),

            'development_areas' => $this->stringList(
                $data['development_areas'] ?? [],
                2,
                [
                    'Explore program subjects more deeply',
                    'Compare career paths before enrolling',
                ]
            ),

            'feedback' => trim(
                (string) (
                    $data['feedback']
                    ?? 'Use this recommendation together with adviser consultation and personal interests.'
                )
            ),

            'evidence' => $this->stringList(
                $data['evidence'] ?? [],
                3,
                [
                    'Assessment answers show consistent academic-interest patterns.',
                ]
            ),

            'next_steps' => $this->stringList(
                $data['next_steps'] ?? [],
                3,
                [
                    'Review the curriculum',
                    'Consult an adviser',
                    'Explore related career opportunities',
                ]
            ),

            'alternative_programs' => $alternatives,

            'disclaimer' => 'This assessment provides guidance-based academic recommendations and should be used together with adviser consultation, student interests, and academic performance.',

            'source' => 'ollama',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $answerRows
     * @return array<string, mixed>
     */
    private function fallbackRecommendation(array $answerRows, string $error = ''): array
    {
        $programs = $this->programCatalog();

        $code = $this->fallbackProgramCode($answerRows);

        return [
            'recommended_program' => [
                'code' => $code,
                'name' => $programs[$code],
            ],

            'confidence' => 68,

            'fit_level' => 'Preliminary Fit',

            'reason' => 'The recommendation was generated from assessment trait patterns while the AI service was unavailable.',

            'strengths' => [
                'Your responses show identifiable academic interests and preferences.',
                'The suggested program aligns with the strongest trait indicators.',
                'The recommendation can support initial academic exploration.',
            ],

            'development_areas' => [
                'Review the curriculum before making a final decision.',
                'Consult an academic adviser for further evaluation.',
            ],

            'feedback' => 'This is a fallback recommendation generated through trait-based assessment analysis.',

            'evidence' => $this->fallbackEvidence($answerRows),

            'next_steps' => [
                'Read the curriculum of '.$programs[$code].'.',
                'Explore careers related to '.$code.'.',
                'Discuss the recommendation with an adviser.',
            ],

            'alternative_programs' => $this->fallbackAlternatives($code),

            'disclaimer' => 'This assessment provides guidance-based academic recommendations and should be used together with adviser consultation, student interests, and academic performance.',

            'source' => 'fallback',

            'error' => $error,
        ];
    }

    /**
     * TRAIT-BASED FALLBACK RECOMMENDATION
     *
     * @param  array<int, array<string, mixed>>  $answerRows
     */
    private function fallbackProgramCode(array $answerRows): string
    {
        return (string) array_key_first(
            $this->scorePrograms($answerRows)
        );
    }

    /**
     * PROGRAM MATCHES
     *
     * Ranks every catalog program against the submitted answers using the
     * deterministic trait scorer and normalizes each raw score into an
     * absolute compatibility percentage (0-99).
     *
     * Because every program profile's trait weights sum to exactly 1.0, the
     * theoretical maximum raw score for n answered items is n * 5, which
     * keeps percentages comparable across students and retakes.
     *
     * @param  array<int, array<string, mixed>>  $answerRows
     * @return array<int, array<string, mixed>>
     */
    public function programMatches(array $answerRows): array
    {
        if ($answerRows === []) {
            return [];
        }

        $details = $this->programDetails();

        $scores = $this->scorePrograms($answerRows);

        $maxScore = max(1, count($answerRows) * 5);

        return collect($scores)
            ->map(function ($score, string $code) use ($details, $maxScore): array {
                $detail = $details[$code] ?? [];

                return [
                    'code' => $code,

                    'name' => $detail['name']
                        ?? $this->programCatalog()[$code]
                        ?? $code,

                    'description' => $detail['description'] ?? '',

                    'careers' => $detail['careers'] ?? [],

                    'compatibility' => min(99, max(1, (int) round(($score / $maxScore) * 100))),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * SCORE PROGRAMS
     *
     * Deterministic trait-based scoring shared by the fallback recommender
     * and the results page compatibility chart. Returns raw scores ordered
     * from highest to lowest.
     *
     * @param  array<int, array<string, mixed>>  $answerRows
     * @return array<string, float>
     */
    private function scorePrograms(array $answerRows): array
    {
        $traitScores = [
            'analytical' => 0,
            'technical' => 0,
            'social' => 0,
            'organizational' => 0,
            'practical' => 0,
        ];

        foreach ($answerRows as $row) {
            $answer = (float) ($row['answer'] ?? 3);

            $question = mb_strtolower(
                (string) ($row['question'] ?? '')
            );

            $matched = false;

            if (
                str_contains($question, 'logical') ||
                str_contains($question, 'problem solving') ||
                str_contains($question, 'analysis') ||
                str_contains($question, 'critical thinking')
            ) {
                $traitScores['analytical'] += $answer;
                $matched = true;
            }

            if (
                str_contains($question, 'technology') ||
                str_contains($question, 'computer') ||
                str_contains($question, 'software') ||
                str_contains($question, 'system')
            ) {
                $traitScores['technical'] += $answer;
                $matched = true;
            }

            if (
                str_contains($question, 'communication') ||
                str_contains($question, 'team') ||
                str_contains($question, 'helping') ||
                str_contains($question, 'interacting')
            ) {
                $traitScores['social'] += $answer;
                $matched = true;
            }

            if (
                str_contains($question, 'detail') ||
                str_contains($question, 'organizing') ||
                str_contains($question, 'planning') ||
                str_contains($question, 'business')
            ) {
                $traitScores['organizational'] += $answer;
                $matched = true;
            }

            if (
                str_contains($question, 'hands-on') ||
                str_contains($question, 'practical') ||
                str_contains($question, 'applied') ||
                str_contains($question, 'real-world')
            ) {
                $traitScores['practical'] += $answer;
                $matched = true;
            }

            // Rewording a question must never silently remove its weight
            // from the fallback score. When no keyword matches, fall back
            // to a stable per-category distribution instead.
            if (! $matched) {
                foreach ($this->categoryTraitDefaults()[$row['category'] ?? ''] ?? [] as $trait => $weight) {
                    $traitScores[$trait] += $answer * $weight;
                }
            }
        }

        $programScores = [];

        foreach ($this->programProfiles() as $program => $weights) {
            $score = 0;

            foreach ($weights as $trait => $weight) {
                $score += ($traitScores[$trait] ?? 0) * $weight;
            }

            $programScores[$program] = round($score, 2);
        }

        arsort($programScores);

        return $programScores;
    }

    /**
     * PROGRAM PROFILES
     *
     * Trait weight distributions per program. Each row sums to exactly 1.0
     * so raw scores remain bounded by count(answers) * 5.
     *
     * @return array<string, array<string, float>>
     */
    private function programProfiles(): array
    {
        return [
            'BSCS' => [
                'analytical' => 0.40,
                'technical' => 0.35,
                'organizational' => 0.10,
                'practical' => 0.10,
                'social' => 0.05,
            ],

            'BSIT' => [
                'technical' => 0.35,
                'practical' => 0.30,
                'analytical' => 0.20,
                'organizational' => 0.10,
                'social' => 0.05,
            ],

            'BSAIS' => [
                'organizational' => 0.35,
                'analytical' => 0.30,
                'technical' => 0.20,
                'practical' => 0.10,
                'social' => 0.05,
            ],

            'BSHM' => [
                'social' => 0.40,
                'organizational' => 0.25,
                'practical' => 0.20,
                'technical' => 0.05,
                'analytical' => 0.10,
            ],

            'BSTM' => [
                'social' => 0.40,
                'organizational' => 0.20,
                'practical' => 0.20,
                'analytical' => 0.10,
                'technical' => 0.10,
            ],

            'ACT' => [
                'technical' => 0.30,
                'practical' => 0.30,
                'analytical' => 0.20,
                'organizational' => 0.10,
                'social' => 0.10,
            ],
        ];
    }

    /**
     * CATEGORY TRAIT DEFAULTS
     *
     * Stable per-category weight distributions used by the deterministic
     * fallback when a question's text matches no trait keyword (e.g., after
     * rewording). Values items are intentionally excluded: career-value
     * preferences are not trait evidence.
     *
     * @return array<string, array<string, float>>
     */
    private function categoryTraitDefaults(): array
    {
        return [
            'technical' => [
                'technical' => 1.0,
            ],

            'skills' => [
                'analytical' => 0.6,
                'social' => 0.4,
            ],

            'interest' => [
                'technical' => 0.25,
                'organizational' => 0.25,
                'social' => 0.25,
                'practical' => 0.25,
            ],

            'personality' => [
                'analytical' => 0.25,
                'social' => 0.25,
                'organizational' => 0.25,
                'practical' => 0.25,
            ],

            'work_style' => [
                'practical' => 0.4,
                'organizational' => 0.3,
                'social' => 0.3,
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $answerRows
     * @return array<int, string>
     */
    private function fallbackEvidence(array $answerRows): array
    {
        return collect($answerRows)
            ->sortByDesc(fn (array $row) => (int) ($row['answer'] ?? 0))
            ->take(3)
            ->map(
                fn (array $row) => ($row['question'] ?? 'Assessment item')
                    .' - '
                    .($row['answer_label'] ?? $row['answer'] ?? 'Answered')
            )
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function fallbackAlternatives(string $recommendedCode): array
    {
        $programs = $this->programCatalog();

        return collect($programs)
            ->reject(fn (string $name, string $code) => $code === $recommendedCode)
            ->take(2)
            ->map(fn (string $name, string $code) => [
                'code' => $code,
                'name' => $name,
                'reason' => 'This program also shares related academic traits.',
            ])
            ->values()
            ->all();
    }

    private function extractProgramCode(string $value): string
    {
        foreach (array_keys($this->programCatalog()) as $code) {
            if (str_contains(strtoupper($value), $code)) {
                return $code;
            }
        }

        return '';
    }

    /**
     * @return array<string, string>
     */
    private function programCatalog(): array
    {
        return [
            'BSCS' => 'Bachelor of Science in Computer Science',
            'BSIT' => 'Bachelor of Science in Information Technology',
            'BSAIS' => 'Bachelor of Science in Accounting Information System',
            'BSHM' => 'Bachelor of Science in Hospitality Management',
            'BSTM' => 'Bachelor of Science in Tourism Management',
            'ACT' => 'Associate in Computer Technology',
        ];
    }

    /**
     * PROGRAM DETAILS
     *
     * Static, CHED-recognized program blurbs used by the results page
     * compatibility cards. Purely informational content.
     *
     * @return array<string, array{name: string, description: string, careers: array<int, string>}>
     */
    public function programDetails(): array
    {
        return [
            'BSCS' => [
                'name' => 'Bachelor of Science in Computer Science',
                'description' => 'Focuses on computing theory, algorithms, software engineering, and intelligent systems.',
                'careers' => [
                    'Software Engineer',
                    'Systems Analyst',
                    'AI/ML Developer',
                    'Full Stack Web Developer',
                ],
            ],

            'BSIT' => [
                'name' => 'Bachelor of Science in Information Technology',
                'description' => 'Emphasizes practical network infrastructure, cybersecurity, and enterprise systems deployment.',
                'careers' => [
                    'Network Administrator',
                    'Cybersecurity Analyst',
                    'DevOps Specialist',
                    'IT Project Manager',
                ],
            ],

            'BSAIS' => [
                'name' => 'Bachelor of Science in Accounting Information System',
                'description' => 'Bridges accounting fundamentals and information systems for business data integrity and reporting.',
                'careers' => [
                    'Accounting Systems Analyst',
                    'Auditor',
                    'Financial Systems Consultant',
                    'ERP Administrator',
                ],
            ],

            'BSHM' => [
                'name' => 'Bachelor of Science in Hospitality Management',
                'description' => 'Trains students in hotel, restaurant, and resort operations with strong service leadership focus.',
                'careers' => [
                    'Hotel Operations Manager',
                    'F&B Supervisor',
                    'Events Coordinator',
                    'Guest Relations Officer',
                ],
            ],

            'BSTM' => [
                'name' => 'Bachelor of Science in Tourism Management',
                'description' => 'Prepares students for travel, tour operations, and destination management careers.',
                'careers' => [
                    'Tour Operations Specialist',
                    'Travel Consultant',
                    'Airline Cabin Crew',
                    'Destination Marketing Officer',
                ],
            ],

            'ACT' => [
                'name' => 'Associate in Computer Technology',
                'description' => 'A two-year hands-on pathway into computer support, networking, and entry-level development roles.',
                'careers' => [
                    'IT Support Specialist',
                    'Junior Web Developer',
                    'Computer Technician',
                    'Helpdesk Associate',
                ],
            ],
        ];
    }

    /**
     * @param  array<int, mixed>  $fallback
     * @return array<int, string>
     */
    private function stringList(mixed $value, int $limit, array $fallback): array
    {
        if (! is_array($value)) {
            return array_slice($fallback, 0, $limit);
        }

        $items = collect($value)
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->take($limit)
            ->values()
            ->all();

        return $items
            ?: array_slice($fallback, 0, $limit);
    }

    private function clampConfidence(mixed $value): int
    {
        $confidence = is_numeric($value)
            ? (int) round((float) $value)
            : 78;

        return max(0, min(100, $confidence));
    }
}
