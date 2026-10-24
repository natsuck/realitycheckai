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

        $interestItems = [
            'R' => [
                'I would enjoy assembling, operating, or setting up physical equipment, tools, or devices.',
                'I would enjoy troubleshooting and solving technical or operational problems when things are not working properly.',
                'I would enjoy assembling, operating, or setting up physical equipment, tools, or devices.',
                'I would like to work with technical tools and equipment.',
                'I would enjoy learning how to set up, customize, and use new software applications or digital tools.',
                'I would enjoy maintaining, servicing, or repairing physical equipment, tools, or devices.',
                'I would enjoy arranging and setting up physical spaces, equipment, or systems for an event or project.',
                'I enjoy discovering how different tools, equipment, or machinery are designed to perform specific tasks.',
                'I would enjoy operating technical equipment in a workplace.',
                'I would like to assemble, setup, or configure physical equipment and tools based on specific user or operational needs.',
                'I would enjoy preparing and arranging equipment and physical resources for daily operations or special projects.',
                'I would like to work in an environment where I regularly handle equipment.',
                'I would enjoy solving practical problems involving physical equipment, tools, or operational processes.',
                'I would like to perform tasks that require careful physical coordination.',
                'I would prefer a career focused on managing and operating physical equipment or practical systems.',
            ],
            'I' => [
                'I would enjoy solving challenging mathematical problems.',
                'I would like to understand how things work by examining their different parts and processes.',
                'I would enjoy analyzing data to discover patterns.',
                'I would like to find solutions to problems by analyzing the causes and possible outcomes.',
                'I would enjoy solving logical puzzles.',
                'I would like to investigate why something does not work as expected.',
                'I would enjoy learning new concepts and finding out how they can be applied to solve problems.',
                'I would like to analyze information before making a decision.',
                'I would enjoy conducting research about a topic that interests me.',
                'I would like to collect, organize, and analyze information to understand a problem or situation.',
                'I would enjoy solving problems that require several logical steps.',
                'I would like to examine information carefully to identify errors, patterns, or inconsistencies.',
                'I would enjoy studying how technology can improve business operations.',
                'I would like to investigate problems using evidence rather than guesses.',
                'I would prefer a career that regularly requires analytical and critical thinking.',
            ],
            'A' => [
                'I would enjoy designing posters or promotional materials.',
                'I would like to create graphics using digital tools.',
                'I would enjoy designing visually appealing layouts for digital content, displays, or promotional materials.',
                'I would enjoy creating engaging video content or visual stories to share with an audience.',
                'I would enjoy photography.',
                'I would like to create attractive presentations.',
                'I would enjoy designing logos or brand identities.',
                'I would like to write creative content for social media.',
                'I would enjoy planning the visual design of an event.',
                'I would like to edit photographs or videos.',
                'I would enjoy creating advertisements for a business.',
                'I would enjoy developing creative marketing campaigns or visual concepts to promote a project, brand, or service.',
                'I would enjoy experimenting with different layouts, colors, and designs.',
                'I would like to create digital content to communicate an idea.',
                'I would prefer a career where creativity and self-expression are important.',
            ],
            'S' => [
                'I would enjoy helping customers solve their concerns.',
                'I would like to teach another person how to perform a task.',
                'I would enjoy communicating with people from different backgrounds.',
                'I would like to help others have a positive experience when receiving assistance or support.',
                'I would enjoy assisting and supporting people when they need help.',
                'I would like to work with people as part of a team.',
                'I would enjoy organizing activities for groups of people.',
                'I would like to listen to someone\'s concerns and help them.',
                'I would enjoy explaining information to customers.',
                'I would like to assist people who need guidance.',
                'I would enjoy meeting new people regularly.',
                'I would like to work in customer service.',
                'I would enjoy helping classmates understand difficult lessons.',
                'I would like to make people feel comfortable and welcome.',
                'I would prefer a career where interacting with and helping people is important.',
            ],
            'E' => [
                'I would enjoy leading a group project.',
                'I would like to start my own business someday.',
                'I would enjoy presenting a business idea to other people.',
                'I would like to develop marketing strategies.',
                'I would enjoy negotiating with customers or business partners.',
                'I would like to manage a team.',
                'I would enjoy organizing a business or school event.',
                'I would like to make important decisions for an organization.',
                'I would enjoy promoting a product or service.',
                'I would like to take the lead in organizing activities or projects and making decisions to achieve a goal.',
                'I would enjoy persuading people to support an idea.',
                'I would like to create a business plan.',
                'I would enjoy taking responsibility for a major project.',
                'I would like to manage employees or team members.',
                'I would prefer a career that provides opportunities for leadership and entrepreneurship.',
            ],
            'C' => [
                'I would enjoy organizing information in a clear and orderly manner.',
                'I would like to work with spreadsheets.',
                'I would enjoy checking records for errors.',
                'I would like to organize digital files and documents.',
                'I would enjoy keeping business records accurate.',
                'I would like to manage an inventory system.',
                'I would enjoy working with numerical information.',
                'I would like to organize customer or employee records.',
                'I would enjoy following established procedures.',
                'I would like to maintain organized records and keep information up to date.',
                'I would enjoy checking information carefully to make sure it is accurate and complete.',
                'I would like to prepare organized reports.',
                'I would enjoy managing schedules, reservations, or appointments.',
                'I would like to make sure important documents are complete and properly organized.',
                'I would prefer a career that requires organization, accuracy, and systematic work.',
            ],
        ];

        $itemNumber = 1;

        foreach ($interestItems as $riasec => $items) {
            foreach ($items as $text) {
                Question::query()->updateOrCreate(
                    [
                        'instrument_version' => Question::CURRENT_INSTRUMENT_VERSION,
                        'item_number' => $itemNumber,
                    ],
                    [
                        'question_text' => $text,
                        'category' => $riasec,
                        'section' => 'interest',
                        'question_type' => 'likert',
                        'riasec_type' => $riasec,
                        'is_reverse' => false,
                        'choices' => null,
                        'correct_answer' => null,
                    ]
                );

                $itemNumber++;
            }
        }

        $aptitudeItems = [
            ['verbal', 'GENEROUS is to SELFISH as HONEST is to:', ['Kind', 'Careful', 'Dishonest', 'Friendly'], 3],
            ['verbal', 'RAPID is to SLOW as DIFFICULT is to:', ['Hard', 'Easy', 'Complex', 'Serious'], 2],
            ['verbal', 'HYPOTHESIS : EXPERIMENT :: ARGUMENT : ______', ['Evidence', 'Emotion', 'Memory', 'Habit'], 1],
            ['verbal', 'MUSICIAN : COMPOSITION :: ARCHITECT : ______', ['Blueprint', 'Building', 'Hammer', 'Measurement'], 2],
            ['verbal', 'Which word is closest in meaning to ACCURATE?', ['Correct', 'Fast', 'Large', 'Difficult'], 1],
            ['verbal', 'Which word is opposite in meaning to EXPAND?', ['Increase', 'Develop', 'Contract', 'Improve'], 3],
            ['verbal', 'Which word does NOT belong?', ['Apple', 'Mango', 'Banana', 'Carrot'], 4],
            ['verbal', 'Which item does NOT belong with the others?', ['Keyboard', 'Mouse', 'Monitor', 'Paper notebook'], 4],
            ['verbal', 'A student creates a regular study schedule, reviews lessons every week, and asks teachers for clarification when concepts are unclear. What is the main idea?', ['Students should study only before exams.', 'Regular study habits can improve learning.', 'Teachers should give easier examinations.', 'Students should avoid asking questions.'], 2],
            ['verbal', 'Why does the student ask teachers for clarification?', ['To avoid studying', 'To understand unclear concepts', 'To finish school early', 'To avoid examinations'], 2],
            ['verbal', 'A hotel finds that employees repeatedly enter the same information into different systems. What problem did the hotel identify?', ['Too many customers', 'Repeated data entry', 'Poor hotel facilities', 'Lack of employees'], 2],
            ['verbal', 'What solution did the hotel manager choose?', ['Close the hotel', 'Hire fewer employees', 'Connect the systems', 'Remove the check-in process'], 3],
            ['verbal', 'A small business keeps financial records in several separate spreadsheets. What is the main problem?', ['Too many customers', 'Poorly organized information management', 'Not enough products', 'No financial activity'], 2],
            ['verbal', 'What would most likely help solve the problem?', ['Delete all records', 'Stop recording transactions', 'Use a more organized information system', 'Reduce the number of customers'], 3],
            ['verbal', 'Neither the students nor the instructor ______ aware of the schedule change.', ['were', 'was', 'are', 'have been'], 2],
            ['verbal', 'The manager reviewed the report carefully ______ approving the proposal.', ['because', 'before', 'although', 'unless'], 2],
            ['verbal', 'Which statement is most logical?', ['All programmers are accountants.', 'Some students interested in technology may enjoy programming.', 'Every hotel employee is a programmer.', 'No student can learn programming.'], 2],
            ['verbal', 'If all managers are employees and Ana is a manager, what can be concluded?', ['Ana is an employee.', 'Ana is a customer.', 'Ana owns the company.', 'Ana is a student.'], 1],
            ['verbal', 'What word best describes a person who carefully examines information for mistakes?', ['Thorough', 'Careless', 'Impulsive', 'Flexible'], 1],
            ['verbal', 'Technology allows businesses to collect, organize, and analyze information more efficiently. What is the best summary?', ['Technology prevents businesses from collecting information.', 'Technology can improve information management.', 'Businesses no longer need employees.', 'Information cannot be analyzed.'], 2],

            ['quantitative', 'A student scored 78, 85, and 92 on three tests. What is the student’s average score?', ['52', '62', '72', '85'], 4],
            ['quantitative', 'A project has a budget of ₱5,000. If ₱3,275 has already been spent, how much of the budget remains?', ['₱7,912', '₱1,725', '₱6,334', '₱1,672'], 2],
            ['quantitative', 'A student has 240 minutes available to complete four tasks. If the first three tasks take 45, 60, and 50 minutes, how many minutes remain for the fourth task?', ['85 minutes', '96 minutes', '106 minutes', '116 minutes'], 1],
            ['quantitative', 'A class of 36 students is divided equally into groups. If each group must have between 5 and 7 students, which group size is possible?', ['10', '11', '6', '14'], 3],
            ['quantitative', 'A student receives scores of 80, 85, and 90. What is the average?', ['80', '82', '85', '90'], 3],
            ['quantitative', 'A product costs 500 and is discounted by 20%. What is the discount?', ['50', '75', '100', '125'], 3],
            ['quantitative', 'After a 20% discount on a 500 product, what is the final price?', ['350', '400', '450', '480'], 2],
            ['quantitative', 'If 5 notebooks cost 150, how much does one notebook cost?', ['20', '25', '30', '35'], 3],
            ['quantitative', 'A class has 40 students. If 25% are absent, how many are absent?', ['5', '10', '15', '20'], 2],
            ['quantitative', 'What comes next? 2, 4, 8, 16, ____', ['20', '24', '30', '32'], 4],
            ['quantitative', 'What comes next? 5, 10, 15, 20, ____', ['21', '23', '25', '30'], 3],
            ['quantitative', 'If x + 15 = 40, what is x?', ['15', '20', '25', '30'], 3],
            ['quantitative', 'If 3x = 27, what is x?', ['6', '7', '8', '9'], 4],
            ['quantitative', 'A restaurant has 120 customers in one day. 30% order a particular meal. How many customers ordered it?', ['24', '30', '36', '40'], 3],
            ['quantitative', 'A hotel has 50 rooms. 40 rooms are occupied. What percentage are occupied?', ['60%', '70%', '80%', '90%'], 3],
            ['quantitative', 'A business earns 10,000 and spends 7,000. What is the remaining amount?', ['2,000', '3,000', '4,000', '5,000'], 2],
            ['quantitative', 'A trip takes 3 hours and begins at 8:00 AM. What time does it end?', ['10:00 AM', '11:00 AM', '12:00 PM', '1:00 PM'], 2],
            ['quantitative', 'A database contains 500 records. 10% contain errors. How many records contain errors?', ['5', '25', '50', '100'], 3],
            ['quantitative', 'A rectangular garden has a length of 8 meters and a width of 6 meters. What is the length of the diagonal?', ['8 m', '9 m', '10 m', '12 m'], 3],
            ['quantitative', 'A student saves 200 every week. How much after 10 weeks?', ['1,000', '1,500', '2,000', '2,500'], 3],

            ['analytical', 'A computer suddenly shuts down because it has no power. What should be checked first?', ['Internet speed', 'Power connection', 'Keyboard language', 'Screen brightness'], 2],
            ['analytical', 'A program produces an error after a recent change. What is the most logical first step?', ['Delete the entire program', 'Identify and review the recent change', 'Replace the computer', 'Ignore the error'], 2],
            ['analytical', 'A customer complains about a service. What should be done first?', ['Ignore the complaint', 'Listen and identify the customer’s concern', 'Blame another employee', 'Immediately end the conversation'], 2],
            ['analytical', 'A hotel reservation system shows two reservations for the same room at the same time. What type of problem is this?', ['Duplicate/conflicting data', 'Hardware damage', 'Weather problem', 'Marketing problem'], 1],
            ['analytical', 'A business notices that its expenses are increasing every month. What should management do first?', ['Guess the reason', 'Analyze the expense records', 'Stop all business operations', 'Delete the records'], 2],
            ['analytical', 'A student gets different answers from two sources. What should the student do?', ['Choose randomly', 'Verify the information using reliable evidence', 'Ignore both sources', 'Always choose the first answer'], 2],
            ['analytical', 'A network connects 5 computers and one cannot access it. What should be investigated first?', ['Whether the affected computer has a network connection', 'Whether other computers should be deleted', 'Whether the monitor is large enough', 'Whether the keyboard is wireless'], 1],
            ['analytical', 'A restaurant receives fewer customers on weekdays than weekends. Which information is most useful?', ['Customer numbers by day', 'Employee birthdays', 'Wall color', 'Number of tables only'], 1],
            ['analytical', 'An employee notices that the total in a financial report does not match individual transactions. What should be done?', ['Ignore the difference', 'Check the transactions and calculations', 'Delete the report', 'Change the total without checking'], 2],
            ['analytical', 'A student wants to determine whether regular studying improves test scores. Which approach is appropriate?', ['Compare study habits and test results', 'Ask only one student', 'Guess the answer', 'Avoid collecting information'], 1],
            ['analytical', 'Plants in sunlight grow faster than plants in darkness. What factor is being investigated?', ['Light exposure', 'Computer speed', 'Internet connection', 'Room size'], 1],
            ['analytical', 'A database contains duplicate customer records. What is the appropriate action?', ['Identify and remove duplicates carefully', 'Delete the entire database', 'Add more duplicates', 'Stop using customer records'], 1],
            ['analytical', 'A travel agency wants to know which destination is most popular. What data is useful?', ['Customer destination choices', 'Employee shoe sizes', 'Office temperature', 'Computer screen sizes'], 1],
            ['analytical', 'If one solution does not solve a problem, what should happen next?', ['Give up immediately', 'Analyze why it failed and consider another solution', 'Pretend the problem does not exist', 'Repeat the same action without changes'], 2],
            ['analytical', 'A hotel notices frequent complaints about slow check-in. What information helps identify the cause?', ['Average check-in time and process steps', 'Hotel wall color', 'Guest clothing', 'Weather from last year'], 1],
            ['analytical', 'A program gives an unexpected result when input is 0. What should the programmer investigate?', ['How the program handles 0', 'Monitor size', 'Office furniture', 'Computer wallpaper'], 1],
            ['analytical', 'A company wants to know whether a new system reduces processing time. What should be compared?', ['Processing time before and after implementation', 'Employee ages', 'Office locations', 'Computer colors'], 1],
            ['analytical', 'A tourism manager notices visitor numbers increase during holidays. What can be investigated?', ['The relationship between holidays and visitor numbers', 'Whether all tourists dislike weekdays', 'Whether hotels should close during holidays', 'Whether visitor numbers never change'], 1],
            ['analytical', 'A student gets a low mathematics score. Which action demonstrates analytical thinking?', ['Identify the types of mistakes made', 'Assume the test was unfair', 'Stop studying mathematics', 'Ignore the result'], 1],
            ['analytical', 'A business has three possible solutions. How should it choose among them?', ['Select one randomly', 'Compare advantages, disadvantages, cost, and expected results', 'Choose the most expensive solution', 'Choose the first solution presented'], 2],
        ];

        $choiceLabels = ['A', 'B', 'C', 'D'];

        foreach ($aptitudeItems as $index => [$section, $text, $choices, $correctAnswer]) {
            Question::query()->updateOrCreate(
                [
                    'instrument_version' => Question::CURRENT_INSTRUMENT_VERSION,
                    'item_number' => $index + 91,
                ],
                [
                    'question_text' => $text,
                    'category' => $section,
                    'section' => $section,
                    'question_type' => 'multiple_choice',
                    'riasec_type' => null,
                    'is_reverse' => false,
                    'choices' => array_map(
                        fn (string $label, string $choice): array => [
                            'label' => $label,
                            'text' => $choice,
                        ],
                        $choiceLabels,
                        $choices
                    ),
                    'correct_answer' => $correctAnswer,
                ]
            );
        }
    }
}
