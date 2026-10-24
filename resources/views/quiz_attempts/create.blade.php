<x-app-layout title="Take Quiz">
    <div class="flex-grow w-full max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-10 flex flex-col gap-6">

        {{-- Page Header + Timer --}}
        <div class="flex flex-col sm:flex-row justify-between sm:items-end gap-4 pb-4 border-b border-surface-low">
            <div>
                <h1 class="font-headline text-2xl sm:text-3xl font-bold text-primary">{{ $quiz->title }}</h1>
                <p class="text-sm text-ink-muted mt-1">{{ $quiz->questions->count() }} questions · {{ $timeLimitMinutes }} minutes</p>
            </div>
            <div class="rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white shadow-md shrink-0 tabular-nums">
                <span id="timer">{{ sprintf('%02d:%02d', intdiv($secondsRemaining, 60), $secondsRemaining % 60) }}</span>
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                <p class="font-semibold">Please correct the following before submitting:</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="quiz-form" method="POST" action="{{ route('quizzes.attempts.store', $quiz) }}" class="card overflow-hidden mb-8">
            @csrf

            <div class="border-b border-surface-low p-5">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-semibold text-primary-container">Progress</span>
                    <span id="progress-label" class="text-ink-muted">Question 1 of {{ $quiz->questions->count() }}</span>
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-surface-high">
                    <div id="progress-bar" class="h-full rounded-full bg-accent transition-all" style="width: {{ 100 / max($quiz->questions->count(), 1) }}%"></div>
                </div>
            </div>

            <div class="p-5 sm:p-8">
                @foreach ($quiz->questions as $question)
                    <section class="question-page {{ $loop->first ? '' : 'hidden' }}" data-page="{{ $loop->index }}">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-surface px-3 py-1 text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ $question->type === 'mcq' ? 'MCQ' : 'Identification' }}</span>
                        </div>

                        <h2 class="mt-5 font-headline text-xl font-bold leading-8 text-ink">{{ $loop->iteration }}. {{ $question->question }}</h2>

                        @if ($question->isMcq())
                            <div class="mt-6 grid gap-3">
                                @foreach ($question->choices as $choice)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-surface-low bg-surface/50 px-4 py-3 transition hover:border-accent hover:bg-accent/10 has-[:checked]:border-accent has-[:checked]:bg-accent/10">
                                        <input type="radio" name="answers[{{ $question->id }}]" value="{{ $choice }}" @checked(old('answers.'.$question->id) === $choice) class="h-4 w-4 border-slate-300 text-primary focus:ring-accent">
                                        <span class="text-sm font-medium text-ink">{{ $choice }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <div class="mt-6">
                                <label for="answer-{{ $question->id }}" class="block text-sm font-semibold text-primary-container">Answer</label>
                                <input id="answer-{{ $question->id }}" name="answers[{{ $question->id }}]" type="text" value="{{ old('answers.'.$question->id) }}" class="mt-2 block w-full rounded-xl border-surface-high bg-white text-ink shadow-sm focus:border-accent focus:ring-accent" autocomplete="off">
                            </div>
                        @endif
                    </section>
                @endforeach
            </div>

            <div class="flex items-center justify-between border-t border-surface-low p-5">
                <button id="prev-button" type="button" class="rounded-xl border border-surface-high bg-white px-4 py-2 text-sm font-semibold text-primary-container transition hover:bg-surface-low disabled:cursor-not-allowed disabled:opacity-40" disabled>
                    Previous
                </button>
                <div class="flex gap-3">
                    <a href="{{ route('quizzes.show', $quiz) }}" class="rounded-xl border border-surface-high bg-white px-4 py-2 text-sm font-semibold text-primary-container transition hover:bg-surface-low">Exit</a>
                    <button id="next-button" type="button" class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-container">
                        Next
                    </button>
                    <button id="submit-button" type="submit" class="hidden rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        Submit
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        const pages = Array.from(document.querySelectorAll('.question-page'));
        const progressLabel = document.getElementById('progress-label');
        const progressBar = document.getElementById('progress-bar');
        const prevButton = document.getElementById('prev-button');
        const nextButton = document.getElementById('next-button');
        const submitButton = document.getElementById('submit-button');
        const quizForm = document.getElementById('quiz-form');
        const timer = document.getElementById('timer');
        let currentPage = 0;
        let secondsLeft = {{ $secondsRemaining }};
        let submitted = false;
        const countdown = setInterval(tick, 1000);

        function renderPage() {
            pages.forEach((page, index) => page.classList.toggle('hidden', index !== currentPage));
            progressLabel.textContent = `Question ${currentPage + 1} of ${pages.length}`;
            progressBar.style.width = `${((currentPage + 1) / pages.length) * 100}%`;
            prevButton.disabled = currentPage === 0;
            nextButton.classList.toggle('hidden', currentPage === pages.length - 1);
            submitButton.classList.toggle('hidden', currentPage !== pages.length - 1);
        }

        prevButton.addEventListener('click', () => {
            currentPage = Math.max(0, currentPage - 1);
            renderPage();
        });

        nextButton.addEventListener('click', () => {
            currentPage = Math.min(pages.length - 1, currentPage + 1);
            renderPage();
        });

        function tick() {
            secondsLeft -= 1;
            const minutes = Math.max(0, Math.floor(secondsLeft / 60));
            const seconds = Math.max(0, secondsLeft % 60);
            timer.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

            if (secondsLeft <= 0 && !submitted) {
                submitted = true;
                clearInterval(countdown);
                quizForm.submit();
            }
        }

        // Manual submits stop the countdown so it cannot fire again later.
        quizForm.addEventListener('submit', () => {
            submitted = true;
            clearInterval(countdown);
        });
    </script>
</x-app-layout>
