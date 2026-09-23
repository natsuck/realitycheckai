<x-app-layout title="Quiz Result">
    <div class="flex-grow w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-10 flex flex-col gap-8">

        {{-- Page Header --}}
        <div class="flex flex-col gap-4 sm:flex-row justify-between sm:items-end pb-4 border-b border-surface-low">
            <div>
                <h1 class="font-headline text-2xl sm:text-3xl font-bold text-primary">Quiz Result</h1>
                <p class="text-sm text-ink-muted mt-1">{{ $attempt->quiz->title }} · {{ $attempt->created_at->format('M d, Y h:i A') }}</p>
            </div>
            <div class="flex gap-3 shrink-0">
                <a href="{{ route('quiz-analytics.index') }}" class="rounded-xl border border-surface-high bg-white px-4 py-2 text-sm font-semibold text-primary-container hover:bg-surface-low transition">Analytics</a>
                <a href="{{ route('quizzes.take', $attempt->quiz) }}" class="rounded-xl bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-bold text-primary shadow-md transition-all">Retake</a>
            </div>
        </div>

        {{-- Summary --}}
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-5">
            <section class="card p-6 lg:col-span-2 bg-gradient-to-b from-primary to-primary-container text-white shadow-ambient">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-300">Final Score</p>
                <div class="mt-4 flex items-end gap-3">
                    <span class="text-6xl font-headline font-extrabold tabular-nums">{{ $attempt->score }}</span>
                    <span class="pb-2 text-xl font-bold text-accent">%</span>
                </div>
                <p class="mt-4 text-sm leading-relaxed text-slate-200">{{ $attempt->feedback }}</p>
            </section>

            <div class="card p-6">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Correct Answers</p>
                <p class="mt-4 text-4xl font-headline font-bold text-emerald-600">{{ $attempt->correct_count }}/{{ $attempt->total_questions }}</p>
            </div>

            <div class="card p-6">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">AI Confidence</p>
                <p class="mt-4 text-4xl font-headline font-bold text-primary-container">{{ $attempt->ai_confidence ?? 0 }}%</p>
            </div>
        </div>

        {{-- Answer Review --}}
        <div class="grid grid-cols-1 gap-4 pb-8">
            @foreach ($attempt->answers as $answer)
                <article class="card p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-surface px-3 py-1 text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ $answer->question->type === 'mcq' ? 'MCQ' : 'Identification' }}</span>
                            <span class="rounded-full bg-accent/20 px-3 py-1 text-xs font-semibold text-accent-ink">{{ ucfirst($answer->question->difficulty) }}</span>
                        </div>
                        <span @class([
                            'rounded-full px-3 py-1 text-xs font-semibold',
                            'bg-emerald-100 text-emerald-700' => $answer->is_correct,
                            'bg-rose-100 text-rose-700' => ! $answer->is_correct,
                        ])>
                            {{ $answer->is_correct ? 'Correct' : 'Incorrect' }}
                        </span>
                    </div>

                    <h3 class="mt-4 font-headline text-base font-bold text-ink">{{ $loop->iteration }}. {{ $answer->question->question }}</h3>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="rounded-xl bg-surface p-3 text-sm">
                            <p class="font-semibold text-ink-muted">Your Answer</p>
                            <p class="mt-1 font-medium text-ink">{{ $answer->user_answer ?: 'No answer' }}</p>
                        </div>
                        <div class="rounded-xl bg-surface p-3 text-sm">
                            <p class="font-semibold text-ink-muted">Correct Answer</p>
                            <p class="mt-1 font-medium text-primary">{{ $answer->question->correct_answer }}</p>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl bg-surface-low p-3 text-sm text-primary-container">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-semibold">Evaluation</span>
                            <span>{{ $answer->ai_confidence ?? 0 }}% confidence</span>
                        </div>
                        <p class="mt-2 leading-relaxed">{{ $answer->ai_explanation }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</x-app-layout>
