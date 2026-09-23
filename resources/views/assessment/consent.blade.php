<x-app-layout title="Data Privacy Consent">
    <div class="flex-grow w-full max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-10 flex flex-col gap-6">

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row justify-between sm:items-end gap-4 pb-4 border-b border-surface-low">
            <div>
                <h1 class="font-headline text-2xl sm:text-3xl font-bold text-primary">Data Privacy Consent</h1>
                <p class="text-sm text-ink-muted mt-1">Required under Republic Act No. 10173 (Data Privacy Act of 2012) before taking the assessment.</p>
            </div>
            <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-primary-container hover:text-primary shrink-0">Back to dashboard</a>
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                You must tick the consent checkbox below before continuing.
            </div>
        @endif

        <section class="card p-6 sm:p-8 mb-8">
            <h2 class="font-headline text-lg font-bold text-primary">Privacy Notice</h2>

            <div class="mt-5 space-y-5 text-sm leading-7 text-ink-muted">
                <p>
                    Aguhon collects and processes your personal data to provide AI-assisted academic
                    program recommendations. By continuing, you acknowledge the following:
                </p>

                <div>
                    <h3 class="font-bold text-primary">1. Data Collected</h3>
                    <ul class="mt-2 list-inside list-disc space-y-1">
                        <li>Your account information (name and email address).</li>
                        <li>Your Likert-scale answers to the career assessment questions.</li>
                        <li>The generated recommendations, confidence scores, and assessment history.</li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-bold text-primary">2. Purpose of Processing</h3>
                    <p class="mt-2">
                        Your data is used solely to generate guidance-based program recommendations, to display your
                        results back to you, and to produce anonymized aggregate statistics that help guidance
                        counselors understand overall student tendencies. It is not used as the sole basis for any
                        enrollment decision.
                    </p>
                </div>

                <div>
                    <h3 class="font-bold text-primary">3. Storage and Retention</h3>
                    <p class="mt-2">
                        Your data is stored in a secured database accessible only to authorized personnel. Records are
                        retained only for as long as necessary for the stated academic purposes.
                    </p>
                </div>

                <div>
                    <h3 class="font-bold text-primary">4. Your Rights</h3>
                    <p class="mt-2">
                        Under the Data Privacy Act of 2012, you have the right to access your data, request correction
                        or erasure, object to processing, and lodge a complaint with the National Privacy Commission.
                        Participation is voluntary and you may withdraw consent at any time by requesting account deletion.
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('assessment.consent.store') }}" class="mt-8">
                @csrf

                <label class="flex cursor-pointer items-start gap-3 rounded-xl bg-surface border border-surface-low p-4 transition hover:border-accent hover:bg-accent/10">
                    <input type="checkbox" name="privacy_consent" value="1" required @checked(old('privacy_consent')) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-primary focus:ring-accent">
                    <span class="text-sm font-medium text-ink">
                        I have read and understood this privacy notice. I voluntarily consent to the collection and
                        processing of my assessment data for academic guidance purposes.
                    </span>
                </label>

                <button type="submit" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-accent hover:bg-accent-hover px-5 py-2.5 text-sm font-bold text-primary shadow-md transition-all">
                    Accept and Continue to Assessment
                    <x-icon name="chevron-right" class="w-4 h-4"/>
                </button>
            </form>
        </section>
    </div>
</x-app-layout>
