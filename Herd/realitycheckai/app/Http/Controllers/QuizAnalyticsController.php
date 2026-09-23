<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QuizAnalyticsController extends Controller
{
    public function index(): View
    {
        $attempts = QuizAttempt::query()
            ->with('quiz')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        $recentAttempts = $attempts->take(10)->reverse()->values();
        $difficultyRows = QuizAnswer::query()
            ->join('quiz_questions', 'quiz_answers.question_id', '=', 'quiz_questions.id')
            ->join('quiz_attempts', 'quiz_answers.attempt_id', '=', 'quiz_attempts.id')
            ->where('quiz_attempts.user_id', Auth::id())
            ->selectRaw('quiz_questions.difficulty, COUNT(*) as total, SUM(CASE WHEN quiz_answers.is_correct = 1 THEN 1 ELSE 0 END) as correct')
            ->groupBy('quiz_questions.difficulty')
            ->get()
            ->keyBy('difficulty');

        $difficultyLabels = ['easy', 'medium', 'hard'];
        $difficultyAccuracy = collect($difficultyLabels)->map(function (string $difficulty) use ($difficultyRows): float {
            $row = $difficultyRows->get($difficulty);

            if (! $row || (int) $row->total === 0) {
                return 0.0;
            }

            return round(((int) $row->correct / (int) $row->total) * 100, 2);
        })->values();

        return view('quiz_analytics.index', [
            'totalQuizzes' => Quiz::query()->where('user_id', Auth::id())->count(),
            'readyQuizzes' => Quiz::query()->where('user_id', Auth::id())->where('status', Quiz::STATUS_READY)->count(),
            'completedAttempts' => $attempts->count(),
            'averageScore' => round((float) ($attempts->avg('score') ?? 0), 2),
            'bestScore' => round((float) ($attempts->max('score') ?? 0), 2),
            'scoreLabels' => $recentAttempts->map(fn (QuizAttempt $attempt) => $attempt->created_at->format('M d'))->values(),
            'scoreValues' => $recentAttempts->map(fn (QuizAttempt $attempt) => (float) $attempt->score)->values(),
            'confidenceLabels' => $recentAttempts->map(fn (QuizAttempt $attempt) => str($attempt->quiz->title)->limit(16)->toString())->values(),
            'confidenceValues' => $recentAttempts->map(fn (QuizAttempt $attempt) => (float) ($attempt->ai_confidence ?? 0))->values(),
            'difficultyLabels' => $difficultyLabels,
            'difficultyAccuracy' => $difficultyAccuracy,
        ]);
    }
}
