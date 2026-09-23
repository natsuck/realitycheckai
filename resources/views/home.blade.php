<x-app-layout title="Home">
    <div class="flex-grow flex flex-col items-center justify-center w-full">
        <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12 flex flex-col gap-12 md:gap-20">

            {{-- Hero --}}
            <section class="w-full py-12 md:py-20 flex flex-col items-center text-center relative overflow-hidden rounded-2xl bg-surface-low shadow-ambient border border-surface-high/60 px-4 sm:px-8">

                <div
                    class="absolute inset-0 opacity-15 pointer-events-none"
                    style="background-image: radial-gradient(circle at center, #1a365d 1px, transparent 1px); background-size: 24px 24px;"
                ></div>

                <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-primary/5 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -top-24 -right-24 w-80 h-80 bg-accent/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 max-w-3xl flex flex-col items-center">
                    <div class="inline-flex items-center justify-center bg-surface-high text-primary-container px-4 py-1.5 rounded-full mb-6 text-xs sm:text-sm font-semibold border border-slate-300/50 shadow-sm gap-2">
                        <x-icon name="sparkles" class="w-4 h-4 text-accent fill-accent"/>
                        <span>Empowering Filipino Students</span>
                    </div>

                    <h1 class="font-headline text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-extrabold text-primary mb-6 tracking-tight leading-[1.15]">
                        Discover Your Path to
                        <span class="text-accent relative inline-block whitespace-nowrap">
                            <span class="relative z-10">College Success</span>
                            <svg class="absolute w-full h-3 -bottom-1.5 left-0 text-accent/40 z-0" preserveAspectRatio="none" viewBox="0 0 100 10">
                                <path d="M0,5 Q50,10 100,5" fill="none" stroke="currentColor" stroke-width="4"/>
                            </svg>
                        </span>
                    </h1>

                    <p class="text-base sm:text-lg md:text-xl text-ink-muted mb-8 max-w-2xl leading-relaxed">
                        Take the {{ config('app.name', 'Gabay') }} personality assessment designed for upcoming Filipino
                        college students and get AI-powered insights into your ideal course and career.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
                        <a href="{{ auth()->check() ? route('assessment.show') : route('register') }}"
                           class="bg-accent hover:bg-accent-hover text-primary font-bold text-sm sm:text-base px-8 py-3.5 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 transform hover:-translate-y-0.5 flex items-center justify-center gap-2 cursor-pointer">
                            <span>{{ __('Start Assessment') }}</span>
                            <x-icon name="arrow-right" class="w-4 h-4"/>
                        </a>

                        @auth
                            <a href="{{ route('results.index') }}"
                               class="bg-white text-primary border-2 border-primary hover:bg-surface font-semibold text-sm sm:text-base px-8 py-3.5 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 flex items-center justify-center cursor-pointer">
                                {{ __('View My Results') }}
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="bg-white text-primary border-2 border-primary hover:bg-surface font-semibold text-sm sm:text-base px-8 py-3.5 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 flex items-center justify-center cursor-pointer">
                                {{ __('Sign In') }}
                            </a>
                        @endauth
                    </div>

                    <div class="mt-10 pt-6 border-t border-surface-high/60 grid grid-cols-3 gap-4 sm:gap-8 text-center w-full max-w-lg">
                        <div>
                            <p class="font-headline font-bold text-xl sm:text-2xl text-primary">6</p>
                            <p class="text-[11px] sm:text-xs text-ink-muted">Trait Categories</p>
                        </div>
                        <div>
                            <p class="font-headline font-bold text-xl sm:text-2xl text-primary">6+</p>
                            <p class="text-[11px] sm:text-xs text-ink-muted">CHED Programs Matched</p>
                        </div>
                        <div>
                            <p class="font-headline font-bold text-xl sm:text-2xl text-primary">100%</p>
                            <p class="text-[11px] sm:text-xs text-ink-muted">Free for Students</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Benefits Bento --}}
            <section class="w-full">
                <div class="text-center mb-10 md:mb-14">
                    <h2 class="font-headline text-2xl sm:text-3xl md:text-4xl font-bold text-primary mb-3">
                        Why {{ config('app.name', 'Gabay') }}?
                    </h2>
                    <p class="text-sm sm:text-base text-ink-muted max-w-2xl mx-auto">
                        Built specifically for the Philippine academic landscape, providing actionable guidance for your future.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">
                    <div class="group bg-white rounded-2xl p-6 sm:p-8 shadow-ambient hover:shadow-ambient-lg border border-surface-high transition-all duration-300 flex flex-col items-start relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-blue-200/40 rounded-bl-full -mr-8 -mt-8 transition-transform group-hover:scale-110 duration-500 pointer-events-none"></div>

                        <div class="w-12 h-12 bg-primary-container text-white rounded-xl flex items-center justify-center mb-6 shadow-md relative z-10">
                            <x-icon name="brain" class="w-6 h-6 text-[#adc7f7]"/>
                        </div>

                        <h3 class="font-headline text-xl font-bold text-primary mb-2 relative z-10">AI-Powered</h3>
                        <p class="text-sm text-ink-muted leading-relaxed relative z-10 flex-grow">
                            Advanced algorithms analyze your strengths, interests, and working style to match you with degrees where you are statistically likely to thrive.
                        </p>
                    </div>

                    <div class="group bg-white rounded-2xl p-6 sm:p-8 shadow-ambient hover:shadow-ambient-lg border border-surface-high transition-all duration-300 flex flex-col items-start relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-accent-soft/60 rounded-bl-full -mr-8 -mt-8 transition-transform group-hover:scale-110 duration-500 pointer-events-none"></div>

                        <div class="w-12 h-12 bg-accent text-primary rounded-xl flex items-center justify-center mb-6 shadow-md relative z-10">
                            <x-icon name="globe" class="w-6 h-6"/>
                        </div>

                        <h3 class="font-headline text-xl font-bold text-primary mb-2 relative z-10">Culturally Contextualized</h3>
                        <p class="text-sm text-ink-muted leading-relaxed relative z-10 flex-grow">
                            Designed with the realities of the Philippine job market and university systems in mind. We understand local demand, CHED priorities, and educational structures.
                        </p>
                    </div>

                    <div class="group bg-white rounded-2xl p-6 sm:p-8 shadow-ambient hover:shadow-ambient-lg border border-surface-high transition-all duration-300 flex flex-col items-start relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-blue-300/50 rounded-bl-full -mr-8 -mt-8 transition-transform group-hover:scale-110 duration-500 pointer-events-none"></div>

                        <div class="w-12 h-12 bg-[#455f88] text-white rounded-xl flex items-center justify-center mb-6 shadow-md relative z-10">
                            <x-icon name="trending-up" class="w-6 h-6 text-[#d6e3ff]"/>
                        </div>

                        <h3 class="font-headline text-xl font-bold text-primary mb-2 relative z-10">Career-Focused</h3>
                        <p class="text-sm text-ink-muted leading-relaxed relative z-10 flex-grow">
                            We don't just suggest a course; we outline a realistic career trajectory, highlighting potential roles and required foundational skills.
                        </p>
                    </div>
                </div>
            </section>

            {{-- Strand Alignment --}}
            <section class="w-full bg-white rounded-2xl p-6 sm:p-10 border border-surface-high shadow-sm mb-8">
                <div class="flex flex-col md:flex-row items-center justify-between gap-8">
                    <div class="max-w-xl">
                        <span class="text-xs font-bold uppercase tracking-wider text-primary-container bg-surface-low px-3 py-1 rounded-full border border-surface-high">
                            K-12 Senior High Ready
                        </span>
                        <h3 class="font-headline text-2xl sm:text-3xl font-bold text-primary mt-3 mb-2">
                            Covers All Academic & TVL Strands
                        </h3>
                        <p class="text-sm text-ink-muted leading-relaxed">
                            Whether you are in STEM, ABM, HUMSS, GAS, or Technical-Vocational-Livelihood (TVL),
                            {{ config('app.name', 'Gabay') }} evaluates your profile against verified CHED & TESDA university course benchmarks.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2.5">
                        @foreach (['STEM', 'ABM', 'HUMSS', 'GAS', 'ICT / TVL', 'Arts & Design'] as $strand)
                            <span class="px-4 py-2 rounded-xl bg-surface-low text-primary text-xs font-bold border border-slate-300/60 flex items-center gap-1.5">
                                <x-icon name="check-circle" class="w-3.5 h-3.5 text-accent"/>
                                {{ $strand }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </section>

        </div>
    </div>
</x-app-layout>
