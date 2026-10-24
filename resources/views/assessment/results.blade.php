@php
    $flagLabels = \App\Http\Controllers\AssessmentController::responseFlagLabels();

    $universities = [
        [
            'shortName' => 'UP',
            'name' => 'University of the Philippines',
            'location' => 'Diliman, Quezon City / Manila',
            'type' => 'State University',
            'topPrograms' => ['BS Computer Science', 'BS Statistics', 'BS Molecular Biology', 'BS Civil Engineering'],
            'entranceExam' => 'UPCAT (University of the Philippines College Admission Test)',
            'tuitionRange' => 'Free Higher Education Act (Public/Subsidized)',
            'website' => 'https://up.edu.ph',
            'logoBg' => '#7b1113',
        ],
        [
            'shortName' => 'ADMU',
            'name' => 'Ateneo de Manila University',
            'location' => 'Loyola Heights, Quezon City',
            'type' => 'Private University',
            'topPrograms' => ['BS Management Information Systems', 'BS Computer Science', 'BS Applied Mathematics'],
            'entranceExam' => 'ACET (Ateneo College Entrance Test)',
            'tuitionRange' => '₱90,000 - ₱120,000 per sem (Scholarships available)',
            'website' => 'https://ateneo.edu',
            'logoBg' => '#002f6c',
        ],
        [
            'shortName' => 'DLSU',
            'name' => 'De La Salle University',
            'location' => 'Taft Avenue, Manila / Laguna',
            'type' => 'Private University',
            'topPrograms' => ['BS Computer Science (Software Tech)', 'BS Information Technology', 'BS Accountancy'],
            'entranceExam' => 'DCAT (De La Salle College Admission Test)',
            'tuitionRange' => '₱85,000 - ₱115,000 per term (Financial assistance available)',
            'website' => 'https://dlsu.edu.ph',
            'logoBg' => '#00703c',
        ],
        [
            'shortName' => 'UST',
            'name' => 'University of Santo Tomas',
            'location' => 'España Blvd, Sampaloc, Manila',
            'type' => 'Private University',
            'topPrograms' => ['BS Information Systems', 'BS Medical Technology', 'BS Nursing', 'BS Architecture'],
            'entranceExam' => 'USTET (UST Entrance Test)',
            'tuitionRange' => '₱60,000 - ₱80,000 per sem',
            'website' => 'https://ust.edu.ph',
            'logoBg' => '#d4a017',
        ],
        [
            'shortName' => 'PUP',
            'name' => 'Polytechnic University of the Philippines',
            'location' => 'Sta. Mesa, Manila',
            'type' => 'State University',
            'topPrograms' => ['BS Information Technology', 'BS Computer Engineering', 'BS Accountancy'],
            'entranceExam' => 'PUPCET',
            'tuitionRange' => 'Free Higher Education Act (Subsidized)',
            'website' => 'https://pup.edu.ph',
            'logoBg' => '#800000',
        ],
        [
            'shortName' => 'MAPUA',
            'name' => 'Mapúa University',
            'location' => 'Intramuros, Manila / Makati',
            'type' => 'Private University',
            'topPrograms' => ['BS Data Science', 'BS Computer Science', 'BS Electrical Engineering'],
            'entranceExam' => 'Mapúa Scholastic Assessment (MSA)',
            'tuitionRange' => '₱45,000 - ₱60,000 per quarter',
            'website' => 'https://mapua.edu.ph',
            'logoBg' => '#c8102e',
        ],
    ];

    $traitSummary = trim((string) ($aiFeedback['reason'] ?? '')) !== ''
        ? $aiFeedback['reason']
        : ($aiFeedback['feedback'] ?? 'Complete the assessment to generate your trait analysis.');

    $strengthChips = collect($aiFeedback['strengths'] ?? [])->take(3);
@endphp

<x-app-layout title="My Results">
    <div x-data="{ copied: false, expandedUni: null }"
         class="flex-grow w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-10 flex flex-col gap-8">

        {{-- Header --}}
        <section class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 pb-2 border-b border-surface-low">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="text-xs uppercase font-bold tracking-wider px-2.5 py-0.5 rounded-full bg-accent/20 text-accent-ink border border-accent/40">
                        {{ $isVersionedResult ? 'Exploratory AI Guidance' : 'Verified '.config('app.name', 'Gabay').' Assessment' }}
                    </span>
                    @if ($completedAt)
                        <span class="text-xs text-ink-faint">Completed {{ $completedAt }}</span>
                    @endif

                    @if (($aiFeedback['source'] ?? null) === 'fallback')
                        <span class="text-xs px-2.5 py-0.5 rounded-full bg-slate-100 text-ink-muted border border-slate-200 font-semibold">
                            Offline estimate
                        </span>
                    @else
                        <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-semibold">
                            AI-generated
                        </span>
                    @endif
                </div>

                <h1 class="font-headline text-2xl sm:text-3xl md:text-4xl font-bold text-primary">
                    {{ $isVersionedResult ? 'Your Career Interest and Aptitude Profile' : 'Your Personality Profile & Career Insights.' }}
                </h1>
                <p class="text-sm sm:text-base text-ink-muted max-w-3xl mt-1 leading-relaxed">
                    @if ($isVersionedResult)
                        Your RIASEC interests and scholastic aptitude scores are shown separately. Program suggestions below are AI-generated possibilities based on your scores and the program descriptions; they are not an approved or validated NCAE mapping.
                    @else
                        Based on your recent assessment, we've compiled a comprehensive overview of your strengths,
                        working style, and ideal career paths to help guide your academic journey in the Philippines.
                    @endif
                </p>
            </div>

            <div class="flex items-center gap-2.5 shrink-0 w-full sm:w-auto">
                <a href="{{ route('assessment.show') }}"
                   class="flex-1 sm:flex-initial px-4 py-2 rounded-xl border border-slate-300 hover:bg-surface-low text-primary font-semibold text-xs sm:text-sm transition-all flex items-center justify-center gap-1.5">
                    <x-icon name="rotate-ccw" class="w-3.5 h-3.5"/>
                    <span>{{ __('Retake') }}</span>
                </a>

                <button type="button" x-cloak
                        @click="navigator.clipboard && navigator.clipboard.writeText(window.location.href).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                        class="flex-1 sm:flex-initial px-4 py-2 rounded-xl bg-surface-low hover:bg-surface-high text-primary-container font-semibold text-xs sm:text-sm transition-all flex items-center justify-center gap-1.5">
                    <x-icon name="check-circle" class="w-3.5 h-3.5 text-emerald-600" x-show="copied" x-cloak/>
                    <x-icon name="share-2" class="w-3.5 h-3.5" x-show="! copied"/>
                    <span x-text="copied ? 'Link Copied' : 'Share Profile'"></span>
                </button>
            </div>
        </section>

        {{-- Response validity notice --}}
        @if (! empty($responseFlags))
            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:p-5">
                <div class="flex items-start gap-3">
                    <x-icon name="alert-triangle" class="w-5 h-5 text-amber-500 shrink-0 mt-0.5"/>
                    <div class="text-sm text-amber-900">
                        <p class="font-bold">Response pattern notice</p>
                        <ul class="list-disc list-inside mt-1 space-y-0.5 text-xs sm:text-sm">
                            @foreach ($responseFlags as $flag)
                                <li>{{ $flagLabels[$flag] ?? ucfirst(str_replace('_', ' ', $flag)) }}</li>
                            @endforeach
                        </ul>
                        <p class="mt-1 text-xs">Consider reviewing your answers with a school counselor.</p>
                    </div>
                </div>
            </section>
        @endif

        @if ($isVersionedResult)
            @if ($scoreBreakdown)
            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:p-5 text-sm text-amber-900">
                <p class="font-bold">Exploratory suggestions—not a placement decision</p>
                <p class="mt-1">These AI-generated matches are not a validated NCAE score-to-program mapping or a prediction of academic success. They do not assign or dictate your choice. Your interests, goals, and decision come first; discuss the profile with a counselor.</p>
            </section>

            <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="card p-6 sm:p-8">
                    <h2 class="font-headline text-lg sm:text-xl font-bold text-primary mb-4">Career Interest Inventory (RIASEC)</h2>
                    <p class="text-xs text-ink-muted mb-4">Each scale contains 15 items rated from 1 to 5. Scores are your item averages, not a comparison with other students.</p>
                    <div class="space-y-3">
                        @foreach ($scoreBreakdown['interest'] as $score)
                            <div>
                                <div class="flex justify-between gap-2 text-sm mb-1">
                                    <span class="font-semibold text-primary">{{ $score['label'] }}</span>
                                    <span class="text-ink-muted">{{ number_format((float) $score['average'], 2) }} / 5</span>
                                </div>
                                <div class="h-2 rounded-full bg-surface-low overflow-hidden">
                                    <div class="h-full rounded-full bg-primary-container" style="width: {{ min(100, max(0, (float) $score['percentage'])) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="card p-6 sm:p-8">
                    <h2 class="font-headline text-lg sm:text-xl font-bold text-primary mb-4">Scholastic Aptitude</h2>
                    <p class="text-xs text-ink-muted mb-4">Each section shows the number and percentage of correct answers. These results are reported separately from career interests.</p>
                    <div class="space-y-4">
                        @foreach ($scoreBreakdown['aptitude'] as $score)
                            <div class="rounded-xl border border-surface-high p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <span class="font-semibold text-primary">{{ $score['label'] }}</span>
                                    <span class="font-bold text-primary-container">{{ $score['correct'] }} / {{ $score['total'] }}</span>
                                </div>
                                <div class="mt-2 h-2 rounded-full bg-surface-low overflow-hidden">
                                    <div class="h-full rounded-full bg-accent" style="width: {{ min(100, max(0, (float) $score['percentage'])) }}%"></div>
                                </div>
                                <p class="mt-1 text-right text-xs text-ink-muted">{{ number_format((float) $score['percentage'], 0) }}% correct</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="card p-6 sm:p-8 flex flex-col gap-4">
                <div class="flex items-center gap-2.5 text-primary">
                    <div class="p-2 rounded-lg bg-surface-low text-primary-container">
                        <x-icon name="brain" class="w-5 h-5"/>
                    </div>
                    <h2 class="font-headline text-xl sm:text-2xl font-bold">AI Profile Explanation</h2>
                </div>
                <p class="text-sm sm:text-base text-ink-muted leading-relaxed">{{ $traitSummary }}</p>
                @if (! empty($aiFeedback['strengths']))
                    <div>
                        <h3 class="text-sm font-bold text-primary mb-2">Evidence-based observations</h3>
                        <ul class="list-disc list-inside space-y-1 text-sm text-ink-muted">
                            @foreach ($aiFeedback['strengths'] as $strength)
                                <li>{{ $strength }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>

            <section class="card p-6 sm:p-8">
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-2 mb-5">
                    <div>
                        <h2 class="font-headline text-lg sm:text-xl font-bold text-primary">Programs to Explore</h2>
                        <p class="text-xs text-ink-muted mt-1">Ranked AI suggestions based on your profile—not a required or definitive choice.</p>
                    </div>
                    <span class="text-[11px] font-semibold text-amber-800 bg-amber-50 border border-amber-200 rounded-full px-3 py-1">Exploratory; not school-validated</span>
                </div>
                @if (! empty($aiFeedback['program_matches']))
                    <p class="mb-4 rounded-xl border border-accent/30 bg-accent/10 p-3 text-sm text-accent-ink">
                        <strong>AI-generated program mapping.</strong> The AI ranks these programs using your RIASEC profile, separate aptitude results, and the program descriptions. This is an exploratory suggestion—not a validated placement, admission decision, or instruction. Your choice comes first.
                    </p>
                    <div class="space-y-3">
                        @foreach ($aiFeedback['program_matches'] as $index => $match)
                            <article class="rounded-xl border border-surface-high p-4">
                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2">
                                    <h3 class="font-semibold text-primary">
                                        <span class="text-ink-faint mr-1">{{ $index + 1 }}.</span>
                                        {{ $match['name'] }}
                                    </h3>
                                    <span class="shrink-0 text-[10px] uppercase font-bold tracking-wide text-amber-800">AI-ranked possibility</span>
                                </div>
                                <p class="text-sm text-ink-muted mt-2">{{ $match['reason'] }}</p>
                                @if (! empty($match['evidence']))
                                    <ul class="list-disc list-inside text-xs text-ink-muted mt-2 space-y-1">
                                        @foreach ($match['evidence'] as $evidence)
                                            <li>{{ $evidence }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                                @if (! empty($match['explore']))
                                    <p class="text-xs text-primary-container mt-3"><span class="font-bold">Explore:</span> {{ $match['explore'] }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-ink-muted">AI program suggestions are unavailable for this attempt. Your score profile is still available to discuss with a counselor.</p>
                @endif
            </section>
            @else
                <section class="rounded-2xl border border-rose-200 bg-rose-50 p-4 sm:p-5 text-sm text-rose-900">
                    The saved score profile is unavailable. Please contact your school counselor for assistance.
                </section>
            @endif
        @else
        {{-- Bento: AI Trait Analysis + Aptitude --}}
        <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2 card p-6 sm:p-8 flex flex-col gap-4 relative overflow-hidden group hover:shadow-ambient-lg transition-shadow">
                <div class="absolute top-0 right-0 w-36 h-36 bg-accent/10 rounded-bl-full pointer-events-none group-hover:scale-105 transition-transform"></div>

                <div class="flex items-center gap-2.5 text-primary">
                    <div class="p-2 rounded-lg bg-surface-low text-primary-container">
                        <x-icon name="brain" class="w-5 h-5"/>
                    </div>
                    <h2 class="font-headline text-xl sm:text-2xl font-bold">AI Trait Analysis</h2>
                </div>

                <p class="text-sm sm:text-base text-ink-muted leading-relaxed">{{ $traitSummary }}</p>

                <div class="flex flex-wrap gap-2 mt-auto pt-4 border-t border-surface-low">
                    @if ($strengthChips->isNotEmpty())
                        @foreach ($strengthChips as $chip)
                            <span class="px-3.5 py-1 bg-surface-low text-primary rounded-full text-xs sm:text-sm font-semibold border border-surface-high">
                                {{ $chip }}
                            </span>
                        @endforeach
                    @endif

                    @if (! empty($aiFeedback['fit_level']))
                        <span class="px-3.5 py-1 bg-surface-low text-primary rounded-full text-xs sm:text-sm font-semibold border border-surface-high flex items-center gap-1">
                            <x-icon name="check-circle" class="w-3 h-3 text-emerald-600"/>
                            {{ $aiFeedback['fit_level'] }}
                        </span>
                    @endif

                    <span class="px-3.5 py-1 bg-accent/10 text-accent-ink rounded-full text-xs sm:text-sm font-semibold border border-accent/30 flex items-center gap-1">
                        <x-icon name="sparkles" class="w-3 h-3 text-accent"/>
                        {{ __('Top Trait') }}: {{ $strongestTrait ?? 'N/A' }}
                    </span>
                </div>
            </div>

            {{-- Aptitude Score Radial --}}
            <div class="card p-6 sm:p-8 flex flex-col items-center justify-center text-center gap-4 relative overflow-hidden">
                <div class="w-28 h-28 rounded-full bg-surface-low flex items-center justify-center border-4 border-accent shadow-sm">
                    <span class="font-headline text-3xl font-extrabold text-primary">
                        {{ $aptitude['score'] ?? '--' }}%
                    </span>
                </div>
                <div>
                    <h3 class="font-headline text-lg sm:text-xl font-bold text-primary">Aptitude Score</h3>
                    <p class="text-xs sm:text-sm text-ink-muted mt-1 font-medium">
                        {{ $aptitude['ranking'] ?? 'Complete the assessment to compute a score.' }}
                    </p>
                    <p class="text-[11px] text-ink-faint mt-2 leading-relaxed">
                        Weighted composite of your category strengths.
                    </p>
                </div>
            </div>
        </section>

        {{-- Charts row --}}
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Radar --}}
            <div class="card p-6 flex flex-col">
                <div class="flex items-center gap-2 text-primary mb-4">
                    <x-icon name="radar" class="w-5 h-5 text-primary-container"/>
                    <h2 class="font-headline text-lg sm:text-xl font-bold">Category Strengths</h2>
                </div>

                <div class="h-64 sm:h-72 w-full">
                    @if (! empty($summary))
                        <x-radar-chart
                            :data="collect($summary)->map(fn ($row) => ['name' => $row['label'] ?? $row['category'] ?? '', 'value' => (float) ($row['percentage'] ?? 0)])->all()"/>
                    @else
                        <div class="h-full flex items-center justify-center text-sm text-ink-faint">
                            No category summary available yet.
                        </div>
                    @endif
                </div>

                <div class="text-center text-[11px] text-ink-faint mt-2">
                    Percentage agreement per assessment category (0-100).
                </div>
            </div>

            {{-- Compatibility bars --}}
            <div class="card p-6 flex flex-col">
                <div class="flex items-center gap-2 text-primary mb-4">
                    <x-icon name="bar-chart" class="w-5 h-5 text-primary-container"/>
                    <h2 class="font-headline text-lg sm:text-xl font-bold">Course Compatibility (%)</h2>
                </div>

                @if ($programMatches)
                    <div class="flex-1 flex items-end justify-around gap-2 sm:gap-4 min-h-[16rem] pt-4 pb-1">
                        @foreach ($programMatches as $match)
                            <div class="flex flex-col items-center justify-end gap-2 h-full flex-1 max-w-[72px]">
                                <span class="text-[11px] font-bold {{ $match['is_top_match'] ? 'text-accent-ink' : 'text-primary' }}">
                                    {{ $match['compatibility'] }}%
                                </span>
                                <div
                                    class="w-full rounded-t-md transition-all {{ $match['is_top_match'] ? 'bg-accent' : 'bg-primary' }}"
                                    style="height: {{ max(6, $match['compatibility']) }}%"
                                    title="{{ $match['name'] }}"></div>
                                <span class="text-[10px] sm:text-[11px] font-semibold text-ink-muted text-center leading-tight">
                                    {{ $match['code'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex-1 flex items-center justify-center text-sm text-ink-faint">
                        Take the assessment to see program matches.
                    </div>
                @endif

                <div class="flex items-center justify-center gap-4 text-xs mt-2">
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-sm bg-accent"></span>
                        <span class="text-ink-muted">Top Matched Course</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-sm bg-primary"></span>
                        <span class="text-ink-muted">Secondary Pathways</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- Recommendations row --}}
        <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Recommended courses --}}
            <div class="card p-6 sm:p-8 border-t-4 !border-t-accent flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-primary">
                        <x-icon name="graduation-cap" class="w-5 h-5 text-accent-ink"/>
                        <h2 class="font-headline text-lg sm:text-xl font-bold">Recommended Courses</h2>
                    </div>
                    <span class="text-xs text-ink-faint">CHED Recognized</span>
                </div>

                <div class="flex flex-col gap-3">
                    @forelse (array_slice($programMatches, 0, 3) as $index => $course)
                        <div class="p-4 rounded-xl border transition-all flex flex-col gap-2 group {{ $index === 0 ? 'bg-surface-low border-primary-container/40 shadow-sm' : 'bg-white border-slate-300 hover:border-primary-container' }}">
                            <div class="flex justify-between items-start">
                                <div>
                                    @if ($loop->first)
                                        <span class="text-[10px] uppercase font-bold tracking-wider text-accent-ink bg-accent/20 px-2 py-0.5 rounded-full inline-block mb-1">
                                            &#11088; Top Match @if ($course['is_ai_pick'] ?? false)
                                                &middot; AI Pick
                                            @endif
                                        </span>
                                    @endif
                                    <h4 class="font-headline text-base sm:text-lg font-bold text-primary group-hover:text-primary-container">
                                        {{ $course['name'] }}
                                    </h4>
                                </div>
                                <span class="px-2.5 py-1 rounded-full bg-primary-container text-white text-xs font-bold shrink-0">
                                    {{ $course['compatibility'] }}%
                                </span>
                            </div>

                            <p class="text-xs text-ink-muted leading-relaxed">{{ $course['description'] }}</p>

                            <div class="flex flex-wrap items-center gap-1.5 pt-1 text-[11px] text-primary-container">
                                <span class="font-semibold text-primary">Careers:</span>
                                {{ implode(' • ', array_slice($course['careers'], 0, 3)) }}
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-ink-faint py-6 text-center">
                            Complete the assessment to receive course recommendations.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Suggested universities --}}
            <div class="card p-6 sm:p-8 border-t-4 !border-t-primary flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-primary">
                        <x-icon name="building" class="w-5 h-5 text-primary-container"/>
                        <h2 class="font-headline text-lg sm:text-xl font-bold">Suggested Universities</h2>
                    </div>
                    <span class="text-xs text-ink-faint">Top Philippine Campuses</span>
                </div>

                <ul class="flex flex-col divide-y divide-surface-low">
                    @foreach (array_slice($universities, 0, 4) as $uni)
                        <li class="py-2.5 transition-colors">
                            <button type="button"
                                    @click="expandedUni = expandedUni === '{{ $uni['shortName'] }}' ? null : '{{ $uni['shortName'] }}'"
                                    class="w-full flex items-center gap-3.5 hover:bg-surface-low p-2 rounded-xl cursor-pointer group text-left">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-headline font-bold text-xs shrink-0 shadow-sm"
                                     style="background-color: {{ $uni['logoBg'] }}">
                                    {{ $uni['shortName'] }}
                                </div>
                                <div class="flex-grow min-w-0">
                                    <h4 class="font-headline text-sm font-bold text-primary truncate">{{ $uni['name'] }}</h4>
                                    <p class="text-xs text-ink-faint">{{ $uni['location'] }}</p>
                                </div>
                                <x-icon name="chevron-down"
                                        class="w-4 h-4 text-ink-faint group-hover:text-primary transition-transform"
                                        x-bind:class="expandedUni === '<?php echo e($uni['shortName']); ?>' ? 'rotate-180' : ''"/>
                            </button>

                            <div x-show="expandedUni === '{{ $uni['shortName'] }}'" x-cloak x-transition
                                 class="mt-2 p-3.5 bg-surface rounded-xl border border-surface-high text-xs space-y-2">
                                <div>
                                    <span class="font-semibold text-primary">Top Programs: </span>
                                    <span class="text-ink-muted">{{ implode(', ', $uni['topPrograms']) }}</span>
                                </div>
                                <div>
                                    <span class="font-semibold text-primary">Entrance Exam: </span>
                                    <span class="text-ink-muted">{{ $uni['entranceExam'] }}</span>
                                </div>
                                <div>
                                    <span class="font-semibold text-primary">Tuition: </span>
                                    <span class="text-ink-muted">{{ $uni['tuitionRange'] }}</span>
                                </div>
                                <a href="{{ $uni['website'] }}" target="_blank" rel="noreferrer"
                                   class="inline-flex items-center gap-1 font-semibold text-primary-container hover:underline pt-1">
                                    <span>Visit Official Admissions Page</span>
                                    <x-icon name="external-link" class="w-3 h-3"/>
                                </a>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <a href="{{ route('resources.index') }}"
                   class="mt-auto pt-2 text-primary font-semibold text-xs sm:text-sm hover:underline flex items-center justify-center gap-1 w-full">
                    <span>View All Philippine University Requirements</span>
                    <x-icon name="arrow-up-right" class="w-4 h-4"/>
                </a>
            </div>
        </section>
        @endif

        {{-- Next steps + disclaimer --}}
        <section class="card p-6 sm:p-8 flex flex-col gap-4">
            <div class="flex items-center gap-2 text-primary">
                <x-icon name="compass" class="w-5 h-5 text-primary-container"/>
                <h2 class="font-headline text-lg sm:text-xl font-bold">Recommended Next Steps</h2>
            </div>

            <ol class="grid sm:grid-cols-3 gap-3 list-none">
                @forelse ($aiFeedback['next_steps'] ?? [] as $step)
                    <li class="flex items-start gap-2.5 p-3.5 bg-surface-low rounded-xl border border-surface-high text-xs sm:text-sm text-primary-container">
                        <span class="w-5 h-5 shrink-0 rounded-full bg-primary text-white text-[10px] font-bold flex items-center justify-center mt-0.5">{{ $loop->iteration }}</span>
                        {{ $step }}
                    </li>
                @empty
                    <li class="text-sm text-ink-faint">No next steps recorded.</li>
                @endempty
            </ol>

            <p class="text-xs text-ink-faint leading-relaxed border-t border-surface-low pt-3">
                {{ $disclaimer }}
            </p>
        </section>

        {{-- History --}}
        @if ($history->isNotEmpty())
            <section class="card overflow-hidden">
                <div class="p-5 sm:p-6 border-b border-surface-low flex justify-between items-center bg-surface">
                    <div>
                        <h3 class="font-headline text-lg sm:text-xl font-bold text-primary">Assessment History</h3>
                        <p class="text-xs text-ink-faint">Your previous recommendations</p>
                    </div>
                    <span class="text-xs font-semibold text-primary-container bg-surface-low px-3 py-1 rounded-full border border-surface-high">
                        {{ $history->count() }} records
                    </span>
                </div>

                <div class="divide-y divide-surface-low">
                    @foreach ($history as $past)
                        <div class="p-4 sm:p-5 flex flex-wrap items-center justify-between gap-3 hover:bg-surface transition-colors">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-xl bg-primary-container/10 text-primary-container flex items-center justify-center shrink-0">
                                    <x-icon name="graduation-cap" class="w-4 h-4"/>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-sm text-primary truncate">{{ $past->program_name }}</p>
                                    <p class="text-xs text-ink-faint">{{ $past->completed_at?->format('M d, Y g:i A') }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                @if ($past->strongest_trait)
                                    <span class="bg-surface-low text-primary px-3 py-1 rounded-full text-xs font-semibold border border-surface-high">
                                        {{ $past->strongest_trait }}
                                    </span>
                                @endif

                                @if ($past->instrument_version)
                                    <span class="px-2.5 py-1 rounded-full bg-surface-low text-primary-container text-xs font-bold">
                                        Score profile
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full bg-primary-container text-white text-xs font-bold">
                                        {{ number_format((float) $past->confidence, 0) }}% conf.
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>

@if (session('just_completed'))
    @once
        <script>
            window.addEventListener('load', () => window.gabayConfetti && window.gabayConfetti());
        </script>
    @endonce
@endif

@if ($isVersionedResult)
    <script>
        try {
            sessionStorage.removeItem(@js('aguhon-assessment-'.\App\Models\Question::CURRENT_INSTRUMENT_VERSION.'-'.auth()->id()));
        } catch (error) {
            // Session storage may be unavailable; the completed result is unaffected.
        }
    </script>
@endif
