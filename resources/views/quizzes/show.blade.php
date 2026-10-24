<x-app-layout title="{{ $quiz->title }}">
    <div class="flex-grow w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-10 flex flex-col gap-8">

        {{-- Page Header --}}
        <div class="flex flex-col gap-4 sm:flex-row justify-between sm:items-end pb-4 border-b border-surface-low">
            <div>
                <h1 class="font-headline text-2xl sm:text-3xl font-bold text-primary">{{ $quiz->title }}</h1>
                <p class="text-sm text-ink-muted mt-1">{{ $quiz->original_filename ?? basename($quiz->source_file) }}</p>
            </div>
            <div class="flex flex-wrap gap-3 shrink-0">
                <a href="{{ route('quizzes.index') }}" class="rounded-xl border border-surface-high bg-white px-4 py-2 text-sm font-semibold text-primary-container hover:bg-surface-low transition">All Quizzes</a>
                @if ($quiz->source_file)
                    <a href="{{ route('quizzes.material', $quiz) }}" class="rounded-xl border border-primary-container/30 bg-white px-4 py-2 text-sm font-semibold text-primary-container hover:bg-surface-low transition">Download Material</a>
                @endif
                <form method="POST" action="{{ route('quizzes.destroy', $quiz) }}"
                      onsubmit="return confirm('Delete this quiz permanently? Its questions, attempts, and uploaded material will also be removed.')">
                    @csrf
                    @method('DELETE')
                    <button class="rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50 transition">Delete</button>
                </form>
                @if ($quiz->isReady())
                    <a href="{{ route('quizzes.take', $quiz) }}" class="rounded-xl bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-bold text-primary shadow-md transition-all">Take Quiz</a>
                @endif
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if (! $quiz->isReady())
            <section class="card p-6 sm:p-8 mb-8" data-status-url="{{ route('quizzes.status', $quiz) }}">
                @if ($quiz->isFailed())
                    <div class="max-w-2xl">
                        <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700">Generation failed</span>
                        <h2 class="mt-4 font-headline text-xl font-bold text-primary">AI could not generate a valid quiz</h2>
                        <p class="mt-2 text-sm text-ink-muted">{{ $quiz->generation_error }}</p>
                        <form method="POST" action="{{ route('quizzes.retry', $quiz) }}" class="mt-6">
                            @csrf
                            <button class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 transition">Retry Generation</button>
                        </form>
                    </div>
                @else
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <span class="rounded-full bg-accent/20 px-3 py-1 text-xs font-semibold text-accent-ink">{{ ucfirst($quiz->status) }}</span>
                            <h2 class="mt-4 font-headline text-xl font-bold text-primary">AI quiz generation is running</h2>
                            <p class="mt-2 max-w-2xl text-sm text-ink-muted">Text extraction, strict JSON generation, validation, and database saving are handled by the queue worker.</p>
                        </div>
                        <div class="flex h-24 w-24 items-center justify-center rounded-full bg-surface-low shrink-0">
                            <div class="h-14 w-14 animate-spin rounded-full border-4 border-surface-high border-t-primary"></div>
                        </div>
                    </div>

                    <div class="mt-8 grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="rounded-xl bg-surface-low p-4 text-sm font-medium text-primary-container">Upload saved</div>
                        <div class="rounded-xl bg-surface-low p-4 text-sm font-medium text-primary-container">Text extraction</div>
                        <div class="rounded-xl bg-accent/20 p-4 text-sm font-medium text-accent-ink">AI generation</div>
                        <div class="rounded-xl bg-surface p-4 text-sm font-medium text-ink-faint">Quiz ready</div>
                    </div>
                @endif
            </section>
        @else
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="card p-5 border border-surface-low">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Questions</p>
                    <p class="mt-2 text-3xl font-headline font-bold text-primary">{{ $quiz->questions->count() }}</p>
                </div>
                <div class="card p-5 border border-surface-low">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">MCQs</p>
                    <p class="mt-2 text-3xl font-headline font-bold text-primary-container">{{ $quiz->questions->where('type', 'mcq')->count() }}</p>
                </div>
                <div class="card p-5 border border-surface-low">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Flashcards</p>
                    <p class="mt-2 text-3xl font-headline font-bold text-primary-container">{{ $quiz->flashcards->count() }}</p>
                </div>
                <div class="card p-5 border border-surface-low">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">AI Confidence</p>
                    <p class="mt-2 text-3xl font-headline font-bold text-emerald-600">{{ $quiz->ai_confidence ?? 0 }}%</p>
                </div>
            </div>

            @if ($quiz->attempts->isEmpty())
                <section class="card p-6 sm:p-8">
                    <div class="max-w-3xl">
                        <span class="rounded-full bg-accent/20 px-3 py-1 text-xs font-semibold text-accent-ink">Ready to take</span>
                        <h2 class="mt-4 font-headline text-xl font-bold text-primary">Take the quiz before reviewing answers</h2>
                        <p class="mt-2 text-sm leading-relaxed text-ink-muted">
                            Complete an attempt to see the questions, correct answers, and explanations. Your score and feedback will be available as soon as you submit.
                        </p>
                    </div>

                    <p class="mt-6 text-sm text-ink-muted">
                        {{ $quiz->flashcards->count() }} flashcards are available in a separate, ungraded study mode.
                    </p>
                </section>
            @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 pb-8">
                <section class="lg:col-span-2 space-y-4">
                    @foreach ($quiz->questions as $question)
                        <article class="card p-5">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-surface px-3 py-1 text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ $question->type === 'mcq' ? 'MCQ' : 'Identification' }}</span>
                            </div>
                            <h3 class="mt-4 font-headline text-base font-bold text-ink">{{ $loop->iteration }}. {{ $question->question }}</h3>

                            @if ($question->isMcq())
                                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                                    @foreach ($question->choices as $choice)
                                        <div @class([
                                            'rounded-xl border px-3 py-2 text-sm',
                                            'border-emerald-300 bg-emerald-50 text-emerald-800' => $choice === $question->correct_answer,
                                            'border-surface-low text-ink-muted' => $choice !== $question->correct_answer,
                                        ])>
                                            {{ $choice }}
                                        </div>
                                    @endforeach
                                </div>
                                <p class="mt-4 rounded-xl bg-surface px-3 py-2 text-sm text-ink-muted">
                                    Answer key: <span class="font-semibold text-primary">{{ $question->correct_answer }}</span>
                                </p>
                            @else
                                <p class="mt-4 rounded-xl bg-surface px-3 py-2 text-sm text-ink-muted">
                                    Answer key: <span class="font-semibold text-primary">{{ $question->correct_answer }}</span>
                                </p>
                            @endif
                            @if ($question->explanation)
                                <p class="mt-3 text-sm leading-relaxed text-ink-muted">{{ $question->explanation }}</p>
                            @endif
                        </article>
                    @endforeach
                </section>

                <aside class="space-y-5">
                    <section class="card p-5">
                        <h3 class="font-headline font-bold text-base text-primary">Attempt History</h3>
                        <div class="mt-4 space-y-3">
                            @forelse ($quiz->attempts as $attempt)
                                <a href="{{ route('quiz-attempts.show', $attempt) }}" class="block rounded-xl bg-surface p-3 text-sm transition hover:bg-surface-low">
                                    <span class="font-bold text-accent-ink">{{ $attempt->score }}%</span>
                                    <span class="ml-2 text-ink-faint">{{ $attempt->created_at->format('M d, Y h:i A') }}</span>
                                </a>
                            @empty
                                <p class="text-sm text-ink-muted">No attempts yet.</p>
                            @endforelse
                        </div>
                    </section>

                    <section class="rounded-2xl bg-gradient-to-b from-primary to-primary-container p-5 text-white shadow-ambient">
                        <h3 class="font-headline font-bold text-base">Review or retake</h3>
                        <p class="mt-2 text-sm text-slate-200">Practice with flashcards or take the graded MCQ quiz.</p>
                        @if ($quiz->flashcards->isNotEmpty())
                            <a href="{{ route('quizzes.flashcards', $quiz) }}" class="mt-5 inline-flex rounded-xl border border-white/40 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/10">Study Flashcards</a>
                        @endif
                        <a href="{{ route('quizzes.take', $quiz) }}" class="mt-5 inline-flex rounded-xl bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-bold text-primary transition-all">Retake Quiz</a>
                    </section>
                </aside>
            </div>
            @endif
        @endif
    </div>

    @if (! $quiz->isReady() && ! $quiz->isFailed())
        <script>
            const statusPanel = document.querySelector('[data-status-url]');
            const statusUrl = statusPanel?.dataset.statusUrl;

            if (statusUrl) {
                const pollStatus = async () => {
                    const response = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
                    const data = await response.json();

                    if (data.ready || data.failed) {
                        window.location.href = data.redirect_url;
                    }
                };

                setInterval(pollStatus, 2000);
            }
        </script>
    @endif
</x-app-layout>
