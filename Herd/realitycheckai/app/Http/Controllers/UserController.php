<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        if (Auth::check() && Auth::user()->user_type == 'user') {
            $userId = Auth::id();

            return view('dashboard', [
                'assessmentAnswered' => Answer::query()->where('user_id', $userId)->count(),
                'quizCount' => Quiz::query()->where('user_id', $userId)->count(),
                'readyQuizCount' => Quiz::query()->where('user_id', $userId)->where('status', Quiz::STATUS_READY)->count(),
                'averageQuizScore' => round((float) (QuizAttempt::query()->where('user_id', $userId)->avg('score') ?? 0), 2),
                'latestAttempts' => QuizAttempt::query()
                    ->with('quiz')
                    ->where('user_id', $userId)
                    ->latest()
                    ->take(3)
                    ->get(),
            ]);
        } elseif (Auth::check() && Auth::user()->isAdmin()) {
            return redirect()->route('admin.analytics');
        }

        abort(403);
    }
}
