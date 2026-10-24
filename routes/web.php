<?php

use App\Http\Controllers\Admin\ResourceController as AdminResourceController;
use App\Http\Controllers\AdminAnalyticsController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/resources', [ResourceController::class, 'index'])->name('resources.index');

Route::get('/dashboard', [UserController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Assessment
    Route::get('/assessment', [AssessmentController::class, 'show'])->name('assessment.index');
    Route::get('/assessment/consent', [AssessmentController::class, 'consent'])->name('assessment.consent');
    Route::post('/assessment/consent', [AssessmentController::class, 'storeConsent'])
        ->middleware('throttle:10,10')
        ->name('assessment.consent.store');
    Route::get('/assessment/start', [AssessmentController::class, 'show'])->name('assessment.show');
    Route::post('/assessment/submit', [AssessmentController::class, 'submit'])
        ->middleware('throttle:5,10')
        ->name('assessment.submit');

    // Results
    Route::get('/results', [AssessmentController::class, 'results'])->name('results.index');

    Route::get('/quizzes', [QuizController::class, 'index'])->name('quizzes.index');
    Route::get('/quizzes/upload', [QuizController::class, 'create'])->name('quizzes.create');
    Route::post('/quizzes', [QuizController::class, 'store'])->name('quizzes.store');
    Route::get('/quizzes/{quiz}', [QuizController::class, 'show'])->name('quizzes.show');
    Route::get('/quizzes/{quiz}/status', [QuizController::class, 'status'])->name('quizzes.status');
    Route::get('/quizzes/{quiz}/material', [QuizController::class, 'downloadMaterial'])->name('quizzes.material');
    Route::post('/quizzes/{quiz}/retry', [QuizController::class, 'retry'])->name('quizzes.retry');
    Route::get('/quizzes/{quiz}/flashcards', [QuizController::class, 'flashcards'])->name('quizzes.flashcards');
    Route::delete('/quizzes/{quiz}', [QuizController::class, 'destroy'])->name('quizzes.destroy');
    Route::get('/quizzes/{quiz}/take', [QuizAttemptController::class, 'create'])->name('quizzes.take');
    Route::post('/quizzes/{quiz}/attempts', [QuizAttemptController::class, 'store'])->name('quizzes.attempts.store');
    Route::get('/quiz-attempts/{quizAttempt}', [QuizAttemptController::class, 'show'])->name('quiz-attempts.show');
    // Admin panel
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/analytics', [AdminAnalyticsController::class, 'index'])->name('analytics');
        Route::get('/analytics/programs', [AdminAnalyticsController::class, 'programDistribution'])->name('analytics.programs');

        Route::get('/resources', [AdminResourceController::class, 'index'])->name('resources.index');
        Route::post('/resources', [AdminResourceController::class, 'store'])->name('resources.store');
        Route::put('/resources/{resource}', [AdminResourceController::class, 'update'])->name('resources.update');
        Route::delete('/resources/{resource}', [AdminResourceController::class, 'destroy'])->name('resources.destroy');
    });
});

require __DIR__.'/auth.php';
