<?php

namespace Database\Seeders;

use App\Models\Resource;
use App\Models\School;
use Illuminate\Database\Seeder;

class ResourceSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->cets() as $order => $cet) {
            $this->upsert(Resource::TYPE_CET, $cet['name'], [
                'period' => $cet['period'],
                'coverage' => $cet['coverage'],
                'tips' => $cet['tips'],
                'link' => $cet['link'],
                'sort_order' => $order,
            ]);
        }

        foreach ($this->scholarships() as $order => $scholarship) {
            $this->upsert(Resource::TYPE_SCHOLARSHIP, $scholarship['title'], [
                'grantor' => $scholarship['grantor'],
                'coverage' => $scholarship['coverage'],
                'criteria' => $scholarship['criteria'],
                'link' => $scholarship['link'],
                'sort_order' => $order,
            ]);
        }

        foreach ($this->universities() as $order => $uni) {
            $school = School::query()->updateOrCreate(
                ['name' => $uni['name']],
                [
                    'short_name' => $uni['shortName'],
                    'location' => $uni['location'],
                    'institution_type' => $uni['type'],
                    'website' => $uni['website'],
                ],
            );

            $resource = $this->upsert(Resource::TYPE_UNIVERSITY, $uni['name'], [
                'short_name' => $uni['shortName'],
                'location' => $uni['location'],
                'institution_type' => $uni['type'],
                'top_programs' => $uni['topPrograms'],
                'entrance_exam' => $uni['entranceExam'],
                'tuition_range' => $uni['tuitionRange'],
                'website' => $uni['website'],
                'logo_bg' => $uni['logoBg'],
                'sort_order' => $order,
            ]);

            $resource->schools()->syncWithoutDetaching([$school->id]);
        }

        foreach ($this->strands() as $order => $strand) {
            $this->upsert(Resource::TYPE_STRAND, $strand['title'], [
                'emoji' => $strand['emoji'],
                'subtitle' => $strand['subtitle'],
                'alignments' => $strand['alignments'],
                'sort_order' => $order,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsert(string $type, string $title, array $attributes): Resource
    {
        return Resource::query()->updateOrCreate(
            ['type' => $type, 'title' => $title],
            $attributes + [
                'slug' => \Illuminate\Support\Str::slug($title),
                'is_published' => true,
            ],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function cets(): array
    {
        return [
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
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function scholarships(): array
    {
        return [
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
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function universities(): array
    {
        return [
            ['shortName' => 'UP', 'name' => 'University of the Philippines', 'location' => 'Diliman, Quezon City / Manila', 'type' => 'State University', 'topPrograms' => ['BS Computer Science', 'BS Statistics', 'BS Molecular Biology', 'BS Civil Engineering'], 'entranceExam' => 'UPCAT', 'tuitionRange' => 'Free Higher Education Act (Public/Subsidized)', 'website' => 'https://up.edu.ph', 'logoBg' => '#7b1113'],
            ['shortName' => 'ADMU', 'name' => 'Ateneo de Manila University', 'location' => 'Loyola Heights, Quezon City', 'type' => 'Private University', 'topPrograms' => ['BS MIS', 'BS Computer Science', 'BS Applied Mathematics'], 'entranceExam' => 'ACET', 'tuitionRange' => '₱90,000 - ₱120,000 per sem', 'website' => 'https://ateneo.edu', 'logoBg' => '#002f6c'],
            ['shortName' => 'DLSU', 'name' => 'De La Salle University', 'location' => 'Taft Avenue, Manila / Laguna', 'type' => 'Private University', 'topPrograms' => ['BS CS (Software Tech)', 'BS Information Technology', 'BS Accountancy'], 'entranceExam' => 'DCAT', 'tuitionRange' => '₱85,000 - ₱115,000 per term', 'website' => 'https://dlsu.edu.ph', 'logoBg' => '#00703c'],
            ['shortName' => 'UST', 'name' => 'University of Santo Tomas', 'location' => 'España Blvd, Sampaloc, Manila', 'type' => 'Private University', 'topPrograms' => ['BS Information Systems', 'BS Medical Technology', 'BS Nursing'], 'entranceExam' => 'USTET', 'tuitionRange' => '₱60,000 - ₱80,000 per sem', 'website' => 'https://ust.edu.ph', 'logoBg' => '#d4a017'],
            ['shortName' => 'PUP', 'name' => 'Polytechnic University of the Philippines', 'location' => 'Sta. Mesa, Manila', 'type' => 'State University', 'topPrograms' => ['BS Information Technology', 'BS Computer Engineering', 'BS Accountancy'], 'entranceExam' => 'PUPCET', 'tuitionRange' => 'Free Higher Education Act (Subsidized)', 'website' => 'https://pup.edu.ph', 'logoBg' => '#800000'],
            ['shortName' => 'MAPUA', 'name' => 'Mapúa University', 'location' => 'Intramuros, Manila / Makati', 'type' => 'Private University', 'topPrograms' => ['BS Data Science', 'BS Computer Science', 'BS Electrical Engineering'], 'entranceExam' => 'MSA', 'tuitionRange' => '₱45,000 - ₱60,000 per quarter', 'website' => 'https://mapua.edu.ph', 'logoBg' => '#c8102e'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function strands(): array
    {
        return [
            ['emoji' => '🔬', 'title' => 'STEM Strand', 'subtitle' => 'Science, Technology, Engineering, and Mathematics', 'alignments' => 'BS Computer Science, BS Data Science, BS Civil/Mechanical Engineering, BS Nursing, BS Biology.'],
            ['emoji' => '📈', 'title' => 'ABM Strand', 'subtitle' => 'Accountancy, Business, and Management', 'alignments' => 'BS Accountancy, BS Business Administration, BS Management Information Systems, BS Economics.'],
            ['emoji' => '⚖️', 'title' => 'HUMSS Strand', 'subtitle' => 'Humanities and Social Sciences', 'alignments' => 'BA Communication, BA Political Science, BS Psychology, BA Journalism, Bachelor of Secondary Education.'],
            ['emoji' => '💻', 'title' => 'ICT / TVL Track', 'subtitle' => 'Information and Communications Technology / Tech-Voc', 'alignments' => 'BS Information Technology, BS Computer Engineering, Animation, Web Development, Network Systems.'],
        ];
    }
}
