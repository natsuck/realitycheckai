@props(['active' => null])

@php
    $user = auth()->user();
    $initials = strtoupper(substr($user->name ?? 'A', 0, 1));

    $links = [
        ['key' => 'analytics', 'route' => 'admin.analytics', 'icon' => 'bar-chart', 'label' => 'Assessment Analytics'],
        ['key' => 'resources', 'route' => 'admin.resources.index', 'icon' => 'book-open', 'label' => 'Resource Center'],
        ['key' => 'quiz-analytics', 'route' => 'quiz-analytics.index', 'icon' => 'file-text', 'label' => 'Quiz Analytics'],
        ['key' => 'dashboard', 'route' => 'dashboard', 'icon' => 'layout-dashboard', 'label' => 'Dashboard'],
    ];
@endphp

<div class="flex-grow flex flex-col md:flex-row w-full min-h-[calc(100vh-4rem)] bg-surface">
    {{-- Side Navigation --}}
    <aside class="w-full md:w-64 bg-primary text-white p-4 flex flex-col justify-between shrink-0 shadow-xl z-20">
        <div>
            <div class="flex items-center gap-3 mb-6 mt-2 px-2">
                <div class="w-10 h-10 rounded-full bg-primary-container border border-slate-400 shadow-sm flex items-center justify-center font-headline font-bold">
                    {{ $initials }}
                </div>
                <div>
                    <h2 class="text-lg font-headline font-bold text-accent-soft">Admin Portal</h2>
                    <p class="text-xs text-[#adc7f7]">Management Console</p>
                </div>
            </div>

            <nav class="flex flex-col gap-1 text-xs sm:text-sm font-medium">
                @foreach ($links as $link)
                    @if ($active === $link['key'])
                        <span class="w-full text-left px-3.5 py-2.5 rounded-xl flex items-center gap-3 bg-accent text-primary font-bold shadow-sm cursor-default">
                            <x-icon :name="$link['icon']" class="w-4 h-4"/>
                            <span>{{ $link['label'] }}</span>
                        </span>
                    @else
                        <a href="{{ route($link['route']) }}"
                           class="w-full text-left px-3.5 py-2.5 rounded-xl flex items-center gap-3 transition-all text-[#d6e3ff] hover:bg-primary-container hover:text-white">
                            <x-icon :name="$link['icon']" class="w-4 h-4"/>
                            <span>{{ $link['label'] }}</span>
                        </a>
                    @endif
                @endforeach
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
        {{ $slot }}
    </main>
</div>
