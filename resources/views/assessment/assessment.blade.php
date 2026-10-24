<x-app-layout>
    <div x-data="assessmentWizard({{ \Illuminate\Support\Js::from([
        'total' => count($wizardQuestions),
        'questions' => $wizardQuestions,
        'storageKey' => 'aguhon-assessment-'.\App\Models\Question::CURRENT_INSTRUMENT_VERSION.'-'.auth()->id(),
    ]) }})"
         class="flex-grow flex flex-col items-center justify-between w-full relative min-h-[calc(100vh-4rem)]">

        {{-- Top Fixed Progress Bar --}}
        <div class="fixed top-16 left-0 w-full h-1.5 bg-primary-container/10 z-30">
            <div class="h-full bg-accent transition-all duration-300 ease-out shadow-sm" :style="`width: ${progress}%`"></div>
        </div>

        @if (session('status'))
            <div class="fixed top-20 left-1/2 -translate-x-1/2 z-40 max-w-lg w-full mx-auto px-4">
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-ambient">
                    {{ session('status') }}
                </div>
            </div>
        @endif

        <main class="w-full max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-14 flex-grow flex flex-col justify-center">

            @if (count($wizardQuestions) === 0)
                <div class="card p-8 text-center text-ink-muted">
                    No assessment questions are currently available.
                </div>
            @else
                @if ($errors->any())
                    <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 shadow-ambient">
                        <p class="font-semibold">We couldn't submit your assessment:</p>
                        <ul class="mt-1 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <p class="mt-1 text-xs">Your saved answers are still available. Review the item(s) above and try again.</p>
                    </div>
                @endif

                {{-- Progress Context Header --}}
                <div class="w-full flex justify-between items-center mb-6" x-show="! submitting">
                    <span class="font-headline font-bold text-xs sm:text-sm text-ink-muted tracking-wider uppercase">
                        Question <span x-text="current + 1"></span> of <span x-text="total"></span>
                    </span>

                    <div class="flex flex-col items-end gap-1">
                        <span class="text-[10px] sm:text-xs text-ink-faint" x-text="questions[current].section"></span>
                        <div class="bg-surface-low px-3 py-1 rounded-full border border-surface-high">
                            <span class="text-xs font-semibold text-primary-container" x-text="questions[current].label"></span>
                        </div>
                    </div>
                </div>

                {{-- Question Card --}}
                <form x-ref="form" method="POST" action="{{ route('assessment.submit') }}" class="transition-all">
                    @csrf

                    {{-- Hidden inputs keep every answer in one POST payload --}}
                    @foreach ($wizardQuestions as $index => $question)
                        <input type="hidden" name="question_{{ $question['id'] }}"
                               :value="answers[{{ $question['id'] }}] ?? ''"
                               @if ($index === 0) autofocus @endif>
                    @endforeach

                    <div class="card p-6 sm:p-10 md:p-12 flex flex-col gap-8" x-show="! submitting">
                        <div class="text-center py-2">
                            <h2 class="font-headline text-xl sm:text-2xl md:text-3xl font-bold text-primary leading-relaxed">
                                "<span x-text="questions[current].text"></span>"
                            </h2>
                            <p class="text-xs sm:text-sm text-ink-muted mt-2">
                                <span x-show="questions[current].type === 'likert'">Rate how much you would enjoy the activity, regardless of salary, prestige, or difficulty.</span>
                                <span x-show="questions[current].type === 'multiple_choice'">Select the best answer.</span>
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-2.5 sm:gap-2 w-full pt-2"
                             :class="questions[current].type === 'likert' ? 'sm:grid-cols-5' : 'sm:grid-cols-1'">
                            <template x-for="option in questions[current].options" :key="option.value">
                                <button
                                    type="button"
                                    @click="select(questions[current].id, option.value); $event.currentTarget.blur()"
                                    class="relative p-4 rounded-xl border text-left sm:text-center transition-all duration-200 cursor-pointer flex items-center justify-between sm:justify-center gap-3 min-h-[64px]"
                                    :class="[
                                        questions[current].type === 'likert' ? 'sm:flex-col sm:min-h-[100px]' : '',
                                        answers[questions[current].id] === option.value
                                        ? 'bg-primary-container border-primary-container text-white shadow-md transform sm:-translate-y-1'
                                        : 'bg-white border-slate-300 hover:bg-surface-low text-ink hover:border-primary-container/40'
                                    ]"
                                >
                                    <span class="text-xs sm:text-sm font-semibold">
                                        <span x-show="questions[current].type === 'multiple_choice'" x-text="option.label + '. '"></span>
                                        <span x-text="option.text"></span>
                                    </span>

                                    <div
                                        class="w-5 h-5 rounded-full border flex items-center justify-center shrink-0"
                                        :class="answers[questions[current].id] === option.value ? 'border-accent bg-accent text-primary' : 'border-slate-300 bg-white'"
                                    >
                                        <x-icon name="check" class="w-3.5 h-3.5 stroke-[3]" x-show="answers[questions[current].id] === option.value" style="display:none"/>
                                    </div>
                                </button>
                            </template>
                        </div>

                        {{-- Feedback hint for blocked actions --}}
                        <div class="flex justify-center">
                            <p x-show="hint" x-text="hint" x-cloak
                               class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-800 shadow-sm transition-all"></p>
                        </div>

                        <div class="hidden sm:flex justify-center items-center gap-4 text-[11px] text-ink-faint">
                            <span>Tip: Press 1 to <span x-text="questions[current].options.length"></span> to answer quickly</span>
                            <span>&bull;</span>
                            <span>Use Next button or Enter to advance</span>
                        </div>
                    </div>

                    {{-- Bottom Actions --}}
                    <div class="flex justify-between items-center w-full mt-8" x-show="! submitting">
                        <button
                            type="button"
                            @click="prev()"
                            :disabled="current === 0"
                            class="px-6 py-2.5 rounded-full border-2 border-primary-container font-semibold text-xs sm:text-sm transition-all flex items-center gap-2"
                            :class="current === 0 ? 'opacity-40 cursor-not-allowed text-ink-faint border-slate-300' : 'text-primary-container hover:bg-surface-low active:scale-95'"
                        >
                            <x-icon name="arrow-left" class="w-4 h-4"/>
                            Previous
                        </button>

                        <button
                            type="button"
                            @click="next()"
                            class="px-8 py-3 rounded-full bg-accent hover:bg-accent-hover text-primary font-bold text-xs sm:text-sm transition-all shadow-md hover:shadow-lg flex items-center gap-2 tracking-wide active:scale-95 cursor-pointer"
                        >
                            <span x-show="current < total - 1">Next</span>
                            <span x-show="current >= total - 1" x-cloak>View My Results</span>
                            <x-icon name="sparkles" class="w-4 h-4" x-show="current >= total - 1" x-cloak/>
                            <x-icon name="arrow-right" class="w-4 h-4" x-show="current < total - 1"/>
                        </button>
                    </div>

                    {{-- Submitting state --}}
                    <div class="py-16 text-center" x-show="submitting" x-cloak>
                        <div class="inline-flex flex-col items-center gap-4">
                            <div class="w-12 h-12 border-4 border-surface-high border-t-accent rounded-full animate-spin"></div>
                            <p class="font-headline font-bold text-primary text-lg">Analyzing Responses...</p>
                            <p class="text-xs text-ink-faint">Our AI is comparing your trait profile against every program in the catalog.</p>
                        </div>
                    </div>
                </form>
            @endif
        </main>

        {{-- Footer minimal info --}}
        <footer class="w-full py-4 text-center text-xs text-ink-faint border-t border-surface-low bg-white/50">
            Questionnaire standardized for Filipino Senior High graduates and College freshmen.
        </footer>
    </div>
</x-app-layout>
