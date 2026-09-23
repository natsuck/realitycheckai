<?php

use App\Services\OllamaService;
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

function fakeOllamaResponse(array|string $body): void
{
    Http::fake([
        'http://localhost:11434/*' => Http::response($body),
    ]);
}

it('normalizes a valid structured ai recommendation', function () {
    fakeOllamaResponse([
        'response' => json_encode([
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
        ]),
    ]);

    $result = (new OllamaService)->analyze([
        assessmentRow('I enjoy solving logical problems.', 'skills', 5),
        assessmentRow('I am interested in computer technology.', 'technical', 5),
    ]);

    expect($result['source'])->toBe('ollama')
        ->and($result['recommended_program']['code'])->toBe('BSIT')
        ->and($result['recommended_program']['name'])
        ->toBe('Bachelor of Science in Information Technology')
        ->and($result['confidence'])->toBe(91)
        // Unknown alternative codes are replaced with a catalog entry
        ->and($result['alternative_programs'][1]['code'])->toBe('BSIT');
});

it('clamps out-of-range confidence values', function () {
    fakeOllamaResponse([
        'response' => json_encode([
            'recommended_program' => ['code' => 'BSCS', 'name' => 'CS'],
            'confidence' => 150,
        ]),
    ]);

    $result = (new OllamaService)->analyze([
        assessmentRow('I enjoy solving logical problems.', 'skills', 5),
    ]);

    expect($result['confidence'])->toBe(100);
});

it('replaces an unknown program code with a deterministic trait match', function () {
    fakeOllamaResponse([
        'response' => json_encode([
            'recommended_program' => ['code' => 'BSPHILO', 'name' => 'Not offered'],
            'confidence' => 80,
        ]),
    ]);

    $result = (new OllamaService)->analyze([
        assessmentRow('I enjoy solving logical problems.', 'skills', 5),
        assessmentRow('I am interested in computer technology.', 'technical', 5),
    ]);

    expect($result['recommended_program']['code'])->toBe('BSCS');
});

it('produces a marked fallback when the ai output is not json', function () {
    fakeOllamaResponse([
        'response' => 'Sure! Here is your recommendation, dear student.',
    ]);

    $result = (new OllamaService)->analyze([
        assessmentRow('I enjoy helping people.', 'interest', 5),
        assessmentRow('I am interested in business management.', 'interest', 5),
    ]);

    expect($result['source'])->toBe('fallback')
        ->and($result['fit_level'])->toBe('Preliminary Fit')
        ->and($result['error'])->toContain('JSON object')
        ->and($result['disclaimer'])->toContain('guidance');
});

it('produces a marked fallback when the ollama service fails', function () {
    Http::fake([
        'http://localhost:11434/*' => Http::response('server exploded', 500),
    ]);

    $result = (new OllamaService)->analyze([
        assessmentRow('I enjoy solving logical problems.', 'skills', 5),
    ]);

    expect($result['source'])->toBe('fallback')
        ->and($result['error'])->toContain('500');
});

it('maps technical-heavy patterns to computing programs and social-heavy to service programs', function () {
    $service = new OllamaService;

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
    $service = new OllamaService;

    $method = new ReflectionMethod($service, 'fallbackProgramCode');

    $code = $method->invoke($service, [
        assessmentRow('Completely reworded item with zero keywords.', 'technical', 5),
        assessmentRow('Another unrecognizable statement here.', 'technical', 5),
    ]);

    expect($code)->toBe('BSCS');
});
