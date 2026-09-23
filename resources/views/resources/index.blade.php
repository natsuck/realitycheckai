@php
    $collegeExams = [
        [
            'name' => 'UPCAT (University of the Philippines)',
            'period' => 'August (Annual)',
            'coverage' => 'Language Proficiency, Science, Mathematics, Reading Comprehension',
            'tips' => 'Sub-test speed is critical. Passing score requires high UPG (University Predicted Grade) combining high school GWA (40%) and UPCAT score (60%).',
            'link' => 'https://upcat.up.edu.ph',
        ],
        [
            'name' => 'ACET (Ateneo College Entrance Test)',
            'period' => 'October - November',
            'coverage' => 'Numerical Ability, Logic/Abstract Reasoning, English, General Information, Essay',
            'tips' => 'Very heavy on logical speed and reading passages. Prepare for high-frequency essay prompts on national issues.',
            'link' => 'https://ateneo.edu/admissions',
        ],
        [
            'name' => 'DCAT (De La Salle University)',
            'period' => 'October - December',
            'coverage' => 'Science, Math, English, General Reasoning, Personality Test',
            'tips' => 'Look into the St. La Salle Financial Assistance Grant which covers up to 100% tuition for qualifying top rankers.',
            'link' => 'https://www.dlsu.edu.ph/admission/scholarship/',
        ],
        [
            'name' => 'USTET (University of Santo Tomas)',
            'period' => 'October - January (Multiple Batches)',
            'coverage' => 'Mental Ability, English, Mathematics, Science',
            'tips' => 'Science and Math questions require strong high school chemistry, biology, physics fundamentals, especially for LEAPMed & Allied Health programs.',
            'link' => 'https://ustet.ust.edu.ph',
        ],
        [
            'name' => 'PUPCET (Polytechnic University of the Philippines)',
            'period' => 'January - March',
            'coverage' => 'Mathematics, Science, English, Reasoning, General Information',
            'tips' => 'High volume of applicants nationwide. Fully covered under RA 10931 (Free Higher Education Act).',
            'link' => 'https://www.pup.edu.ph/applicants/',
        ],
    ];

    $scholarships = [
        [
            'title' => 'DOST-SEI Undergraduate S&T Scholarship',
            'grantor' => 'Department of Science and Technology (DOST)',
            'coverage' => '₱40,000/year Tuition Subsidy + ₱8,000/month living stipend + Book & Thesis Allowances',
            'criteria' => 'Top 5% of non-STEM graduating class or ANY graduating STEM strand student.',
            'link' => 'https://sei.dost.gov.ph',
        ],
        [
            'title' => 'CHED Student Financial Assistance Program (StuFAPs)',
            'grantor' => 'Commission on Higher Education (CHED)',
            'coverage' => 'Merit and need-based full and half-tuition subsidies across priority degree programs.',
            'criteria' => 'Filipino citizen with GWA of 90% (Full Merit) or 85% (Half Merit).',
            'link' => 'https://ched.gov.ph',
        ],
        [
            'title' => 'SM Foundation College Scholarship',
            'grantor' => 'SM Foundation',
            'coverage' => '100% Tuition & Miscellaneous fees + Monthly allowance + Part-time work opportunities',
            'criteria' => 'Public high school graduates or private school students with DepEd voucher.',
            'link' => 'https://sm-foundation.org',
        ],
    ];

    $universities = [
        ['shortName' => 'UP', 'name' => 'University of the Philippines', 'location' => 'Diliman, Quezon City / Manila', 'type' => 'State University', 'topPrograms' => ['BS Computer Science', 'BS Statistics', 'BS Molecular Biology', 'BS Civil Engineering'], 'entranceExam' => 'UPCAT', 'tuitionRange' => 'Free Higher Education Act (Public/Subsidized)', 'website' => 'https://up.edu.ph', 'logoBg' => '#7b1113'],
        ['shortName' => 'ADMU', 'name' => 'Ateneo de Manila University', 'location' => 'Loyola Heights, Quezon City', 'type' => 'Private University', 'topPrograms' => ['BS MIS', 'BS Computer Science', 'BS Applied Mathematics'], 'entranceExam' => 'ACET', 'tuitionRange' => '₱90,000 - ₱120,000 per sem', 'website' => 'https://ateneo.edu', 'logoBg' => '#002f6c'],
        ['shortName' => 'DLSU', 'name' => 'De La Salle University', 'location' => 'Taft Avenue, Manila / Laguna', 'type' => 'Private University', 'topPrograms' => ['BS CS (Software Tech)', 'BS Information Technology', 'BS Accountancy'], 'entranceExam' => 'DCAT', 'tuitionRange' => '₱85,000 - ₱115,000 per term', 'website' => 'https://dlsu.edu.ph', 'logoBg' => '#00703c'],
        ['shortName' => 'UST', 'name' => 'University of Santo Tomas', 'location' => 'España Blvd, Sampaloc, Manila', 'type' => 'Private University', 'topPrograms' => ['BS Information Systems', 'BS Medical Technology', 'BS Nursing'], 'entranceExam' => 'USTET', 'tuitionRange' => '₱60,000 - ₱80,000 per sem', 'website' => 'https://ust.edu.ph', 'logoBg' => '#d4a017'],
        ['shortName' => 'PUP', 'name' => 'Polytechnic University of the Philippines', 'location' => 'Sta. Mesa, Manila', 'type' => 'State University', 'topPrograms' => ['BS Information Technology', 'BS Computer Engineering', 'BS Accountancy'], 'entranceExam' => 'PUPCET', 'tuitionRange' => 'Free Higher Education Act (Subsidized)', 'website' => 'https://pup.edu.ph', 'logoBg' => '#800000'],
        ['shortName' => 'MAPUA', 'name' => 'Mapúa University', 'location' => 'Intramuros, Manila / Makati', 'type' => 'Private University', 'topPrograms' => ['BS Data Science', 'BS Computer Science', 'BS Electrical Engineering'], 'entranceExam' => 'MSA', 'tuitionRange' => '₱45,000 - ₱60,000 per quarter', 'website' => 'https://mapua.edu.ph', 'logoBg' => '#c8102e'],
    ];

    $strands = [
        [
            'emoji' => '🔬',
            'title' => 'STEM Strand',
            'subtitle' => 'Science, Technology, Engineering, and Mathematics',
            'alignments' => 'BS Computer Science, BS Data Science, BS Civil/Mechanical Engineering, BS Nursing, BS Biology.',
        ],
        [
            'emoji' => '📈',
            'title' => 'ABM Strand',
            'subtitle' => 'Accountancy, Business, and Management',
            'alignments' => 'BS Accountancy, BS Business Administration, BS Management Information Systems, BS Economics.',
        ],
        [
            'emoji' => '⚖️',
            'title' => 'HUMSS Strand',
            'subtitle' => 'Humanities and Social Sciences',
            'alignments' => 'BA Communication, BA Political Science, BS Psychology, BA Journalism, Bachelor of Secondary Education.',
        ],
        [
            'emoji' => '💻',
            'title' => 'ICT / TVL Track',
            'subtitle' => 'Information and Communications Technology / Tech-Voc',
            'alignments' => 'BS Information Technology, BS Computer Engineering, Animation, Web Development, Network Systems.',
        ],
    ];
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
            @foreach ($collegeExams as $exam)
                <div class="card p-6 flex flex-col justify-between gap-4 group hover:border-primary-container transition-all">
                    <div>
                        <div class="flex justify-between items-start gap-2 mb-2">
                            <h3 class="font-headline text-lg font-bold text-primary">{{ $exam['name'] }}</h3>
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-surface-low text-primary-container shrink-0">
                                {{ $exam['period'] }}
                            </span>
                        </div>

                        <div class="space-y-2 text-xs sm:text-sm text-ink-muted">
                            <div>
                                <strong class="text-primary">Exam Coverage: </strong>
                                {{ $exam['coverage'] }}
                            </div>
                            <div class="p-3 bg-surface rounded-xl border border-surface-low text-xs text-primary-container">
                                <strong class="text-primary">Reviewer Strategy: </strong>
                                {{ $exam['tips'] }}
                            </div>
                        </div>
                    </div>

                    <a href="{{ $exam['link'] }}" target="_blank" rel="noreferrer"
                       class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:text-primary-container pt-2 border-t border-surface-low">
                        <span>Visit Official Portal</span>
                        <x-icon name="external-link" class="w-3.5 h-3.5 text-accent"/>
                    </a>
                </div>
            @endforeach
        </div>

        {{-- Scholarships --}}
        <div x-show="tab === 'scholarships'" x-cloak class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach ($scholarships as $scholarship)
                <div class="card p-6 flex flex-col justify-between gap-4">
                    <div>
                        <span class="text-[10px] uppercase font-bold tracking-wider text-accent-ink bg-accent/20 px-2 py-0.5 rounded-full inline-block mb-2">
                            {{ $scholarship['grantor'] }}
                        </span>
                        <h3 class="font-headline text-lg font-bold text-primary mb-2">{{ $scholarship['title'] }}</h3>
                        <div class="p-3 bg-surface-low rounded-xl text-xs text-primary-container mb-3">
                            <strong>Benefits: </strong> {{ $scholarship['coverage'] }}
                        </div>
                        <p class="text-xs text-ink-muted">
                            <strong>Eligibility: </strong> {{ $scholarship['criteria'] }}
                        </p>
                    </div>

                    <a href="{{ $scholarship['link'] }}" target="_blank" rel="noreferrer"
                       class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:text-primary-container">
                        <span>Scholarship Application Guidelines</span>
                        <x-icon name="external-link" class="w-3.5 h-3.5 text-accent"/>
                    </a>
                </div>
            @endforeach
        </div>

        {{-- Universities --}}
        <div x-show="tab === 'unis'" x-cloak class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($universities as $uni)
                <div class="card p-6 flex flex-col justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold font-headline text-sm shadow-sm"
                                 style="background-color: {{ $uni['logoBg'] }}">
                                {{ $uni['shortName'] }}
                            </div>
                            <div>
                                <h3 class="font-headline font-bold text-sm text-primary">{{ $uni['name'] }}</h3>
                                <p class="text-xs text-ink-faint">{{ $uni['location'] }}</p>
                            </div>
                        </div>

                        <div class="space-y-1.5 text-xs text-ink-muted">
                            <p><strong>Type: </strong>{{ $uni['type'] }}</p>
                            <p><strong>Admission Exam: </strong>{{ $uni['entranceExam'] }}</p>
                            <p><strong>Tuition: </strong>{{ $uni['tuitionRange'] }}</p>
                            <p><strong>Key Programs: </strong>{{ implode(', ', $uni['topPrograms']) }}</p>
                        </div>
                    </div>

                    <a href="{{ $uni['website'] }}" target="_blank" rel="noreferrer"
                       class="inline-flex items-center gap-1 text-xs font-semibold text-primary-container hover:underline">
                        <span>University Portal</span>
                        <x-icon name="external-link" class="w-3 h-3 text-accent"/>
                    </a>
                </div>
            @endforeach
        </div>

        {{-- Strands --}}
        <div x-show="tab === 'strands'" x-cloak class="card p-6 sm:p-8 space-y-6 mb-8">
            <h3 class="font-headline text-xl font-bold text-primary">
                DepEd K-12 Senior High School Strand Cross-Alignment Matrix
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs sm:text-sm">
                @foreach ($strands as $strand)
                    <div class="p-4 rounded-xl bg-surface-low border border-surface-high">
                        <h4 class="font-bold text-sm text-primary mb-1">{{ $strand['emoji'] }} {{ $strand['title'] }}</h4>
                        <p class="text-ink-muted mb-2">{{ $strand['subtitle'] }}</p>
                        <strong class="text-primary-container">Top Degree Alignments: </strong>
                        <span>{{ $strand['alignments'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
