<x-app-layout title="Quiz Analytics">
    <div class="flex-grow w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-10 flex flex-col gap-8">

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row justify-between sm:items-end gap-4">
            <div>
                <h1 class="font-headline text-2xl sm:text-3xl font-bold text-primary">Quiz Analytics</h1>
                <p class="text-sm text-ink-muted mt-1">Scores, difficulty performance, and AI confidence</p>
            </div>
            <a href="{{ route('quizzes.index') }}" class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-container transition shadow-md shrink-0">AI Quizzes</a>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="card p-5 border border-surface-low">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Quizzes</p>
                <p class="mt-2 text-3xl font-headline font-bold text-primary">{{ $totalQuizzes }}</p>
            </div>
            <div class="card p-5 border border-surface-low">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Ready</p>
                <p class="mt-2 text-3xl font-headline font-bold text-emerald-600">{{ $readyQuizzes }}</p>
            </div>
            <div class="card p-5 border border-surface-low">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Attempts</p>
                <p class="mt-2 text-3xl font-headline font-bold text-primary-container">{{ $completedAttempts }}</p>
            </div>
            <div class="card p-5 border border-surface-low">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Average</p>
                <p class="mt-2 text-3xl font-headline font-bold text-primary-container">{{ $averageScore }}%</p>
            </div>
            <div class="card p-5 border border-surface-low">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Best</p>
                <p class="mt-2 text-3xl font-headline font-bold text-accent-ink">{{ $bestScore }}%</p>
            </div>
        </div>

        {{-- Charts --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pb-8">
            <section class="card p-6">
                <h3 class="font-headline font-bold text-base text-primary">Score Trend</h3>
                <div class="mt-5 h-80">
                    <canvas id="scoreChart"></canvas>
                </div>
            </section>

            <section class="card p-6">
                <h3 class="font-headline font-bold text-base text-primary">Difficulty Accuracy</h3>
                <div class="mt-5 h-80">
                    <canvas id="difficultyChart"></canvas>
                </div>
            </section>

            <section class="card p-6 lg:col-span-2 mb-8">
                <h3 class="font-headline font-bold text-base text-primary">AI Confidence Scores</h3>
                <div class="mt-5 h-80">
                    <canvas id="confidenceChart"></canvas>
                </div>
            </section>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const scoreLabels = @json($scoreLabels);
        const scoreValues = @json($scoreValues);
        const difficultyLabels = @json($difficultyLabels);
        const difficultyAccuracy = @json($difficultyAccuracy);
        const confidenceLabels = @json($confidenceLabels);
        const confidenceValues = @json($confidenceValues);

        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        };

        new Chart(document.getElementById('scoreChart'), {
            type: 'line',
            data: {
                labels: scoreLabels,
                datasets: [{
                    label: 'Score',
                    data: scoreValues,
                    borderColor: '#002045',
                    backgroundColor: 'rgba(0, 32, 69, 0.12)',
                    tension: 0.35,
                    fill: true
                }]
            },
            options: chartOptions
        });

        new Chart(document.getElementById('difficultyChart'), {
            type: 'bar',
            data: {
                labels: difficultyLabels.map((label) => label.charAt(0).toUpperCase() + label.slice(1)),
                datasets: [{
                    label: 'Accuracy',
                    data: difficultyAccuracy,
                    backgroundColor: ['#10b981', '#fcb812', '#1a365d']
                }]
            },
            options: chartOptions
        });

        new Chart(document.getElementById('confidenceChart'), {
            type: 'bar',
            data: {
                labels: confidenceLabels,
                datasets: [{
                    label: 'AI Confidence',
                    data: confidenceValues,
                    backgroundColor: '#ffbb1e'
                }]
            },
            options: chartOptions
        });
    </script>
</x-app-layout>
