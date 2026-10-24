<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateQuizFromMaterial;
use App\Models\Quiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuizController extends Controller
{
    public function index(): View
    {
        $quizzes = Quiz::query()
            ->where('user_id', Auth::id())
            ->withCount(['questions', 'attempts'])
            ->latest()
            ->paginate(6);

        $stats = [
            'total' => Quiz::query()->where('user_id', Auth::id())->count(),
            'ready' => Quiz::query()->where('user_id', Auth::id())->where('status', Quiz::STATUS_READY)->count(),
            'processing' => Quiz::query()->where('user_id', Auth::id())->whereIn('status', [Quiz::STATUS_QUEUED, Quiz::STATUS_PROCESSING])->count(),
            'failed' => Quiz::query()->where('user_id', Auth::id())->where('status', Quiz::STATUS_FAILED)->count(),
        ];

        return view('quizzes.index', compact('quizzes', 'stats'));
    }

    public function create(): View
    {
        return view('quizzes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'material' => ['required', 'file', 'mimes:pdf,docx,txt', 'max:10240'],
        ]);

        $file = $request->file('material');
        $path = $file->store('uploads', 'local');
        $title = $validated['title'] ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $quiz = Quiz::query()->create([
            'user_id' => Auth::id(),
            'title' => $title,
            'source_file' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'status' => Quiz::STATUS_QUEUED,
            'ai_model' => config('services.openai.models.quiz', 'gpt-4o-mini'),
        ]);

        GenerateQuizFromMaterial::dispatch($quiz->id);

        return redirect()
            ->route('quizzes.show', $quiz)
            ->with('status', 'Your learning material was uploaded. AI quiz generation has started.');
    }

    public function show(Quiz $quiz): View
    {
        $this->authorizeOwner($quiz);

        $quiz->load([
            'questions' => fn ($query) => $query->orderBy('id'),
            'flashcards' => fn ($query) => $query->orderBy('id'),
            'attempts' => fn ($query) => $query->where('user_id', Auth::id())->latest(),
        ]);

        return view('quizzes.show', compact('quiz'));
    }

    public function flashcards(Quiz $quiz): View|RedirectResponse
    {
        $this->authorizeOwner($quiz);

        if (! $quiz->isReady()) {
            return redirect()
                ->route('quizzes.show', $quiz)
                ->with('status', 'Quiz generation is not complete yet. Study the flashcards after generation finishes.');
        }

        $quiz->load('flashcards');

        return view('quizzes.flashcards', compact('quiz'));
    }

    public function status(Quiz $quiz): JsonResponse
    {
        $this->authorizeOwner($quiz);

        return response()->json([
            'status' => $quiz->status,
            'question_count' => $quiz->questions()->count(),
            'error' => $quiz->generation_error,
            'ready' => $quiz->isReady(),
            'failed' => $quiz->isFailed(),
            'redirect_url' => route('quizzes.show', $quiz),
        ]);
    }

    public function downloadMaterial(Quiz $quiz): BinaryFileResponse|StreamedResponse
    {
        $this->authorizeOwner($quiz);

        abort_if(empty($quiz->source_file), 404);

        return Storage::disk('local')->download(
            $quiz->source_file,
            $quiz->original_filename ?: basename($quiz->source_file),
        );
    }

    public function retry(Quiz $quiz): RedirectResponse
    {
        $this->authorizeOwner($quiz);

        $quiz->update([
            'status' => Quiz::STATUS_QUEUED,
            'generation_error' => null,
        ]);

        GenerateQuizFromMaterial::dispatch($quiz->id);

        return redirect()
            ->route('quizzes.show', $quiz)
            ->with('status', 'Quiz generation has been queued again.');
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        $this->authorizeOwner($quiz);

        DB::transaction(function () use ($quiz): void {
            // Drop any pending or failed generation jobs so the worker never
            // regenerates (or retries) a quiz that has just been deleted.
            // Questions, attempts, and answers are removed by database cascades.
            foreach (['jobs', 'failed_jobs'] as $table) {
                DB::table($table)
                    ->where('queue', 'ai')
                    ->where('payload', 'like', '%"quizId";i:'.$quiz->id.';%')
                    ->delete();
            }

            if ($quiz->source_file) {
                Storage::disk('local')->delete($quiz->source_file);
            }

            $quiz->delete();
        });

        return redirect()
            ->route('quizzes.index')
            ->with('status', '"'.$quiz->title.'" was deleted along with its attempts and material.');
    }

    private function authorizeOwner(Quiz $quiz): void
    {
        abort_unless($quiz->user_id === Auth::id(), 403);
    }
}
