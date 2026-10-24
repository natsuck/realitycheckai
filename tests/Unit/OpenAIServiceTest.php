<?php

use App\Services\OpenAIService;
use Illuminate\Support\Facades\Http;

function assessmentRow(string $question, string $category, int $answer): array
{
    return [
        'question' => $question,
        'category' => $category,
        'category_label' => ucfirst($category),
        'answer' => $answer,
        'answer_label' => 'Agree',
    ];
}

function fakeOpenAIResponse(array|string $body): void
{
    $content = is_array($body) ? $body : $body;

    Http::fake([
        'https://api.openai.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => $content]],
            ],
        ]),
    ]);
}

it('normalizes a valid structured ai recommendation', function () {
    fakeOpenAIResponse(json_encode([
        'recommended_program' => [
            'code' => 'bsit',
            'name' => 'Whatever the model said',
        ],
        'confidence' => '91',
        'fit_level' => 'Strong Fit',
        'reason' => 'Technical alignment.',
        'strengths' => ['Logic', 'Technology'],
        'alternative_programs' => [
            ['code' => 'BSCS', 'name' => 'CS', 'reason' => 'Related.'],
            ['code' => 'NOPE', 'name' => 'Invalid', 'reason' => 'Bad code.'],
        ],
    ]));

    $result = (new OpenAIService)->analyze([
        assessmentRow('I enjoy solving logical problems.', 'skills', 5),
        assessmentRow('I am interested in computer technology.', 'technical', 5),
    ]);

    expect($result['source'])->toBe('openai')
        ->and($result['recommended_program']['code'])->toBe('BSIT')
        ->and($result['recommended_program']['name'])
        ->toBe('Bachelor of Science in Information Technology')
        ->and($result['confidence'])->toBe(91)
        // Unknown alternative codes are replaced with a catalog entry
        ->and($result['alternative_programs'][1]['code'])->toBe('BSIT');
});

it('clamps out-of-range confidence values', function () {
    fakeOpenAIResponse(json_encode([
        'recommended_program' => ['code' => 'BSCS', 'name' => 'CS'],
        'confidence' => 150,
    ]));

    $result = (new OpenAIService)->analyze([
        assessmentRow('I enjoy solving logical problems.', 'skills', 5),
    ]);

    expect($result['confidence'])->toBe(100);
});

it('replaces an unknown program code with a deterministic trait match', function () {
    fakeOpenAIResponse(json_encode([
        'recommended_program' => ['code' => 'BSPHILO', 'name' => 'Not offered'],
        'confidence' => 80,
    ]));

    $result = (new OpenAIService)->analyze([
        assessmentRow('I enjoy solving logical problems.', 'skills', 5),
        assessmentRow('I am interested in computer technology.', 'technical', 5),
    ]);

    expect($result['recommended_program']['code'])->toBe('BSCS');
});

it('produces a marked fallback when the ai output is not json', function () {
    fakeOpenAIResponse('Sure! Here is your recommendation, dear student.');

    $result = (new OpenAIService)->analyze([
        assessmentRow('I enjoy helping people.', 'interest', 5),
        assessmentRow('I am interested in business management.', 'interest', 5),
    ]);

    expect($result['source'])->toBe('fallback')
        ->and($result['fit_level'])->toBe('Preliminary Fit')
        ->and($result['error'])->toContain('JSON object')
        ->and($result['disclaimer'])->toContain('guidance');
});

it('produces a marked fallback when the openai service fails', function () {
    Http::fake([
        'https://api.openai.com/*' => Http::response('server exploded', 500),
    ]);

    $result = (new OpenAIService)->analyze([
        assessmentRow('I enjoy solving logical problems.', 'skills', 5),
    ]);

    expect($result['source'])->toBe('fallback')
        ->and($result['error'])->toContain('500');
});

it('surfaces a specific connectivity message when DNS cannot resolve the OpenAI host', function () {
    Http::fake([
        'https://api.openai.com/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException(
            'cURL error 6: Could not resolve host: api.openai.com'
        ),
    ]);

    expect(fn () => (new OpenAIService)->generate('Generate a quiz.'))
        ->toThrow(\App\Exceptions\OpenAIRequestException::class, 'Check internet/DNS connectivity');
});

it('returns transparent exploratory program matches from the 150-item score profile', function () {
    fakeOpenAIResponse(json_encode([
        'reason' => 'Your interest scores suggest several programs worth exploring.',
        'strengths' => ['Investigative interests'],
        'development_areas' => [],
        'feedback' => 'These are suggestions, and your choice is yours.',
        'evidence' => ['Investigative average: 4.2 / 5'],
        'next_steps' => ['Review program curricula with a counselor.'],
        'program_matches' => [
            ['code' => 'BSIT', 'reason' => 'Technology-related interests may align.', 'evidence' => ['Investigative: 4.2 / 5'], 'explore' => 'Compare course offerings.'],
            ['code' => 'BSCS', 'reason' => 'Analytical interests may align.', 'evidence' => ['Investigative: 4.2 / 5'], 'explore' => 'Review sample computing courses.'],
            ['code' => 'BSAIS', 'reason' => 'Organized analysis may be relevant.', 'evidence' => ['Conventional: 3.8 / 5'], 'explore' => 'Ask about accounting systems coursework.'],
            ['code' => 'BSTM', 'reason' => 'Some interests may align with tourism operations.', 'evidence' => ['Social: 3.5 / 5'], 'explore' => 'Explore tourism-related activities.'],
            ['code' => 'BSHM', 'reason' => 'Some interests may align with hospitality service.', 'evidence' => ['Social: 3.5 / 5'], 'explore' => 'Talk to hospitality students.'],
        ],
    ]));

    $result = (new OpenAIService)->analyzeAssessmentProfile([
        'interest' => [
            'I' => ['label' => 'Investigative', 'average' => 4.2, 'percentage' => 84, 'items' => 15],
            'C' => ['label' => 'Conventional', 'average' => 3.8, 'percentage' => 76, 'items' => 15],
            'S' => ['label' => 'Social', 'average' => 3.5, 'percentage' => 70, 'items' => 15],
        ],
        'aptitude' => [
            'verbal' => ['label' => 'Verbal', 'correct' => 14, 'total' => 20, 'percentage' => 70],
            'quantitative' => ['label' => 'Quantitative', 'correct' => 12, 'total' => 20, 'percentage' => 60],
            'analytical' => ['label' => 'Analytical', 'correct' => 16, 'total' => 20, 'percentage' => 80],
        ],
    ]);

    expect($result['source'])->toBe('openai')
        ->and($result['recommended_program']['code'])->toBe('BSIT')
        ->and($result['recommended_program']['name'])->toBe('Bachelor of Science in Information Technology')
        ->and($result['program_matches'])->toHaveCount(5)
        ->and($result['program_matches'][0]['code'])->toBe('BSIT')
        ->and($result['confidence'])->toBe(0)
        ->and($result['disclaimer'])->toContain('not a validated NCAE mapping')
        ->and($result['disclaimer'])->toContain('Your choice is yours');
});

it('does not return fabricated program matches when AI output contains invalid programs', function () {
    fakeOpenAIResponse(json_encode([
        'reason' => 'Profile summary.',
        'feedback' => 'Guidance only.',
        'program_matches' => [
            ['code' => 'FAKE', 'reason' => 'Invalid.', 'evidence' => [], 'explore' => 'Invalid.'],
        ],
    ]));

    $result = (new OpenAIService)->analyzeAssessmentProfile([
        'interest' => [],
        'aptitude' => [],
    ]);

    expect($result['source'])->toBe('fallback')
        ->and($result['program_matches'])->toBe([])
        ->and($result['recommended_program']['code'])->toBe('PENDING');
});

it('maps technical-heavy patterns to computing programs and social-heavy to service programs', function () {
    $service = new OpenAIService;

    $method = new ReflectionMethod($service, 'fallbackProgramCode');

    $technicalCode = $method->invoke($service, [
        assessmentRow('I love computer systems and software.', 'technical', 5),
        assessmentRow('I like hands-on practical activities.', 'work_style', 5),
        assessmentRow('I enjoy applied real-world tasks.', 'work_style', 5),
    ]);

    $socialCode = $method->invoke($service, [
        assessmentRow('I enjoy communicating and helping people.', 'interest', 5),
        assessmentRow('I like planning business tasks.', 'interest', 5),
        assessmentRow('I enjoy interacting with people.', 'work_style', 5),
    ]);

    expect($technicalCode)->toBe('BSIT')
        ->and($socialCode)->toBe('BSHM');
});

it('still weighs reworded questions through category-level defaults', function () {
    $service = new OpenAIService;

    $method = new ReflectionMethod($service, 'fallbackProgramCode');

    $code = $method->invoke($service, [
        assessmentRow('Completely reworded item with zero keywords.', 'technical', 5),
        assessmentRow('Another unrecognizable statement here.', 'technical', 5),
    ]);

    expect($code)->toBe('BSCS');
});
