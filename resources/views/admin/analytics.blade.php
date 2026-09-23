<x-app-layout title="Admin Analytics">
    <div class="flex-grow flex flex-col md:flex-row w-full min-h-[calc(100vh-4rem)] bg-surface">

        {{-- Side Navigation --}}
        <aside class="w-full md:w-64 bg-primary text-white p-4 flex flex-col justify-between shrink-0 shadow-xl z-20">
            <div>
                <div class="flex items-center gap-3 mb-6 mt-2 px-2">
                    <div class="w-10 h-10 rounded-full bg-primary-container border border-slate-400 shadow-sm flex items-center justify-center font-headline font-bold">
                        {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                    </div>
                    <div>
                        <h2 class="text-lg font-headline font-bold text-accent-soft">Admin Portal</h2>
                        <p class="text-xs text-[#adc7f7]">Management Console</p>
                    </div>
                </div>

                <nav class="flex flex-col gap-1 text-xs sm:text-sm font-medium">
                    <span class="w-full text-left px-3.5 py-2.5 rounded-xl flex items-center gap-3 bg-accent text-primary font-bold shadow-sm cursor-default">
                        <x-icon name="bar-chart" class="w-4 h-4"/>
                        <span>Assessment Analytics</span>
                    </span>

                    <a href="{{ route('dashboard') }}"
                       class="w-full text-left px-3.5 py-2.5 rounded-xl flex items-center gap-3 transition-all text-[#d6e3ff] hover:bg-primary-container hover:text-white">
                        <x-icon name="layout-dashboard" class="w-4 h-4"/>
                        <span>Dashboard</span>
                    </a>

                    <a href="{{ route('quiz-analytics.index') }}"
                       class="w-full text-left px-3.5 py-2.5 rounded-xl flex items-center gap-3 transition-all text-[#d6e3ff] hover:bg-primary-container hover:text-white">
                        <x-icon name="file-text" class="w-4 h-4"/>
                        <span>Quiz Analytics</span>
                    </a>
                </nav>
            </div>

            <div class="pt-4 mt-6 border-t border-primary-container space-y-1">
                <a href="{{ route('resources.index') }}"
                   class="w-full text-left px-3 py-2 rounded-lg text-xs text-accent-soft hover:bg-primary-container flex items-center gap-2 font-medium">
                    <x-icon name="book-open" class="w-3.5 h-3.5 text-accent"/>
                    <span>Student Resources</span>
                </a>

                <form method="POST" action="{{ route('logout') }}" x-data>
                    @csrf
                    <button type="submit"
                            onclick="event.preventDefault(); this.closest('form').submit();"
                            class="w-full text-left px-3 py-2 rounded-lg text-xs text-rose-300 hover:bg-primary-container flex items-center gap-2">
                        <x-icon name="log-out" class="w-3.5 h-3.5"/>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main Content --}}
        <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto overflow-y-auto">

            <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                <div>
                    <h2 class="font-headline text-2xl sm:text-3xl font-bold text-primary">
                        Dashboard Overview
                    </h2>
                    <p class="text-xs sm:text-sm text-ink-muted mt-0.5">
                        Welcome back. Here is the latest assessment data across your students.
                    </p>
                </div>
            </header>

            {{-- Stats Bento --}}
            <section class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
                <div class="card p-5 sm:p-6 flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold text-ink-muted mb-1">Total Assessments</p>
                        <p class="font-headline text-3xl sm:text-4xl font-bold text-primary">{{ number_format($totalAssessments) }}</p>
                        <p class="text-xs text-emerald-700 font-semibold mt-2 flex items-center gap-1">
                            <x-icon name="users" class="w-3.5 h-3.5"/> {{ number_format($uniqueStudents) }} unique students
                        </p>
                    </div>
                    <div class="p-3 bg-surface-low rounded-xl text-primary">
                        <x-icon name="graduation-cap" class="w-6 h-6"/>
                    </div>
                </div>

                <div class="card p-5 sm:p-6 flex items-start justify-between relative overflow-hidden">
                    <div class="absolute right-0 top-0 w-28 h-28 bg-accent/10 rounded-bl-full pointer-events-none"></div>
                    <div>
                        <p class="text-xs font-semibold text-ink-muted mb-1">AI Availability Rate</p>
                        <p class="font-headline text-3xl sm:text-4xl font-bold text-primary">{{ $aiAvailabilityRate }}%</p>
                        <p class="text-xs text-ink-faint mt-2">{{ number_format($aiGeneratedCount) }} AI &middot; {{ number_format($fallbackCount) }} fallback</p>
                    </div>
                    <div class="p-3 bg-accent rounded-xl text-primary">
                        <x-icon name="brain" class="w-6 h-6"/>
                    </div>
                </div>

                <div class="card p-5 sm:p-6 flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold text-ink-muted mb-1">Avg. Confidence</p>
                        <p class="font-headline text-3xl sm:text-4xl font-bold text-primary">{{ number_format($avgConfidence, 1) }}%</p>
                        <p class="text-xs text-ink-faint mt-2 leading-relaxed">LLM self-report, not calibrated.</p>
                    </div>
                    <div class="p-3 bg-surface-low rounded-xl text-primary">
                        <x-icon name="award" class="w-6 h-6"/>
                    </div>
                </div>
            </section>

            {{-- Flagged responses --}}
            @if ($flaggedCount > 0)
                <section class="mb-8 rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:p-5 flex items-start gap-3">
                    <x-icon name="alert-triangle" class="w-5 h-5 text-amber-500 shrink-0 mt-0.5"/>
                    <div class="text-sm text-amber-900">
                        <span class="font-bold">{{ number_format($flaggedCount) }}</span>
                        {{ str_plural('assessment', $flaggedCount) }} with response-validity flags (uniform answers or implausibly fast completion). Review before relying on these recommendations.
                    </div>
                </section>
            @endif

            {{-- Distribution + recent submissions --}}
            <section x-data="{ q: '' }" class="grid grid-cols-1 lg:grid-cols-5 gap-6 mb-8">

                {{-- Program distribution --}}
                <div class="card p-6 lg:col-span-2 h-fit">
                    <div class="flex items-center gap-2 mb-1">
                        <x-icon name="trending-up" class="w-5 h-5 text-primary-container"/>
                        <h4 class="font-headline text-lg font-bold text-primary">Top Matched Programs</h4>
                    </div>
                    <p class="text-xs text-ink-faint mb-4">Distribution of recommendations across all submissions.</p>

                    <div class="space-y-3 mt-4">
                        @forelse ($programBreakdown as $row)
                            <div class="space-y-1">
                                <div class="flex justify-between text-xs font-semibold text-primary">
                                    <span class="truncate pr-2">{{ $row->program_code }} · {{ $row->program_name }}</span>
                                    <span>{{ $row->percentage }}% ({{ $row->total }})</span>
                                </div>
                                <div class="w-full bg-surface-low h-2 rounded-full overflow-hidden">
                                    <div class="bg-primary-container h-full rounded-full" style="width: {{ $row->percentage }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-ink-faint">No assessment data yet.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Recent submissions --}}
                <div class="card lg:col-span-3 overflow-hidden h-fit">
                    <div class="p-5 sm:p-6 border-b border-surface-low flex flex-wrap gap-3 justify-between items-center bg-surface">
                        <div>
                            <h3 class="font-headline text-lg sm:text-xl font-bold text-primary">Recent Submissions</h3>
                            <p class="text-xs text-ink-faint">Real-time student assessment logs</p>
                        </div>
                        <span class="text-xs font-semibold text-primary-container bg-surface-low px-3 py-1 rounded-full border border-surface-high">
                            {{ $recentResults->count() }} records
                        </span>
                    </div>

                    <div class="px-5 pt-4 pb-2 hidden sm:block">
                        <div class="relative">
                            <x-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-ink-faint"/>
                            <input type="text" x-model="q" placeholder="Search students..."
                                   class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-300 text-xs sm:text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary-container/20 focus:border-primary-container">
                        </div>
                    </div>

                    {{-- Desktop table --}}
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-surface-low text-ink-muted text-xs font-semibold uppercase tracking-wider border-b border-surface-high">
                                    <th class="py-3 px-6">Student</th>
                                    <th class="py-3 px-6">Date Completed</th>
                                    <th class="py-3 px-6">Primary Trait</th>
                                    <th class="py-3 px-4 text-right">Trait %</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-low text-sm">
                                @forelse ($recentResults as $result)
                                    @php
                        $searchable = mb_strtolower(($result->user->name ?? '').' '.($result->user->email ?? '').' '.($result->strongest_trait ?? '').' '.($result->program_code ?? ''));
                        $score = \App\Http\Controllers\AssessmentController::computeAptitude($result->category_summary);
                                    @endphp
                                    <tr x-show="q === '' || (`{{ $searchable }}`.includes(q.toLowerCase()))" class="hover:bg-surface transition-colors">
                                        <td class="py-4 px-6">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center border border-slate-300 shrink-0">
                                                    {{ strtoupper(substr($result->user->name ?? '?', 0, 1)) }}
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-primary">{{ $result->user->name ?? 'Unknown' }}</p>
                                                    <p class="text-xs text-ink-faint">{{ $result->user->email ?? '' }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 text-ink-muted text-xs">{{ $result->completed_at?->format('M d, Y g:i A') }}</td>
                                        <td class="py-4 px-6">
                                            @if ($result->strongest_trait)
                                                <span class="bg-surface-low text-primary px-3 py-1 rounded-full text-xs font-semibold border border-surface-high">
                                                    {{ $result->strongest_trait }}
                                                </span>
                                            @else
                                                <span class="text-xs text-ink-faint">&mdash;</span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-4 text-right font-bold text-primary">
                                            {{ $score !== null ? $score.'%' : '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-8 px-6 text-center text-sm text-ink-faint">No submissions recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Mobile list --}}
                    <div class="sm:hidden divide-y divide-surface-low">
                        @foreach ($recentResults as $result)
                            <div class="p-4 flex items-center justify-between hover:bg-surface">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0">
                                        {{ strtoupper(substr($result->user->name ?? '?', 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-primary text-sm truncate">{{ $result->user->name ?? 'Unknown' }}</p>
                                        <p class="text-xs text-ink-muted truncate">{{ $result->program_code }} · {{ $result->program_name }}</p>
                                        <span class="text-[10px] text-ink-faint">{{ $result->completed_at?->format('M d, Y') }}</span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="inline-block px-2.5 py-0.5 rounded-full bg-surface-low text-primary font-bold text-xs">
                                        {{ \App\Http\Controllers\AssessmentController::computeAptitude($result->category_summary) ?? '--' }}%
                                    </span>
                                    @if ($result->source === 'fallback')
                                        <p class="text-[10px] text-ink-faint mt-1">offline estimate</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <p class="text-xs text-ink-faint leading-relaxed">
                Confidence values reflect the recommendation engine's self-reported certainty and are guidance signals only &mdash; not calibrated probabilities.
            </p>
        </main>
    </div>
</x-app-layout>
