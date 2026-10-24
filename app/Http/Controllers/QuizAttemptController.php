<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Services\AnswerEvaluationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuizAttemptController extends Controller
{
    public function create(Quiz $quiz): View|RedirectResponse
    {
        $this->authorizeQuizOwner($quiz);

        if (! $quiz->isReady()) {
            return redirect()
                ->route('quizzes.show', $quiz)
                ->with('status', 'This quiz is still being generated. You can take it after generation is complete.');
        }

        $questions = $quiz->questions()->orderBy('id')->get();
        $hasCompletedAttempt = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', Auth::id())
            ->exists();

        if ($hasCompletedAttempt) {
            $orderKey = 'quiz_attempt_order_'.$quiz->id;
            $questionIds = $questions->modelKeys();
            $savedOrder = session($orderKey);

            if (! is_array($savedOrder)
                || count($savedOrder) !== count($questionIds)
                || array_diff($questionIds, $savedOrder) !== []
                || array_diff($savedOrder, $questionIds) !== []) {
                $savedOrder = $questions->shuffle()->modelKeys();
                session([$orderKey => $savedOrder]);
            }

            $questionsById = $questions->keyBy('id');
            $questions = collect($savedOrder)
                ->map(fn (int $questionId) => $questionsById->get($questionId))
                ->values();
        } else {
            session()->forget('quiz_attempt_order_'.$quiz->id);
        }

        $quiz->setRelation('questions', $questions);

        // Roughly 90 seconds per question, never shorter than 10 minutes.
        $timeLimitMinutes = max(10, (int) ceil($quiz->questions->count() * 90 / 60));
        $attemptStartKey = 'quiz_attempt_started_at_'.$quiz->id;
        if (! session()->has($attemptStartKey)) {
            session([$attemptStartKey => now()->timestamp]);
        }
        $secondsRemaining = max(
            0,
            ($timeLimitMinutes * 60) - (now()->timestamp - (int) session($attemptStartKey))
        );

        return view('quiz_attempts.create', compact('quiz', 'timeLimitMinutes', 'secondsRemaining'));
    }

    public function store(Request $request, Quiz $quiz, AnswerEvaluationService $evaluator): RedirectResponse
    {
        $this->authorizeQuizOwner($quiz);

        if (! $quiz->isReady()) {
            return redirect()
                ->route('quizzes.show', $quiz)
                ->with('status', 'This quiz is still being generated.');
        }

        $questions = $quiz->questions()->orderBy('id')->get();
        $attemptStartKey = 'quiz_attempt_started_at_'.$quiz->id;
        $startedAt = session($attemptStartKey);
        if (! is_numeric($startedAt) || (int) $startedAt > now()->timestamp) {
            session()->forget($attemptStartKey);

            return redirect()
                ->route('quizzes.take', $quiz)
                ->with('status', 'Start the quiz before submitting your answers.');
        }

        $timeLimitMinutes = max(10, (int) ceil($questions->count() * 90 / 60));
        $timedOut = now()->timestamp - (int) $startedAt >= $timeLimitMinutes * 60;
        $rules = [
            'answers' => [$timedOut ? 'sometimes' : 'required', 'array'],
            'answers.*' => ['nullable', 'string', 'max:2000'],
        ];

        foreach ($questions as $question) {
            $answerRules = [$timedOut ? 'nullable' : 'required', 'string', 'max:2000'];
            if ($question->isMcq()) {
                $answerRules[] = Rule::in($question->choices ?? []);
            }

            $rules['answers.'.$question->id] = $answerRules;
        }

        $validated = $request->validate($rules, [
            'answers.*.required' => 'Please answer every question before submitting.',
            'answers.*.in' => 'Please select one of the available choices.',
        ]);

        $answers = $validated['answers'] ?? [];
        $answerRows = [];
        $correctCount = 0;
        $mcqScore = 0;
        $identificationScore = 0;
        $aiConfidences = [];

        // All identification answers are graded in a single AI request
        // instead of one request per question; exact matches are resolved
        // deterministically and never reach the model.
        $identificationItems = $questions
            ->filter(fn (QuizQuestion $question) => ! $question->isMcq())
            ->map(fn (QuizQuestion $question): array => [
                'key' => $question->id,
                'question' => $question->question,
                'correct_answer' => $question->correct_answer,
                'student_answer' => trim((string) ($answers[$question->id] ?? '')),
            ])
            ->values()
            ->all();

        $evaluations = $evaluator->evaluateBatch($identificationItems);

        foreach ($questions as $question) {
            $userAnswer = trim((string) ($answers[$question->id] ?? ''));

            if ($question->isMcq()) {
                $isCorrect = $this->normalize($userAnswer) === $this->normalize($question->correct_answer);
                $explanation = $isCorrect
                    ? ($question->explanation ?: 'Your answer matched the answer key.')
                    : ($question->explanation ?: 'Your answer did not match the answer key.');
                $confidence = 100.0;

                if ($isCorrect) {
                    $mcqScore++;
                }
            } else {
                $evaluation = $evaluations[$question->id]
                    ?? $evaluator->evaluate($question->question, $question->correct_answer, $userAnswer);
                $isCorrect = $evaluation['is_correct'];
                $explanation = $evaluation['explanation'];
                $confidence = $evaluation['confidence'];
                $aiConfidences[] = $confidence;

                if ($isCorrect) {
                    $identificationScore++;
                }
            }

            if ($isCorrect) {
                $correctCount++;
            }

            $answerRows[] = [
                'question_id' => $question->id,
                'user_answer' => $userAnswer,
                'is_correct' => $isCorrect,
                'ai_explanation' => $explanation,
                'ai_confidence' => $confidence,
            ];
        }

        $totalQuestions = max($questions->count(), 1);
        $score = round(($correctCount / $totalQuestions) * 100, 2);
        $averageAiConfidence = count($aiConfidences) > 0
            ? round(array_sum($aiConfidences) / count($aiConfidences), 2)
            : 100.0;

        $attempt = DB::transaction(function () use ($quiz, $questions, $score, $totalQuestions, $correctCount, $mcqScore, $identificationScore, $averageAiConfidence, $answerRows, $startedAt): QuizAttempt {
            $attempt = QuizAttempt::query()->create([
                'user_id' => Auth::id(),
                'quiz_id' => $quiz->id,
                'score' => $score,
                'total_questions' => $totalQuestions,
                'correct_count' => $correctCount,
                'mcq_score' => $mcqScore,
                'identification_score' => $identificationScore,
                'ai_confidence' => $averageAiConfidence,
                'feedback' => $this->buildFeedback(
                    $score,
                    $mcqScore,
                    $identificationScore,
                    $questions->where('type', 'mcq')->count(),
                    $questions->count() - $questions->where('type', 'mcq')->count()
                ),
                'started_at' => now()->setTimestamp((int) $startedAt),
                'completed_at' => now(),
            ]);

            foreach ($answerRows as $row) {
                $attempt->answers()->create($row);
            }

            return $attempt;
        });

        session()->forget('quiz_attempt_order_'.$quiz->id);
        session()->forget($attemptStartKey);

        return redirect()->route('quiz-attempts.show', $attempt);
    }

    public function show(QuizAttempt $quizAttempt): View
    {
        abort_unless($quizAttempt->user_id === Auth::id(), 403);

        $quizAttempt->load([
            'quiz',
            'answers.question',
        ]);

        return view('quiz_attempts.show', ['attempt' => $quizAttempt]);
    }

    private function authorizeQuizOwner(Quiz $quiz): void
    {
        abort_unless($quiz->user_id === Auth::id(), 403);
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    private function buildFeedback(
        float $score,
        int $mcqScore,
        int $identificationScore,
        int $mcqTotal,
        int $identificationTotal
    ): string {
        $base = match (true) {
            $score >= 90 => 'Excellent mastery. You handled both recall and comprehension items with confidence.',
            $score >= 75 => 'Strong performance. Review the missed items to tighten a few concepts.',
            $score >= 60 => 'Developing understanding. Revisit the source material and focus on the questions marked incorrect.',
            default => 'Needs review. Study the key terms and main ideas from the uploaded material before retaking the quiz.',
        };

        $typeFeedback = ' MCQ correct: '.$mcqScore.'/'.$mcqTotal.'.';

        if ($identificationTotal > 0) {
            $typeFeedback .= ' Identification correct: '.$identificationScore.'/'.$identificationTotal.'.';
        }

        return $base.$typeFeedback;
    }
}
