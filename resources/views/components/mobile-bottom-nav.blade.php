@php
    $homeUrl = auth()->check() ? route('dashboard') : url('/');
    $assessmentUrl = auth()->check() ? route('assessment.show') : route('login');
    $adminActive = request()->routeIs('admin.*');
@endphp

<div class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-surface-high shadow-[0_-4px_16px_rgba(0,0,0,0.06)] px-2 py-1.5 flex justify-around items-center">

    {{-- Home --}}
    <a href="{{ $homeUrl }}"
       class="flex flex-col items-center py-1 px-2.5 rounded-lg text-[10px] font-semibold transition-colors {{ request()->routeIs('dashboard') || request()->routeIs('home') ? 'text-primary' : 'text-ink-faint' }}">
        <x-icon name="home" class="w-5 h-5 mb-0.5 {{ request()->routeIs('dashboard') ? 'text-primary' : '' }}"/>
        <span>{{ __('Home') }}</span>
    </a>

    {{-- Assessment --}}
    <a href="{{ $assessmentUrl }}"
       class="flex flex-col items-center py-1 px-2.5 rounded-lg text-[10px] font-semibold transition-colors {{ request()->routeIs('assessment.*') ? 'text-primary' : 'text-ink-faint' }}">
        <x-icon name="sparkles" class="w-5 h-5 mb-0.5 text-accent"/>
        <span>{{ __('Test') }}</span>
    </a>

    @auth
        {{-- Results --}}
        <a href="{{ route('results.index') }}"
           class="flex flex-col items-center py-1 px-2.5 rounded-lg text-[10px] font-semibold transition-colors {{ request()->routeIs('results.*') ? 'text-primary' : 'text-ink-faint' }}">
            <x-icon name="compass" class="w-5 h-5 mb-0.5 {{ request()->routeIs('results.*') ? 'text-primary' : '' }}"/>
            <span>{{ __('Results') }}</span>
        </a>
    @endauth

    {{-- Resources --}}
    <a href="{{ route('resources.index') }}"
       class="flex flex-col items-center py-1 px-2.5 rounded-lg text-[10px] font-semibold transition-colors {{ request()->routeIs('resources.*') ? 'text-primary' : 'text-ink-faint' }}">
        <x-icon name="book-open" class="w-5 h-5 mb-0.5 {{ request()->routeIs('resources.*') ? 'text-primary' : '' }}"/>
        <span>{{ __('Guide') }}</span>
    </a>

    {{-- Quizzes (students) or Admin (admins) --}}
    @auth
        @if (Auth::user()->isAdmin())
            <a href="{{ route('admin.analytics') }}"
               class="flex flex-col items-center py-1 px-2.5 rounded-lg text-[10px] font-semibold transition-colors {{ $adminActive ? 'text-primary-container' : 'text-ink-faint' }}">
                <x-icon name="shield" class="w-5 h-5 mb-0.5 {{ $adminActive ? 'text-primary-container' : '' }}"/>
                <span>{{ __('Admin') }}</span>
            </a>
        @else
            <a href="{{ route('quizzes.index') }}"
               class="flex flex-col items-center py-1 px-2.5 rounded-lg text-[10px] font-semibold transition-colors {{ request()->routeIs('quizzes.*') || request()->routeIs('quiz-attempts.*') || request()->routeIs('quiz-analytics.*') ? 'text-primary' : 'text-ink-faint' }}">
                <x-icon name="file-text" class="w-5 h-5 mb-0.5 {{ request()->routeIs('quizzes.*') ? 'text-primary' : '' }}"/>
                <span>{{ __('Quizzes') }}</span>
            </a>
        @endif
    @endauth
</div>
