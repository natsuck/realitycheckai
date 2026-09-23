<header x-data="{ open: false, help: false, profile: false }"
        class="bg-white/95 backdrop-blur-md sticky top-0 z-40 border-b border-surface-low shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">

        {{-- Brand --}}
        <a href="{{ url('/') }}" class="flex items-center gap-2 group text-left">
            <span class="text-2xl font-headline font-bold tracking-tight text-primary group-hover:text-primary-container transition-colors">
                {{ config('app.name', 'Gabay') }}
            </span>
            <span class="hidden sm:inline-block text-[10px] uppercase font-bold tracking-widest px-2 py-0.5 rounded-full bg-surface-low text-primary-container border border-surface-high">
                Pilipinas
            </span>
        </a>

        {{-- Desktop Navigation --}}
        <nav class="hidden md:flex items-center gap-1 sm:gap-2">
            @auth
                <a href="{{ route('dashboard') }}"
                   class="px-3.5 py-2 rounded-lg text-sm transition-all {{ request()->routeIs('dashboard') ? 'text-primary font-semibold bg-surface-low' : 'text-ink-muted hover:text-primary hover:bg-surface' }}">
                    {{ __('Home') }}
                </a>
            @endauth

            <a href="{{ auth()->check() ? route('assessment.show') : route('login') }}"
               class="px-3.5 py-2 rounded-lg text-sm transition-all flex items-center gap-1.5 {{ request()->routeIs('assessment.*') ? 'text-primary font-semibold bg-surface-low' : 'text-ink-muted hover:text-primary hover:bg-surface' }}">
                <x-icon name="sparkles" class="w-3.5 h-3.5 text-accent"/>
                <span>{{ __('Assessment') }}</span>
            </a>

            @auth
                <a href="{{ route('results.index') }}"
                   class="px-3.5 py-2 rounded-lg text-sm transition-all {{ request()->routeIs('results.*') ? 'text-primary font-bold bg-surface-low' : 'text-ink-muted hover:text-primary hover:bg-surface' }}">
                    {{ __('Results') }}
                </a>

                <a href="{{ route('quizzes.index') }}"
                   class="px-3.5 py-2 rounded-lg text-sm transition-all {{ request()->routeIs('quizzes.*') || request()->routeIs('quiz-attempts.*') || request()->routeIs('quiz-analytics.*') ? 'text-primary font-semibold bg-surface-low' : 'text-ink-muted hover:text-primary hover:bg-surface' }}">
                    {{ __('AI Quizzes') }}
                </a>
            @endauth

            <a href="{{ route('resources.index') }}"
               class="px-3.5 py-2 rounded-lg text-sm transition-all {{ request()->routeIs('resources.*') ? 'text-primary font-semibold bg-surface-low' : 'text-ink-muted hover:text-primary hover:bg-surface' }}">
                {{ __('Resources') }}
            </a>

            @if (auth()->user()?->isAdmin())
                <a href="{{ route('admin.analytics') }}"
                   class="ml-2 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5 border {{ request()->routeIs('admin.*') ? 'bg-primary text-white border-primary' : 'bg-surface-low text-primary-container border-surface-high hover:bg-surface-high' }}">
                    <x-icon name="shield" class="w-3.5 h-3.5 text-accent"/>
                    <span>{{ __('Admin') }}</span>
                </a>
            @endif
        </nav>

        {{-- Right Header Actions --}}
        <div class="flex items-center gap-2 sm:gap-3">

            {{-- Help --}}
            <button type="button" @click="help = true"
                    class="p-2 rounded-full text-ink-muted hover:text-primary hover:bg-surface-low transition-colors"
                    aria-label="Help and Info">
                <x-icon name="help-circle"/>
            </button>

            @auth
                {{-- Profile Dropdown --}}
                <div class="relative" @click.outside="profile = false">
                    <button type="button" @click="profile = ! profile"
                            class="flex items-center justify-center w-9 h-9 rounded-full bg-primary text-white font-headline font-bold text-sm hover:ring-2 hover:ring-accent transition-all ml-1"
                            aria-label="Profile menu">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </button>

                    <div x-show="profile" x-cloak x-transition
                         class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-2xl border border-surface-high p-3 z-50">
                        <div class="flex items-center gap-3 p-2 bg-surface rounded-lg mb-2 border border-surface-low">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary text-white font-headline font-bold border border-surface-high shrink-0">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                            <div class="overflow-hidden">
                                <p class="text-sm font-bold text-primary truncate">{{ Auth::user()->name }}</p>
                                <p class="text-[11px] text-ink-muted truncate">{{ Auth::user()->email }}</p>
                                <span class="inline-block text-[10px] font-semibold px-2 py-0.5 rounded-full bg-surface-high text-primary mt-1 capitalize">
                                    {{ Auth::user()->isAdmin() ? 'Admin' : 'Student' }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-1 text-xs font-medium text-ink">
                            <a href="{{ route('profile.edit') }}"
                               class="w-full flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-surface-low text-primary">
                                <x-icon name="user" class="w-4 h-4 text-primary-container"/>
                                <span>{{ __('My Profile') }}</span>
                            </a>

                            @if (Auth::user()->isAdmin())
                                <a href="{{ route('admin.analytics') }}"
                                   class="w-full flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-surface-low text-primary">
                                    <x-icon name="layout-dashboard" class="w-4 h-4 text-primary-container"/>
                                    <span>{{ __('Admin Analytics') }}</span>
                                </a>
                            @else
                                <a href="{{ route('results.index') }}"
                                   class="w-full flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-surface-low text-primary">
                                    <x-icon name="compass" class="w-4 h-4 text-primary-container"/>
                                    <span>{{ __('My Assessment Results') }}</span>
                                </a>
                            @endif

                            <div class="border-t border-surface-low my-1"></div>

                            <form method="POST" action="{{ route('logout') }}" x-data>
                                @csrf
                                <button type="submit"
                                        class="w-full flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-rose-50 text-rose-600"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                    <x-icon name="log-out" class="w-4 h-4"/>
                                    <span>{{ __('Log Out') }}</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}"
                   class="px-4 py-2 rounded-lg bg-primary hover:bg-primary-container text-white text-xs sm:text-sm font-semibold transition-all shadow-sm">
                    {{ __('Sign In') }}
                </a>
            @endauth

            {{-- Mobile Hamburger --}}
            <button type="button" @click="open = ! open"
                    class="md:hidden p-2 rounded-lg text-primary hover:bg-surface-low"
                    aria-label="Toggle navigation">
                <x-icon name="{{ 'menu' }}" x-show="! open" class="w-6 h-6"/>
                <x-icon name="x" x-show="open" x-cloak class="w-6 h-6"/>
            </button>
        </div>
    </div>

    {{-- Mobile Dropdown Navigation --}}
    <div x-show="open" x-cloak x-transition class="md:hidden bg-white border-b border-surface-high px-4 pt-2 pb-4 space-y-2">
        @auth
            <a href="{{ route('dashboard') }}"
               class="block w-full text-left px-4 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-surface-low text-primary font-bold' : 'text-ink-muted' }}">
                {{ __('Home') }}
            </a>
        @endauth

        <a href="{{ auth()->check() ? route('assessment.show') : route('login') }}"
           class="flex w-full items-center justify-between text-left px-4 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('assessment.*') ? 'bg-surface-low text-primary font-bold' : 'text-ink-muted' }}">
            <span>{{ __('Take Assessment') }}</span>
            <x-icon name="sparkles" class="w-4 h-4 text-accent"/>
        </a>

        @auth
            <a href="{{ route('results.index') }}"
               class="block w-full text-left px-4 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('results.*') ? 'bg-surface-low text-primary font-bold' : 'text-ink-muted' }}">
                {{ __('Results Portal') }}
            </a>

            <a href="{{ route('quizzes.index') }}"
               class="block w-full text-left px-4 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('quizzes.*') || request()->routeIs('quiz-attempts.*') || request()->routeIs('quiz-analytics.*') ? 'bg-surface-low text-primary font-bold' : 'text-ink-muted' }}">
                {{ __('AI Quizzes') }}
            </a>
        @endauth

        <a href="{{ route('resources.index') }}"
           class="block w-full text-left px-4 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('resources.*') ? 'bg-surface-low text-primary font-bold' : 'text-ink-muted' }}">
            {{ __('Resources & Entrance Exam Reviewers') }}
        </a>

        @if (auth()->user()?->isAdmin())
            <a href="{{ route('admin.analytics') }}"
               class="flex w-full items-center justify-between text-left px-4 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.*') ? 'bg-surface-low text-primary font-bold' : 'text-ink-muted' }}">
                <span>{{ __('Admin Management') }}</span>
                <x-icon name="shield" class="w-4 h-4 text-primary-container"/>
            </a>
        @endif

        @guest
            <a href="{{ route('login') }}"
               class="block w-full text-left px-4 py-2.5 rounded-lg text-sm font-semibold bg-primary text-white">
                {{ __('Sign In') }}
            </a>
        @endguest
    </div>

    {{-- Help Modal --}}
    <div x-show="help" x-cloak x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-primary/50 backdrop-blur-sm" style="display: none">
        <div @click.outside="help = false"
             class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-surface-high">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-headline font-bold text-primary">About {{ config('app.name', 'Gabay') }}</h3>
                <button type="button" @click="help = false" class="p-1 rounded-lg text-ink-faint hover:bg-surface-low" aria-label="Close">
                    <x-icon name="x" class="w-5 h-5"/>
                </button>
            </div>
            <div class="space-y-3 text-sm text-ink-muted leading-relaxed">
                <p>
                    <strong>{{ config('app.name', 'Gabay') }}</strong> is an AI-powered college course and career path
                    discovery platform tailored for Filipino senior high school students and college freshmen.
                </p>
                <div class="p-3 bg-surface-low rounded-xl border border-surface-high">
                    <h5 class="font-semibold text-xs text-primary mb-1">How it works:</h5>
                    <ul class="text-xs space-y-1 list-disc list-inside text-primary-container">
                        <li>Take the Likert-scale assessment evaluating your skills, interests, personality, and work style.</li>
                        <li>Receive an AI trait analysis with a category strength profile.</li>
                        <li>Get a ranked shortlist of degree programs plus university and scholarship guidance.</li>
                    </ul>
                </div>
            </div>
            <button type="button" @click="help = false"
                    class="mt-6 w-full py-2.5 rounded-xl bg-primary text-white font-semibold text-sm">
                Got it, continue
            </button>
        </div>
    </div>
</header>
