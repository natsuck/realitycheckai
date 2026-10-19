@php
    use Illuminate\Support\Str;
@endphp

<x-app-layout title="Resources">
    <div x-data="{ tab: 'exams' }"
         class="flex-grow w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-10 flex flex-col gap-8">

        {{-- Header --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 pb-4 border-b border-surface-low">
            <div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-surface-low text-primary-container text-xs font-semibold mb-2 border border-surface-high">
                    <x-icon name="book-open" class="w-3.5 h-3.5 text-accent"/>
                    <span>{{ config('app.name', 'Gabay') }} Student Resource Center</span>
                </div>
                <h1 class="font-headline text-2xl sm:text-3xl md:text-4xl font-bold text-primary">
                    Philippine College Entrance &amp; Scholarship Hub
                </h1>
                <p class="text-sm sm:text-base text-ink-muted mt-1 max-w-3xl leading-relaxed">
                    Essential guides, exam reviewer syllabi, and government scholarship portals for senior high school students.
                </p>
            </div>

            <a href="{{ auth()->check() ? route('assessment.show') : route('register') }}"
               class="px-6 py-3 rounded-xl bg-accent hover:bg-accent-hover text-primary font-bold text-xs sm:text-sm shadow-md transition-all shrink-0 flex items-center gap-2">
                <span>Take Career Assessment</span>
                <x-icon name="chevron-right" class="w-4 h-4"/>
            </a>
        </div>

        {{-- Tabs --}}
        <div class="flex flex-wrap gap-2 border-b border-slate-300/40 pb-4">
            <button type="button" @click="tab = 'exams'"
                    class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-2 transition-all"
                    :class="tab === 'exams' ? 'bg-primary text-white shadow-md' : 'bg-white text-ink-muted border border-surface-high hover:bg-surface-low'">
                <span :class="tab === 'exams' ? 'text-accent' : 'text-primary-container'">
                    <x-icon name="calendar" class="w-4 h-4"/>
                </span>
                <span>College Entrance Exams (CETs)</span>
            </button>

            <button type="button" @click="tab = 'scholarships'"
                    class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-2 transition-all"
                    :class="tab === 'scholarships' ? 'bg-primary text-white shadow-md' : 'bg-white text-ink-muted border border-surface-high hover:bg-surface-low'">
                <span :class="tab === 'scholarships' ? 'text-accent' : 'text-primary-container'">
                    <x-icon name="award" class="w-4 h-4"/>
                </span>
                <span>Scholarships & Grants (DOST/CHED)</span>
            </button>

            <button type="button" @click="tab = 'unis'"
                    class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-2 transition-all"
                    :class="tab === 'unis' ? 'bg-primary text-white shadow-md' : 'bg-white text-ink-muted border border-surface-high hover:bg-surface-low'">
                <span :class="tab === 'unis' ? 'text-accent' : 'text-primary-container'">
                    <x-icon name="graduation-cap" class="w-4 h-4"/>
                </span>
                <span>Philippine Universities Directory</span>
            </button>

            <button type="button" @click="tab = 'strands'"
                    class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-2 transition-all"
                    :class="tab === 'strands' ? 'bg-primary text-white shadow-md' : 'bg-white text-ink-muted border border-surface-high hover:bg-surface-low'">
                <span :class="tab === 'strands' ? 'text-accent' : 'text-primary-container'">
                    <x-icon name="file-text" class="w-4 h-4"/>
                </span>
                <span>SHS Strand Matching Guide</span>
            </button>
        </div>

        {{-- Exams --}}
        <div x-show="tab === 'exams'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @forelse ($collegeExams as $exam)
                <div class="card p-6 flex flex-col justify-between gap-4 group hover:border-primary-container transition-all">
                    <div>
                        <div class="flex justify-between items-start gap-2 mb-2">
                            <h3 class="font-headline text-lg font-bold text-primary">{{ $exam->title }}</h3>
                            @if ($exam->period)
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-surface-low text-primary-container shrink-0">
                                    {{ $exam->period }}
                                </span>
                            @endif
                        </div>

                        <div class="space-y-2 text-xs sm:text-sm text-ink-muted">
                            @if ($exam->coverage)
                                <div>
                                    <strong class="text-primary">Exam Coverage: </strong>
                                    {{ $exam->coverage }}
                                </div>
                            @endif
                            @if ($exam->tips)
                                <div class="p-3 bg-surface rounded-xl border border-surface-low text-xs text-primary-container">
                                    <strong class="text-primary">Reviewer Strategy: </strong>
                                    {{ $exam->tips }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-surface-low">
                        <button type="button"
                                @click="$dispatch('open-modal', { name: 'resource-detail', payload: @js(\App\Http\Controllers\ResourceController::detailPayload(
                                    $exam->title,
                                    $exam->period,
                                    [
                                        ['label' => 'Exam Coverage', 'value' => $exam->coverage],
                                        ['label' => 'Reviewer Strategy', 'value' => $exam->tips],
                                        ['label' => 'Summary', 'value' => $exam->summary],
                                    ],
                                    $exam->schools->pluck('name')->all(),
                                    $exam->link,
                                )) })"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:text-primary-container">
                            <x-icon name="search" class="w-3.5 h-3.5 text-accent"/>
                            <span>View Details</span>
                        </button>
                        @if ($exam->link)
                            <a href="{{ $exam->link }}" target="_blank" rel="noreferrer"
                               class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:text-primary-container">
                                <span>Official Portal</span>
                                <x-icon name="external-link" class="w-3.5 h-3.5 text-accent"/>
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-ink-muted">No entrance exams published yet.</p>
            @endforelse
        </div>

        {{-- Scholarships --}}
        <div x-show="tab === 'scholarships'" x-cloak class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @forelse ($scholarships as $scholarship)
                <div class="card p-6 flex flex-col justify-between gap-4">
                    <div>
                        @if ($scholarship->grantor)
                            <span class="text-[10px] uppercase font-bold tracking-wider text-accent-ink bg-accent/20 px-2 py-0.5 rounded-full inline-block mb-2">
                                {{ $scholarship->grantor }}
                            </span>
                        @endif
                        <h3 class="font-headline text-lg font-bold text-primary mb-2">{{ $scholarship->title }}</h3>

                        @if ($scholarship->opens_at || $scholarship->closes_at)
                            <div class="flex flex-wrap items-center gap-2 mb-3 text-[11px] font-semibold text-primary-container">
                                <x-icon name="calendar" class="w-3.5 h-3.5 text-accent"/>
                                @if ($scholarship->opens_at)
                                    <span>Opens {{ $scholarship->opens_at->format('M d, Y') }}</span>
                                @endif
                                @if ($scholarship->closes_at)
                                    <span class="text-ink-faint">·</span>
                                    <span>Closes {{ $scholarship->closes_at->format('M d, Y') }}</span>
                                @endif
                            </div>
                        @endif

                        @if ($scholarship->coverage)
                            <div class="p-3 bg-surface-low rounded-xl text-xs text-primary-container mb-3">
                                <strong>Benefits: </strong> {{ $scholarship->coverage }}
                            </div>
                        @endif
                        @if ($scholarship->criteria)
                            <p class="text-xs text-ink-muted">
                                <strong>Eligibility: </strong> {{ $scholarship->criteria }}
                            </p>
                        @endif

                        @if ($scholarship->schools->isNotEmpty())
                            <div class="mt-3 flex flex-wrap gap-1">
                                @foreach ($scholarship->schools as $school)
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-surface-low border border-surface-high text-primary-container">
                                        {{ $school->short_name ?? $school->name }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center justify-between">
                        <button type="button"
                                @click="$dispatch('open-modal', { name: 'resource-detail', payload: @js(\App\Http\Controllers\ResourceController::detailPayload(
                                    $scholarship->title,
                                    $scholarship->grantor,
                                    [
                                        ['label' => 'Benefits', 'value' => $scholarship->coverage],
                                        ['label' => 'Eligibility', 'value' => $scholarship->criteria],
                                        ['label' => 'Application Window', 'value' => trim(($scholarship->opens_at?->format('M d, Y') ?? '').($scholarship->closes_at ? ' – '.$scholarship->closes_at->format('M d, Y') : '')) ?: null],
                                        ['label' => 'Summary', 'value' => $scholarship->summary],
                                    ],
                                    $scholarship->schools->pluck('name')->all(),
                                    $scholarship->link,
                                )) })"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:text-primary-container">
                            <x-icon name="search" class="w-3.5 h-3.5 text-accent"/>
                            <span>Details</span>
                        </button>
                        @if ($scholarship->link)
                            <a href="{{ $scholarship->link }}" target="_blank" rel="noreferrer"
                               class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:text-primary-container">
                                <span>Apply</span>
                                <x-icon name="external-link" class="w-3.5 h-3.5 text-accent"/>
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-ink-muted">No scholarships published yet.</p>
            @endforelse
        </div>

        {{-- Universities --}}
        <div x-show="tab === 'unis'" x-cloak class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($universities as $uni)
                <div class="card p-6 flex flex-col justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold font-headline text-sm shadow-sm"
                                 style="background-color: {{ $uni->logo_bg ?: '#1e3a8a' }}">
                                {{ $uni->short_name ?? Str::upper(Str::limit($uni->title, 2, '')) }}
                            </div>
                            <div>
                                <h3 class="font-headline font-bold text-sm text-primary">{{ $uni->title }}</h3>
                                @if ($uni->location)
                                    <p class="text-xs text-ink-faint">{{ $uni->location }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="space-y-1.5 text-xs text-ink-muted">
                            @if ($uni->institution_type)<p><strong>Type: </strong>{{ $uni->institution_type }}</p>@endif
                            @if ($uni->entrance_exam)<p><strong>Admission Exam: </strong>{{ $uni->entrance_exam }}</p>@endif
                            @if ($uni->tuition_range)<p><strong>Tuition: </strong>{{ $uni->tuition_range }}</p>@endif
                            @if (! empty($uni->top_programs))
                                <p><strong>Key Programs: </strong>{{ implode(', ', $uni->top_programs) }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <button type="button"
                                @click="$dispatch('open-modal', { name: 'resource-detail', payload: @js(\App\Http\Controllers\ResourceController::detailPayload(
                                    $uni->title,
                                    $uni->institution_type,
                                    [
                                        ['label' => 'Location', 'value' => $uni->location],
                                        ['label' => 'Admission Exam', 'value' => $uni->entrance_exam],
                                        ['label' => 'Tuition', 'value' => $uni->tuition_range],
                                        ['label' => 'Key Programs', 'value' => ! empty($uni->top_programs) ? implode(', ', $uni->top_programs) : null],
                                        ['label' => 'Summary', 'value' => $uni->summary],
                                    ],
                                    [],
                                    $uni->website ?? $uni->link,
                                )) })"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:text-primary-container">
                            <x-icon name="search" class="w-3.5 h-3.5 text-accent"/>
                            <span>Details</span>
                        </button>
                        @if ($uni->website)
                            <a href="{{ $uni->website }}" target="_blank" rel="noreferrer"
                               class="inline-flex items-center gap-1 text-xs font-semibold text-primary-container hover:underline">
                                <span>University Portal</span>
                                <x-icon name="external-link" class="w-3 h-3 text-accent"/>
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-ink-muted">No universities published yet.</p>
            @endforelse
        </div>

        {{-- Strands --}}
        <div x-show="tab === 'strands'" x-cloak class="card p-6 sm:p-8 space-y-6 mb-8">
            <h3 class="font-headline text-xl font-bold text-primary">
                DepEd K-12 Senior High School Strand Cross-Alignment Matrix
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs sm:text-sm">
                @forelse ($strands as $strand)
                    <div class="p-4 rounded-xl bg-surface-low border border-surface-high">
                        <h4 class="font-bold text-sm text-primary mb-1">{{ $strand->emoji }} {{ $strand->title }}</h4>
                        @if ($strand->subtitle)<p class="text-ink-muted mb-2">{{ $strand->subtitle }}</p>@endif
                        @if ($strand->alignments)
                            <strong class="text-primary-container">Top Degree Alignments: </strong>
                            <span>{{ $strand->alignments }}</span>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-ink-muted">No strands published yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Detail Modal --}}
    <div x-data="{ payload: null }"
         @open-modal.window="if ($event.detail && $event.detail.name === 'resource-detail') payload = $event.detail.payload"
         x-show="payload" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto px-4 py-6">
        <div class="fixed inset-0 bg-gray-900/60" @click="payload = null"></div>
        <div class="relative mb-6 bg-white rounded-2xl shadow-xl sm:w-full sm:max-w-2xl sm:mx-auto overflow-hidden"
             x-show="payload" x-transition>
            <div class="p-6" x-show="payload">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <template x-if="payload && payload.badge">
                            <span class="text-[10px] uppercase font-bold tracking-wider text-accent-ink bg-accent/20 px-2 py-0.5 rounded-full inline-block mb-2"
                                  x-text="payload ? payload.badge : ''"></span>
                        </template>
                        <h3 class="font-headline text-xl font-bold text-primary" x-text="payload ? payload.title : ''"></h3>
                    </div>
                    <button type="button" @click="payload = null" class="text-ink-faint hover:text-primary">
                        <x-icon name="x" class="w-5 h-5"/>
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <template x-for="section in (payload ? payload.sections : [])" :key="section.label">
                        <div class="p-3 bg-surface-low rounded-xl border border-surface-high">
                            <p class="text-xs font-bold text-primary mb-0.5" x-text="section.label"></p>
                            <p class="text-xs sm:text-sm text-ink-muted" x-text="section.value"></p>
                        </div>
                    </template>

                    <template x-if="payload && payload.schools && payload.schools.length">
                        <div>
                            <p class="text-xs font-bold text-primary mb-1">Applicable Schools</p>
                            <div class="flex flex-wrap gap-1">
                                <template x-for="school in payload.schools" :key="school">
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-surface-low border border-surface-high text-primary-container"
                                          x-text="school"></span>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <template x-if="payload && payload.link">
                        <a :href="payload ? payload.link : '#'" target="_blank" rel="noreferrer"
                           class="inline-flex items-center gap-1.5 rounded-xl bg-accent hover:bg-accent-hover text-primary px-5 py-2 text-sm font-bold shadow-md transition-all">
                            <span>Visit Official Link</span>
                            <x-icon name="external-link" class="w-4 h-4"/>
                        </a>
                    </template>
                    <button type="button" @click="payload = null"
                            class="rounded-xl border border-surface-high bg-white px-4 py-2 text-sm font-semibold text-primary-container hover:bg-surface-low transition">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
