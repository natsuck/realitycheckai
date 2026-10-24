<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\AssessmentResult;
use App\Models\Question;
use App\Services\AssessmentScoringService;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AssessmentController extends Controller
{
    /**
     * Minimum plausible completion time in seconds. Answering 20+ Likert
     * items honestly takes longer than this; anything faster suggests the
     * form was clicked through without reading.
     */
    private const RAPID_COMPLETION_SECONDS = 10;

    public function show()
    {
        if (! Auth::user()?->privacy_consented_at) {
            return redirect()->route('assessment.consent');
        }

        // Used by submit() to compute a rough completion duration for the
        // rapid-response validity check.
        if (! session()->has('assessment_started_at')) {
            session(['assessment_started_at' => now()->toDateTimeString()]);
        }

        // Shuffle once, then remember the order for the rest of the session
        // so refreshes and back-navigation never reshuffle the questionnaire.
        // The view renders a friendly "no questions available" card when empty
        // instead of bouncing the user back.
        $instrumentQuestions = Question::query()
            ->where('instrument_version', Question::CURRENT_INSTRUMENT_VERSION)
            ->orderBy('item_number')
            ->get();
        $isVersionedInstrument = $instrumentQuestions->isNotEmpty();
        $allQuestions = ($isVersionedInstrument
            ? $instrumentQuestions
            : $this->legacyQuestions())
            ->keyBy('id');

        $questionIds = session('assessment_question_ids');

        $orderIsUsable = ! $isVersionedInstrument
            && is_array($questionIds)
            && count($questionIds) === $allQuestions->count()
            && array_diff($questionIds, $allQuestions->keys()->all()) === [];

        if ($isVersionedInstrument && $allQuestions->isNotEmpty()) {
            $questionIds = $allQuestions->keys()->all();
            session(['assessment_question_ids' => $questionIds]);
        } elseif (! $orderIsUsable && $allQuestions->isNotEmpty()) {
            $questionIds = $allQuestions->keys()->all();
            shuffle($questionIds); // reduces order bias

            session(['assessment_question_ids' => $questionIds]);
        }

        $questions = collect($questionIds ?? [])
            ->map(fn ($id) => $allQuestions[$id])
            ->values();

        $categoryLabels = $this->categoryLabels();
        $sectionLabels = $this->sectionLabels();
        $likertLabels = $this->scaleLabels();
        $interestScaleLabels = [
            1 => 'Strongly Dislike',
            2 => 'Dislike',
            3 => 'Neutral',
            4 => 'Like',
            5 => 'Strongly Like',
        ];
        $wizardQuestions = $questions->map(function (Question $question) use ($categoryLabels, $sectionLabels, $likertLabels, $interestScaleLabels): array {
            $isMultipleChoice = $question->question_type === 'multiple_choice';
            $scaleLabels = $question->instrument_version ? $interestScaleLabels : $likertLabels;
            $options = $isMultipleChoice
                ? collect($question->choices ?? [])
                    ->values()
                    ->map(fn (array $choice, int $index): array => [
                        'value' => $index + 1,
                        'label' => $choice['label'] ?? chr(65 + $index),
                        'text' => $choice['text'] ?? '',
                    ])
                    ->all()
                : collect($scaleLabels)
                    ->map(fn (string $label, int $value): array => [
                        'value' => $value,
                        'label' => (string) $value,
                        'text' => $label,
                    ])
                    ->values()
                    ->all();

            return [
                'id' => $question->id,
                'text' => $question->question_text,
                'label' => $categoryLabels[$question->category] ?? ucfirst((string) $question->category),
                'section' => $sectionLabels[$question->section] ?? '',
                'type' => $isMultipleChoice ? 'multiple_choice' : 'likert',
                'options' => $options,
            ];
        })->all();

        return view('assessment.assessment', compact(
            'questions',
            'categoryLabels',
            'wizardQuestions'
        ));
    }

    public function consent()
    {
        return view('assessment.consent');
    }

    public function storeConsent(Request $request)
    {
        $validated = $request->validate([
            'privacy_consent' => ['accepted'],
        ]);

        $request->user()->update([
            'privacy_consented_at' => now(),
        ]);

        return redirect()
            ->route('assessment.show')
            ->with('status', 'Thank you. Your consent has been recorded and you may now take the assessment.');
    }

    public function submit(Request $request, OpenAIService $openai)
    {
        if (! Auth::user()?->privacy_consented_at) {
            return redirect()
                ->route('assessment.consent')
                ->withErrors([
                    'consent' => 'Please review and accept the data privacy notice before taking the assessment.',
                ]);
        }

        $instrumentQuestions = Question::query()
            ->where('instrument_version', Question::CURRENT_INSTRUMENT_VERSION)
            ->orderBy('item_number')
            ->get();
        $isVersionedInstrument = $instrumentQuestions->isNotEmpty();
        $questions = $isVersionedInstrument
            ? $instrumentQuestions
            : $this->legacyQuestions();

        if ($questions->isEmpty()) {
            return back()->withErrors([
                'assessment' => 'Assessment questions are unavailable.',
            ]);
        }

        $rules = [];
        $attributes = [];

        foreach ($questions as $question) {
            $field = 'question_'.$question->id;
            $rules[$field] = [
                $isVersionedInstrument ? 'required' : 'nullable',
                'integer',
                $question->question_type === 'multiple_choice'
                    ? 'between:1,4'
                    : 'between:1,5',
            ];
            $attributes[$field] = $isVersionedInstrument
                ? 'question '.$question->item_number
                : 'assessment question';
        }

        $validated = $request->validate($rules, [], $attributes);

        if ($isVersionedInstrument) {
            return $this->submitVersionedAssessment(
                $request,
                $openai,
                app(AssessmentScoringService::class),
                $questions,
                $validated
            );
        }

        $answersArray = [];

        foreach ($questions as $question) {
            $key = 'question_'.$question->id;

            // Questions left unanswered (blank or missing from the payload)
            // are omitted; the analysis and summary use whichever items were
            // actually answered.
            if (! $request->filled($key)) {
                continue;
            }

            $answer = (int) $validated[$key];

            // Negatively-worded items are flagged in the database so the
            // scale is inverted before scoring (6 - value keeps the 1..5 range).
            if ($question->is_reverse) {
                $answer = 6 - $answer;
            }

            Answer::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'question_id' => $question->id,
                ],
                [
                    'answer' => $answer,
                ]
            );

            $answersArray[] = [
                'question' => $question->question_text,

                'category' => $question->category,

                'category_label' => $this->categoryLabels()[$question->category]
                    ?? ucfirst((string) $question->category),

                'answer' => $answer,

                'answer_label' => $this->scaleLabels()[$answer],
            ];
        }

        if ($answersArray === []) {
            return back()->withErrors([
                'assessment' => 'Please answer at least one question before submitting the assessment.',
            ]);
        }

        $answerJson = json_encode(
            $answersArray,
            JSON_PRETTY_PRINT
        );

        $aiFeedback = $openai->analyze($answerJson);

        $summary = $this->summarizeAssessment(
            collect($answersArray)
        );

        $strongestTrait = $this->strongestTrait($summary);

        $completionSeconds = $this->completionSeconds();
        $responseFlags = $this->detectResponseFlags(
            collect($answersArray),
            $completionSeconds
        );

        AssessmentResult::query()->create([
            'user_id' => Auth::id(),
            'program_code' => $aiFeedback['recommended_program']['code'] ?? 'N/A',
            'program_name' => $aiFeedback['recommended_program']['name'] ?? 'No recommendation',
            'confidence' => (float) ($aiFeedback['confidence'] ?? 0),
            'fit_level' => $aiFeedback['fit_level'] ?? null,
            'source' => $aiFeedback['source'] ?? 'openai',
            'strongest_trait' => $strongestTrait['trait'] ?? null,
            'strongest_trait_percentage' => (float) ($strongestTrait['percentage'] ?? 0),
            'response_flags' => $responseFlags ?: null,
            'completion_seconds' => $completionSeconds,
            'result' => $aiFeedback,
            'category_summary' => $summary,
            'completed_at' => now(),
        ]);

        session([
            'ai_feedback' => $aiFeedback,

            'assessment_summary' => $summary,

            'assessment_disclaimer' => 'This assessment is intended for academic guidance purposes only and should not be treated as a definitive basis for enrollment decisions.',

            'assessment_completed_at' => now()->toDateTimeString(),
        ]);

        // One-request marker consumed by the results page celebration.
        session()->flash('just_completed', true);

        // A fresh shuffle (and clean client-side progress) for the next take.
        session()->forget('assessment_question_ids');

        return redirect()->route('results.index');
    }

    public function results(Request $request, OpenAIService $openai)
    {
        $latestResult = Auth::user()->assessmentResults()->first();
        $answerQuery = Answer::query()
            ->with('question')
            ->where('user_id', Auth::id());

        if ($latestResult?->instrument_version) {
            $answerQuery->where('assessment_result_id', $latestResult->id);
        } else {
            $answerQuery->whereNull('assessment_result_id');
        }

        $answers = $answerQuery->get()
            ->sortBy(fn (Answer $answer) => $answer->question?->id ?? 0)
            ->values();

        /*
        |--------------------------------------------------------------------------
        | PERSISTED RESULT (SOURCE OF TRUTH)
        |--------------------------------------------------------------------------
        | Every submission is stored as an assessment_results row so results
        | survive session expiry and retake history is preserved. The session
        | values are only used as a legacy fallback for pre-existing sessions.
        |--------------------------------------------------------------------------
        */

        if ($latestResult) {
            $aiFeedback = $latestResult->result ?? [];

            $summary = $latestResult->category_summary;

            $disclaimer = $aiFeedback['disclaimer']
                ?? 'This assessment is intended only for academic guidance purposes.';

            $completedAt = $latestResult->completed_at?->toDateTimeString();
        } else {
            $aiFeedback = session('ai_feedback', []);

            if (is_string($aiFeedback)) {
                $aiFeedback = [
                    'recommended_program' => [
                        'code' => 'AI',
                        'name' => 'AI Recommendation',
                    ],

                    'confidence' => null,

                    'fit_level' => 'Generated',

                    'reason' => $aiFeedback,

                    'strengths' => [],

                    'development_areas' => [],

                    'feedback' => $aiFeedback,

                    'evidence' => [],

                    'next_steps' => [],

                    'alternative_programs' => [],

                    'disclaimer' => 'This recommendation is guidance-based and should not be treated as a final enrollment decision.',
                ];
            }

            $summary = session('assessment_summary');

            /*
            |--------------------------------------------------------------------------
            | RECOVER SUMMARY IF SESSION EXPIRED
            |--------------------------------------------------------------------------
            */

            if (! $summary && $answers->isNotEmpty()) {

                $summary = $this->summarizeAssessment(
                    $answers->map(fn (Answer $answer) => [
                        'category' => $answer->question?->category ?? 'uncategorized',

                        'category_label' => $this->categoryLabels()[$answer->question?->category]
                            ?? 'Uncategorized',

                        'answer' => (int) $answer->answer,
                    ])
                );
            }

            $disclaimer = session(
                'assessment_disclaimer',
                'This assessment is intended only for academic guidance purposes.'
            );

            $completedAt = session('assessment_completed_at');
        }

        $history = Auth::user()->assessmentResults()
            ->skip(1)
            ->take(5)
            ->get();

        $responseFlags = $latestResult?->response_flags ?? [];

        $isVersionedResult = (bool) $latestResult?->instrument_version;
        $programMatches = $isVersionedResult
            ? []
            : $this->programMatches($openai, $answers, $aiFeedback);

        $aptitude = $isVersionedResult
            ? null
            : $this->aptitudeScore($summary ?? [], $latestResult?->strongest_trait);
        $scoreBreakdown = $latestResult?->score_breakdown;

        $strongestTrait = $latestResult?->strongest_trait;

        return view('assessment.results', compact(
            'answers',
            'aiFeedback',
            'summary',
            'disclaimer',
            'completedAt',
            'history',
            'responseFlags',
            'programMatches',
            'aptitude',
            'strongestTrait', 'scoreBreakdown',
            'isVersionedResult'
        ));
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @param  array<string, mixed>  $validated
     */
    private function submitVersionedAssessment(
        Request $request,
        OpenAIService $openai,
        AssessmentScoringService $scoring,
        Collection $questions,
        array $validated
    ) {
        $responses = $questions->map(function (Question $question) use ($validated): array {
            return [
                'question' => $question,
                'answer' => (int) $validated['question_'.$question->id],
            ];
        });

        $scoreBreakdown = $scoring->score($responses);
        $summary = $scoring->categorySummary($scoreBreakdown);
        $profile = $openai->analyzeAssessmentProfile($scoreBreakdown);
        $topInterest = collect($scoreBreakdown['interest'])->sortByDesc('average')->first();
        $interestResponses = $responses
            ->filter(fn (array $response) => $response['question']->section === 'interest')
            ->map(fn (array $response): array => ['answer' => $response['answer']]);
        $responseFlags = $this->detectResponseFlags($interestResponses, null);
        $completionSeconds = $this->completionSeconds();

        DB::transaction(function () use ($profile, $topInterest, $responseFlags, $completionSeconds, $scoreBreakdown, $summary, $responses): void {
            $result = AssessmentResult::query()->create([
                'user_id' => Auth::id(),
                'program_code' => $profile['recommended_program']['code'] ?? 'PENDING',
                'program_name' => $profile['recommended_program']['name'] ?? 'AI match unavailable',
                'confidence' => 0,
                'fit_level' => 'Profile only',
                'source' => $profile['source'] ?? 'openai',
                'strongest_trait' => $topInterest['label'] ?? null,
                'strongest_trait_percentage' => $topInterest['percentage'] ?? 0,
                'response_flags' => $responseFlags ?: null,
                'completion_seconds' => $completionSeconds,
                'result' => $profile,
                'category_summary' => $summary,
                'score_breakdown' => $scoreBreakdown,
                'instrument_version' => Question::CURRENT_INSTRUMENT_VERSION,
                'completed_at' => now(),
            ]);

            foreach ($responses as $response) {
                /** @var Question $question */
                $question = $response['question'];

                Answer::query()->create([
                    'user_id' => Auth::id(),
                    'question_id' => $question->id,
                    'assessment_result_id' => $result->id,
                    'answer' => $response['answer'],
                    'is_correct' => $question->question_type === 'multiple_choice'
                        ? $response['answer'] === $question->correct_answer
                        : null,
                ]);
            }
        });

        session([
            'ai_feedback' => $profile,
            'assessment_summary' => $summary,
            'assessment_disclaimer' => $profile['disclaimer'],
            'assessment_completed_at' => now()->toDateTimeString(),
        ]);
        session()->flash('just_completed', true);
        session()->forget(['assessment_question_ids', 'assessment_started_at']);

        return redirect()->route('results.index');
    }

    /**
     * Keep legacy questionnaire fixtures available to existing tests while
     * preventing an unseeded production install from silently serving the
     * retired instrument.
     *
     * @return Collection<int, Question>
     */
    private function legacyQuestions(): Collection
    {
        if (! app()->environment('testing')) {
            return collect();
        }

        return Question::query()
            ->whereNull('instrument_version')
            ->orderBy('id')
            ->get();
    }

    /**
     * PROGRAM MATCHES
     *
     * Ranks the full program catalog against the stored answers using the
     * deterministic trait scorer. When a valid AI recommendation exists it
     * is pinned to the top and flagged; the remaining programs follow in
     * descending compatibility order.
     *
     * @param  \Illuminate\Support\Collection<int, Answer>  $answers
     * @param  array<string, mixed>  $aiFeedback
     * @return array<int, array<string, mixed>>
     */
    private function programMatches(OpenAIService $openai, Collection $answers, array $aiFeedback): array
    {
        $rows = $answers
            ->map(fn (Answer $answer): array => [
                'question' => $answer->question?->question_text ?? '',

                'category' => $answer->question?->category ?? '',

                'answer' => (int) $answer->answer,
            ])
            ->all();

        if ($rows === []) {
            return [];
        }

        $matches = $openai->programMatches($rows);

        $recommendedCode = strtoupper(
            (string) ($aiFeedback['recommended_program']['code'] ?? '')
        );

        return collect($matches)
            ->sortByDesc(function (array $match) use ($recommendedCode) {
                // Pin a valid AI recommendation above everything else so
                // the chart's top match always matches the recommendation.
                if ($match['code'] === $recommendedCode) {
                    return PHP_FLOAT_MAX;
                }

                return $match['compatibility'];
            })
            ->values()
            ->map(fn (array $match, int $index) => $match + [
                'is_top_match' => $index === 0,
                'is_ai_pick' => $match['code'] === $recommendedCode,
            ])
            ->all();
    }

    /**
     * APTITUDE SCORE
     *
     * Weighted composite of category percentages. Values are excluded from
     * the composite: career-value preferences are not trait evidence (see
     * docs/assessment-methodology.md). Weights renormalize over whichever
     * trait categories exist so legacy summaries still produce a score.
     *
     * @param  array<int, array<string, mixed>>  $summary
     * @return array{score: int, ranking: string}|null
     */
    private function aptitudeScore(array $summary, ?string $strongestTrait): ?array
    {
        $score = self::computeAptitude($summary);

        if ($score === null) {
            return null;
        }

        return [
            'score' => $score,

            'ranking' => sprintf(
                'Top %d%% in %s Alignment',
                max(5, 100 - $score),
                $strongestTrait ?? 'Overall Academic'
            ),
        ];
    }

    /**
     * COMPUTE APTITUDE
     *
     * Shared numeric composite used by the student results page and the
     * admin submissions table.
     *
     * @param  array<int, array<string, mixed>>|mixed  $summary
     */
    public static function computeAptitude(mixed $summary): ?int
    {
        if (! is_array($summary) || $summary === []) {
            return null;
        }

        $weights = [
            'technical' => 0.30,
            'skills' => 0.25,
            'interest' => 0.20,
            'personality' => 0.15,
            'work_style' => 0.10,
        ];

        $weightedTotal = 0;
        $weightSum = 0;

        foreach ($summary as $row) {
            $weight = $weights[$row['category'] ?? ''] ?? 0;

            if ($weight <= 0) {
                continue;
            }

            $weightedTotal += (float) ($row['percentage'] ?? 0) * $weight;
            $weightSum += $weight;
        }

        if ($weightSum <= 0) {
            return null;
        }

        return max(0, min(99, (int) round($weightedTotal / $weightSum)));
    }

    /**
     * COMPLETION SECONDS
     *
     * Rough duration between opening the assessment and submitting it.
     * Returns null when the start marker is unavailable (e.g., legacy session).
     */
    private function completionSeconds(): ?int
    {
        $startedAt = session('assessment_started_at');

        if (! $startedAt) {
            return null;
        }

        session()->forget('assessment_started_at');

        return max(0, now()->getTimestamp() - strtotime((string) $startedAt));
    }

    /**
     * RESPONSE VALIDITY CHECKS
     *
     * Flags response patterns that reduce confidence in the recommendation:
     * - uniform/near-uniform answers (straight-lining)
     * - implausibly fast completion
     *
     * @param  Collection<int, array<string, mixed>>  $answers
     * @return array<int, string>
     */
    private function detectResponseFlags(Collection $answers, ?int $completionSeconds): array
    {
        $flags = [];

        if ($answers->isNotEmpty()) {
            $values = $answers->pluck('answer');
            $counts = $values->countBy();
            $dominantShare = $counts->max() / $values->count();

            if ($counts->count() === 1) {
                $flags[] = 'uniform_responses';
            } elseif ($dominantShare >= 0.9) {
                $flags[] = 'near_uniform_responses';
            }
        }

        if ($completionSeconds !== null && $completionSeconds < self::RAPID_COMPLETION_SECONDS) {
            $flags[] = 'rapid_completion';
        }

        return $flags;
    }

    /**
     * RESPONSE FLAG LABELS
     *
     * @return array<string, string>
     */
    public static function responseFlagLabels(): array
    {
        return [
            'uniform_responses' => 'All answers were identical.',
            'near_uniform_responses' => 'Nearly all answers had the same value.',
            'rapid_completion' => 'The assessment was completed unusually fast.',
        ];
    }

    /**
     * CATEGORY SUMMARY
     *
     * @param  Collection<int, array<string, mixed>>  $answers
     * @return array<int, array<string, mixed>>
     */
    private function summarizeAssessment(Collection $answers): array
    {
        return $answers
            ->groupBy('category')
            ->map(function (Collection $group, string $category): array {

                $average = round(
                    (float) $group->avg('answer'),
                    2
                );

                return [
                    'category' => $category,

                    'label' => $group->first()['category_label']
                        ?? $this->categoryLabels()[$category]
                        ?? ucfirst($category),

                    'average' => $average,

                    'percentage' => round(
                        ($average / 5) * 100,
                        2
                    ),

                    'level' => match (true) {
                        $average >= 4.2 => 'Very strong',
                        $average >= 3.4 => 'Strong',
                        $average >= 2.6 => 'Moderate',
                        default => 'Needs exploration',
                    },

                    'count' => $group->count(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * STRONGEST TRAIT
     *
     * Determines the dominant assessment trait from the category summary.
     * The category with the highest percentage wins; ties break on the
     * number of items backing the category, then alphabetically.
     *
     * @param  array<int, array<string, mixed>>  $summary
     * @return array{trait: string, percentage: float}|null
     */
    private function strongestTrait(array $summary): ?array
    {
        if ($summary === []) {
            return null;
        }

        $top = collect($summary)
            ->sort(fn (array $a, array $b) => [$b['percentage'], $b['count'], $a['label']]
                <=> [$a['percentage'], $a['count'], $b['label']])
            ->first();

        return [
            'trait' => $top['label'],
            'percentage' => (float) $top['percentage'],
        ];
    }

    /**
     * CATEGORY LABELS
     *
     * @return array<string, string>
     */
    private function categoryLabels(): array
    {
        return [
            'skills' => 'Skills',

            'interest' => 'Interests',

            'personality' => 'Personality',

            'work_style' => 'Work Style',

            'values' => 'Career Values',

            'technical' => 'Technical Aptitude',

            'R' => 'Realistic',
            'I' => 'Investigative',
            'A' => 'Artistic',
            'S' => 'Social',
            'E' => 'Enterprising',
            'C' => 'Conventional',
            'verbal' => 'Verbal Reasoning',
            'quantitative' => 'Quantitative Reasoning',
            'analytical' => 'Analytical and Scientific Reasoning',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function sectionLabels(): array
    {
        return [
            'interest' => 'Career Interest Inventory',
            'verbal' => 'Verbal Reasoning and Reading Comprehension',
            'quantitative' => 'Quantitative Reasoning',
            'analytical' => 'Analytical and Scientific Reasoning',
        ];
    }

    /**
     * LIKERT SCALE LABELS
     *
     * @return array<int, string>
     */
    private function scaleLabels(): array
    {
        return [
            1 => 'Strongly disagree',
            2 => 'Disagree',
            3 => 'Neutral',
            4 => 'Agree',
            5 => 'Strongly agree',
        ];
    }
}
