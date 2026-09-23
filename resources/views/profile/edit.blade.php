<x-app-layout title="Profile">
    <div class="flex-grow w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-10 flex flex-col gap-6">

        {{-- Page Header --}}
        <div class="pb-4 border-b border-surface-low">
            <h1 class="font-headline text-2xl sm:text-3xl font-bold text-primary">{{ __('Profile') }}</h1>
        </div>

        <div class="space-y-6 pb-8">
            <div class="card p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="card p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="card p-4 sm:p-8 border border-rose-100">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
