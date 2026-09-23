<footer class="w-full bg-surface border-t border-surface-low py-10 mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row justify-between items-center gap-6">

            {{-- Logo & Copyright --}}
            <div class="flex flex-col items-center md:items-start gap-1">
                <div class="flex items-center gap-2">
                    <span class="font-headline font-bold text-xl text-primary">{{ config('app.name', 'Gabay') }}</span>
                    <span class="text-[10px] uppercase font-bold tracking-widest px-2 py-0.5 rounded-full bg-surface-low text-primary-container border border-surface-high">
                        Pilipinas
                    </span>
                </div>
                <p class="text-xs text-ink-faint">
                    &copy; {{ date('Y') }} {{ config('app.name', 'Gabay') }} Assessment Platform. Empowering Filipino Students.
                </p>
            </div>

            {{-- Links --}}
            <div class="flex flex-wrap items-center justify-center gap-6 text-xs text-ink-muted font-medium">
                <a href="{{ auth()->check() ? route('assessment.show') : route('login') }}" class="hover:text-primary transition-colors">
                    {{ __('Take Assessment') }}
                </a>

                @auth
                    <a href="{{ route('results.index') }}" class="hover:text-primary transition-colors">
                        {{ __('Results Portal') }}
                    </a>
                @endauth

                <a href="{{ route('resources.index') }}" class="hover:text-primary transition-colors">
                    {{ __('College Reviewers & Scholarships') }}
                </a>

                @if (auth()->user()?->isAdmin())
                    <a href="{{ route('admin.analytics') }}" class="hover:text-primary transition-colors">
                        {{ __('Admin Console') }}
                    </a>
                @endif
            </div>

        </div>
    </div>
</footer>
