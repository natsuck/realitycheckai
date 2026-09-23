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
use Illuminate\View\View;

class QuizAttemptController extends Controller
{
    public function create(Quiz $quiz): View
    {
        $this->authorizeQuizOwner($quiz);
        abort_unless($quiz->isReady(), 409, 'Quiz is still being generated.');

        $quiz->load(['questions' => fn ($query) => $query->orderBy('id')]);

        // Roughly 90 seconds per question, never shorter than 10 minutes.
        $timeLimitMinutes = max(10, (int) ceil($quiz->questions->count() * 90 / 60));

        return view('quiz_attempts.create', compact('quiz', 'timeLimitMinutes'));
    }

    public function store(Request $request, Quiz $quiz, AnswerEvaluationService $evaluator): RedirectResponse
    {
        $this->authorizeQuizOwner($quiz);
        abort_unless($quiz->isReady(), 409, 'Quiz is still being generated.');

        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable', 'string', 'max:2000'],
        ]);

        $questions = $quiz->questions()->orderBy('id')->get();
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
                    ? 'MCQ answer matched the answer key.'
                    : 'MCQ answer did not match the answer key.';
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

        $attempt = DB::transaction(function () use ($quiz, $score, $totalQuestions, $correctCount, $mcqScore, $identificationScore, $averageAiConfidence, $answerRows): QuizAttempt {
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
                'started_at' => now(),
                'completed_at' => now(),
            ]);

            foreach ($answerRows as $row) {
                $attempt->answers()->create($row);
            }

            return $attempt;
        });

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

        return $base.' MCQ correct: '.$mcqScore.'/'.$mcqTotal.
            '. Identification correct: '.$identificationScore.'/'.$identificationTotal.'.';
    }
}
