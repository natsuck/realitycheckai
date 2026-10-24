<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class AssessmentScoringService
{
    private const RIASEC_CODES = ['R', 'I', 'A', 'S', 'E', 'C'];

    private const APTITUDE_SECTIONS = [
        'verbal' => 'Verbal Reasoning and Reading Comprehension',
        'quantitative' => 'Quantitative Reasoning',
        'analytical' => 'Analytical and Scientific Reasoning',
    ];

    /**
     * @param  Collection<int, array{question: Question, answer: int}>  $responses
     * @return array<string, mixed>
     */
    public function score(Collection $responses): array
    {
        if ($responses->count() !== 150) {
            throw new InvalidArgumentException('The approved assessment requires exactly 150 responses.');
        }

        $interestRows = $responses->filter(
            fn (array $response) => $response['question']->section === 'interest'
        );
        $aptitudeRows = $responses->filter(
            fn (array $response) => $response['question']->section !== 'interest'
        );

        if ($interestRows->count() !== 90 || $aptitudeRows->count() !== 60) {
            throw new InvalidArgumentException('The assessment must contain 90 interest and 60 aptitude responses.');
        }

        $riasec = [];

        foreach (self::RIASEC_CODES as $code) {
            $items = $interestRows->filter(
                fn (array $response) => $response['question']->riasec_type === $code
            );

            if ($items->count() !== 15) {
                throw new InvalidArgumentException("RIASEC scale {$code} must contain exactly 15 items.");
            }

            $total = $items->sum(function (array $response): int {
                $answer = $response['answer'];

                return $response['question']->is_reverse ? 6 - $answer : $answer;
            });
            $average = round($total / $items->count(), 2);

            $riasec[$code] = [
                'label' => self::RIASEC_LABELS[$code],
                'items' => $items->count(),
                'total' => $total,
                'average' => $average,
                'percentage' => round(($average / 5) * 100, 2),
            ];
        }

        $aptitude = [];

        foreach (self::APTITUDE_SECTIONS as $section => $label) {
            $items = $aptitudeRows->filter(
                fn (array $response) => $response['question']->section === $section
            );

            if ($items->count() !== 20) {
                throw new InvalidArgumentException("Aptitude section {$section} must contain exactly 20 items.");
            }

            $correct = $items->filter(
                fn (array $response) => $response['answer'] === $response['question']->correct_answer
            )->count();

            $aptitude[$section] = [
                'label' => $label,
                'correct' => $correct,
                'total' => $items->count(),
                'percentage' => round(($correct / $items->count()) * 100, 2),
            ];
        }

        return [
            'instrument_version' => Question::CURRENT_INSTRUMENT_VERSION,
            'interest' => $riasec,
            'aptitude' => $aptitude,
        ];
    }

    /**
     * @param  array<string, mixed>  $scoreBreakdown
     * @return array<int, array<string, mixed>>
     */
    public function categorySummary(array $scoreBreakdown): array
    {
        return collect($scoreBreakdown['interest'] ?? [])
            ->map(function (array $score, string $code): array {
                return [
                    'category' => $code,
                    'label' => $score['label'],
                    'average' => $score['average'],
                    'percentage' => $score['percentage'],
                    'count' => $score['items'],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @var array<string, string>
     */
    private const RIASEC_LABELS = [
        'R' => 'Realistic',
        'I' => 'Investigative',
        'A' => 'Artistic',
        'S' => 'Social',
        'E' => 'Enterprising',
        'C' => 'Conventional',
    ];
}
