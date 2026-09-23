<x-app-layout title="AI Quizzes">
    <div class="flex-grow w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-10 flex flex-col gap-8">

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row justify-between sm:items-end gap-4">
            <div>
                <h1 class="font-headline text-2xl sm:text-3xl font-bold text-primary">AI Quiz Generator</h1>
                <p class="text-sm text-ink-muted mt-1">Generated assessments from uploaded learning materials</p>
            </div>
            <a href="{{ route('quizzes.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-accent hover:bg-accent-hover text-primary text-sm font-bold shadow-md transition-all shrink-0">
                <x-icon name="file-text" class="w-4 h-4"/>
                Upload Material
            </a>
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        {{-- Stats --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="card p-5 border border-surface-low">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Total Quizzes</p>
                <p class="mt-2 text-3xl font-headline font-bold text-primary">{{ $stats['total'] }}</p>
            </div>
            <div class="card p-5 border border-surface-low">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Ready</p>
                <p class="mt-2 text-3xl font-headline font-bold text-emerald-600">{{ $stats['ready'] }}</p>
            </div>
            <div class="card p-5 border border-surface-low">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Processing</p>
                <p class="mt-2 text-3xl font-headline font-bold text-accent-ink">{{ $stats['processing'] }}</p>
            </div>
            <div class="card p-5 border border-surface-low">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Needs Retry</p>
                <p class="mt-2 text-3xl font-headline font-bold text-rose-600">{{ $stats['failed'] }}</p>
            </div>
        </div>

        {{-- Quiz List --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 pb-8">
            @forelse ($quizzes as $quiz)
                <article class="card p-6 hover:shadow-ambient-lg transition-shadow">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="font-headline font-bold text-base text-primary">{{ $quiz->title }}</h3>
                            <p class="mt-1 text-xs text-ink-muted">{{ $quiz->original_filename ?? basename($quiz->source_file) }}</p>
                        </div>
                        <span @class([
                            'rounded-full px-3 py-1 text-xs font-semibold shrink-0',
                            'bg-emerald-100 text-emerald-700' => $quiz->status === 'ready',
                            'bg-accent/20 text-accent-ink' => in_array($quiz->status, ['queued', 'processing'], true),
                            'bg-rose-100 text-rose-700' => $quiz->status === 'failed',
                        ])>
                            {{ ucfirst($quiz->status) }}
                        </span>
                    </div>

                    <div class="mt-5 grid grid-cols-3 gap-3 text-sm">
                        <div class="rounded-xl bg-surface p-3">
                            <p class="text-xs text-ink-muted">Questions</p>
                            <p class="mt-1 font-bold text-primary">{{ $quiz->questions_count }}</p>
                        </div>
                        <div class="rounded-xl bg-surface p-3">
                            <p class="text-xs text-ink-muted">Attempts</p>
                            <p class="mt-1 font-bold text-primary">{{ $quiz->attempts_count }}</p>
                        </div>
                        <div class="rounded-xl bg-surface p-3">
                            <p class="text-xs text-ink-muted">Confidence</p>
                            <p class="mt-1 font-bold text-primary">{{ $quiz->ai_confidence ? $quiz->ai_confidence.'%' : '—' }}</p>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('quizzes.show', $quiz) }}" class="rounded-xl border border-surface-high bg-white px-4 py-2 text-sm font-semibold text-primary-container transition hover:bg-surface-low">
                            View
                        </a>

                        @if ($quiz->isReady())
                            <a href="{{ route('quizzes.take', $quiz) }}" class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-container">
                                Take Quiz
                            </a>
                        @elseif ($quiz->isFailed())
                            <form method="POST" action="{{ route('quizzes.retry', $quiz) }}">
                                @csrf
                                <button class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700">Retry</button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('quizzes.destroy', $quiz) }}"
                              onsubmit="return confirm('Delete this quiz permanently? Its questions, attempts, and uploaded material will also be removed.')">
                            @csrf
                            @method('DELETE')
                            <button class="rounded-xl border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50">Delete</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="card p-10 text-center lg:col-span-2">
                    <h3 class="font-headline text-lg font-bold text-primary">No generated quizzes yet</h3>
                    <p class="mt-2 text-sm text-ink-muted">Upload a learning material to create the first AI-generated quiz.</p>
                    <a href="{{ route('quizzes.create') }}" class="mt-5 inline-flex rounded-xl bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-bold text-primary shadow-md transition-all">Upload Material</a>
                </div>
            @endforelse
        </div>

        <div class="pb-8">
            {{ $quizzes->links() }}
        </div>
    </div>
</x-app-layout>
