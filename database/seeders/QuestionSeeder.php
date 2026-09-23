<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    public function run()
    {
        $questions = [

            // SKILLS
            ['question_text' => 'I enjoy solving logical or analytical problems.', 'category' => 'skills'],
            ['question_text' => 'I am comfortable analyzing information or data.', 'category' => 'skills'],
            ['question_text' => 'I can communicate my ideas clearly to others.', 'category' => 'skills'],
            ['question_text' => 'I can collaborate effectively in group activities.', 'category' => 'skills'],

            // INTERESTS
            ['question_text' => 'I am interested in technology and digital tools.', 'category' => 'interest'],
            ['question_text' => 'I enjoy creative problem-solving or design-related activities.', 'category' => 'interest'],
            ['question_text' => 'I am interested in business, management, or entrepreneurship.', 'category' => 'interest'],
            ['question_text' => 'I enjoy work that involves assisting or supporting people directly.', 'category' => 'interest'],

            // PERSONALITY
            ['question_text' => 'I am comfortable completing tasks independently.', 'category' => 'personality'],
            ['question_text' => 'I enjoy working with a team to achieve goals.', 'category' => 'personality'],
            ['question_text' => 'I pay attention to details when completing tasks.', 'category' => 'personality'],
            ['question_text' => 'I can adapt easily to changes or new situations.', 'category' => 'personality'],
            ['question_text' => 'I find it difficult to adapt to sudden changes.', 'category' => 'personality', 'is_reverse' => true],

            // WORK STYLE
            ['question_text' => 'I prefer hands-on and practical activities.', 'category' => 'work_style'],
            ['question_text' => 'I like planning and organizing projects or tasks.', 'category' => 'work_style'],
            ['question_text' => 'I can work effectively in fast-paced environments.', 'category' => 'work_style'],
            ['question_text' => 'I enjoy interacting with people in professional settings.', 'category' => 'work_style'],
            ['question_text' => 'I dislike working in groups.', 'category' => 'work_style', 'is_reverse' => true],

            // VALUES
            ['question_text' => 'I value long-term career stability.', 'category' => 'values'],
            ['question_text' => 'I am motivated by financially rewarding career opportunities.', 'category' => 'values'],
            ['question_text' => 'I value maintaining a healthy work-life balance.', 'category' => 'values'],
            ['question_text' => 'I am interested in careers with continuous growth opportunities.', 'category' => 'values'],

            // TECHNICAL APTITUDE
            ['question_text' => 'I enjoy learning how computer systems and applications work.', 'category' => 'technical'],
            ['question_text' => 'I enjoy solving technical or computer-related problems.', 'category' => 'technical'],
            ['question_text' => 'I am interested in software, applications, or programming concepts.', 'category' => 'technical'],
            ['question_text' => 'I avoid tasks involving technology.', 'category' => 'technical', 'is_reverse' => true],
        ];

        foreach ($questions as $q) {
            // Idempotent: safe to re-run so existing databases pick up
            // newly added items without creating duplicates.
            Question::query()->firstOrCreate(
                ['question_text' => $q['question_text']],
                [
                    'category' => $q['category'],
                    'is_reverse' => $q['is_reverse'] ?? false,
                ]
            );
        }
    }
}
