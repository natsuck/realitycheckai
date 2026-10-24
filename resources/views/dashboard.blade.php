<x-app-layout title="Dashboard">
    <div class="flex-grow w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-10 flex flex-col gap-8">

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row justify-between sm:items-end gap-4">
            <div>
                <h1 class="font-headline text-2xl sm:text-3xl font-bold text-primary">Student Dashboard</h1>
                <p class="text-sm text-ink-muted mt-1">Welcome back, {{ auth()->user()->name }}. Continue your assessment and AI learning activities.</p>
            </div>
            <a href="{{ route('assessment.show') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-accent hover:bg-accent-hover text-primary text-sm font-bold shadow-md transition-all shrink-0">
                <x-icon name="sparkles" class="w-4 h-4"/>
                Start Assessment
            </a>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="card p-6 border border-surface-low">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Assessment Attempts</p>
                <p class="mt-3 text-3xl font-headline font-bold text-primary">{{ $assessmentAttempts }}</p>
                <p class="mt-1 text-xs text-ink-muted">Completed 150-item assessments</p>
            </div>
            <div class="card p-6 border border-surface-low">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Generated Quizzes</p>
                <p class="mt-3 text-3xl font-headline font-bold text-primary-container">{{ $quizCount }}</p>
                <p class="mt-1 text-xs text-ink-muted">{{ $readyQuizCount }} ready to take</p>
            </div>
            <div class="card p-6 border border-surface-low">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Average Quiz Score</p>
                <p class="mt-3 text-3xl font-headline font-bold text-emerald-600">{{ $averageQuizScore }}%</p>
                <p class="mt-1 text-xs text-ink-muted">Based on completed quiz attempts</p>
            </div>
        </div>

        {{-- Modules + Recent Activity --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 pb-8">
            <section class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-5">
                <a href="{{ route('assessment.show') }}" class="card p-6 group hover:border-primary transition-all hover:-translate-y-0.5 hover:shadow-ambient-lg">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-surface-low text-primary-container group-hover:bg-primary group-hover:text-white transition-colors">
                        <x-icon name="brain" class="w-5 h-5"/>
                    </div>
                    <h3 class="mt-5 font-headline font-bold text-base text-primary">AI Program Assessment</h3>
                    <p class="mt-2 text-xs leading-relaxed text-ink-muted">Complete 90 career-interest items and 60 aptitude questions to receive a scored profile with an AI explanation.</p>
                </a>

                <a href="{{ route('quizzes.create') }}" class="card p-6 group hover:border-primary transition-all hover:-translate-y-0.5 hover:shadow-ambient-lg">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-surface-low text-primary-container group-hover:bg-primary group-hover:text-white transition-colors">
                        <x-icon name="file-text" class="w-5 h-5"/>
                    </div>
                    <h3 class="mt-5 font-headline font-bold text-base text-primary">AI Quiz Generator</h3>
                    <p class="mt-2 text-xs leading-relaxed text-ink-muted">Upload PDF, DOCX, or TXT material to generate reviewable MCQ quizzes and separate study flashcards.</p>
                </a>

                <a href="{{ route('results.index') }}" class="card p-6 group hover:border-primary transition-all hover:-translate-y-0.5 hover:shadow-ambient-lg">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-surface-low text-primary-container group-hover:bg-primary group-hover:text-white transition-colors">
                        <x-icon name="compass" class="w-5 h-5"/>
                    </div>
                    <h3 class="mt-5 font-headline font-bold text-base text-primary">Assessment Results</h3>
                    <p class="mt-2 text-xs leading-relaxed text-ink-muted">Review your RIASEC profile, separate aptitude scores, AI explanation, and next steps.</p>
                </a>

            </section>

            <aside class="space-y-5">
                <section class="card p-6">
                    <h3 class="font-headline font-bold text-base text-primary">Recent Quiz Attempts</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($latestAttempts as $attempt)
                            <a href="{{ route('quiz-attempts.show', $attempt) }}" class="block rounded-xl bg-surface p-3 text-sm transition hover:bg-surface-low">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="font-semibold text-primary truncate">{{ $attempt->quiz->title }}</span>
                                    <span class="font-bold text-accent-ink shrink-0">{{ $attempt->score }}%</span>
                                </div>
                                <p class="mt-1 text-xs text-ink-faint">{{ $attempt->created_at->format('M d, Y h:i A') }}</p>
                            </a>
                        @empty
                            <p class="text-sm leading-relaxed text-ink-muted">No attempts yet. Generate or take a quiz to see performance history here.</p>
                        @endforelse
                    </div>
                </section>
            </aside>

        </div>
    </div>
</x-app-layout>
