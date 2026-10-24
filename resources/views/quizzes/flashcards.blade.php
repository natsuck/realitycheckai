<x-app-layout title="Study Flashcards">
    <div class="flex-grow w-full max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-10 flex flex-col gap-6">
        <div class="flex items-center justify-between gap-4 border-b border-surface-low pb-4">
            <div>
                <h1 class="font-headline text-2xl sm:text-3xl font-bold text-primary">{{ $quiz->title }} Flashcards</h1>
                <p class="mt-1 text-sm text-ink-muted">Study mode · {{ $quiz->flashcards->count() }} cards · Not graded</p>
            </div>
            <a href="{{ route('quizzes.show', $quiz) }}" class="shrink-0 rounded-xl border border-surface-high bg-white px-4 py-2 text-sm font-semibold text-primary-container hover:bg-surface-low">Back to quiz</a>
        </div>

        @if ($quiz->flashcards->isEmpty())
            <section class="card p-8 text-center">
                <h2 class="font-headline text-lg font-bold text-primary">No flashcards were generated</h2>
                <p class="mt-2 text-sm text-ink-muted">This quiz has no study cards available.</p>
            </section>
        @else
            <section class="card p-6 sm:p-10" aria-live="polite">
                <div class="flex items-center justify-between text-sm text-ink-muted">
                    <span>Card <span id="card-number">1</span> of {{ $quiz->flashcards->count() }}</span>
                    <span id="card-side">Question</span>
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-surface-high">
                    <div id="card-progress" class="h-full rounded-full bg-accent transition-all" style="width: {{ 100 / max($quiz->flashcards->count(), 1) }}%"></div>
                </div>

                <div class="mt-8 flex min-h-64 flex-col items-center justify-center rounded-2xl bg-surface px-6 py-10 text-center">
                    <p id="card-content" class="max-w-2xl font-headline text-xl font-bold leading-relaxed text-primary"></p>
                    <button id="reveal-button" type="button" class="mt-8 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-container">
                        Reveal answer
                    </button>
                </div>

                <div class="mt-6 flex items-center justify-between gap-3">
                    <button id="previous-card" type="button" class="rounded-xl border border-surface-high bg-white px-4 py-2 text-sm font-semibold text-primary-container disabled:opacity-40" disabled>Previous</button>
                    <button id="next-card" type="button" class="rounded-xl bg-accent px-4 py-2 text-sm font-semibold text-primary">Next</button>
                </div>
            </section>
        @endif
    </div>

    @if ($quiz->flashcards->isNotEmpty())
        <script>
            const cards = @json($quiz->flashcards->map(fn ($card) => ['front' => $card->front, 'back' => $card->back])->values());
            const content = document.getElementById('card-content');
            const number = document.getElementById('card-number');
            const side = document.getElementById('card-side');
            const progress = document.getElementById('card-progress');
            const reveal = document.getElementById('reveal-button');
            const previous = document.getElementById('previous-card');
            const next = document.getElementById('next-card');
            let current = 0;
            let showingAnswer = false;

            function renderCard() {
                content.textContent = showingAnswer ? cards[current].back : cards[current].front;
                number.textContent = String(current + 1);
                side.textContent = showingAnswer ? 'Answer' : 'Question';
                reveal.textContent = showingAnswer ? 'Show question' : 'Reveal answer';
                previous.disabled = current === 0;
                next.disabled = current === cards.length - 1;
                progress.style.width = `${((current + 1) / cards.length) * 100}%`;
            }

            reveal.addEventListener('click', () => {
                showingAnswer = !showingAnswer;
                renderCard();
            });

            previous.addEventListener('click', () => {
                current = Math.max(0, current - 1);
                showingAnswer = false;
                renderCard();
            });

            next.addEventListener('click', () => {
                current = Math.min(cards.length - 1, current + 1);
                showingAnswer = false;
                renderCard();
            });

            renderCard();
        </script>
    @endif
</x-app-layout>
