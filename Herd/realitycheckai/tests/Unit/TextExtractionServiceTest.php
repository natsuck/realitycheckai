<?php

use App\Services\TextExtractionService;
use Illuminate\Support\Facades\Storage;

it('extracts readable text from a pdf marked as encrypted', function () {
    Storage::fake('local');
    Storage::disk('local')->put('uploads/material.pdf', encryptedPdf());

    $text = app(TextExtractionService::class)->extract('uploads/material.pdf');

    expect($text)->toContain('Networking connects devices and allows data exchange across systems.');
});

it('recovers literal text when a pdf has no usable catalog', function () {
    Storage::fake('local');
    Storage::disk('local')->put('uploads/material.pdf', "%PDF-1.4\n".str_repeat('(Networking connects devices and allows data exchange across systems.) Tj\n', 3));

    $text = app(TextExtractionService::class)->extract('uploads/material.pdf');

    expect($text)->toContain('Networking connects devices and allows data exchange across systems.');
});

function encryptedPdf(): string
{
    $content = 'Networking connects devices and allows data exchange across systems. ';
    $stream = "BT /F1 12 Tf 72 720 Td (".str_repeat($content, 3).") Tj ET\n";
    $objects = [
        "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
        "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
        "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R >>\nendobj\n",
        "4 0 obj\n<< /Length ".strlen($stream)." >>\nstream\n{$stream}endstream\nendobj\n",
        "5 0 obj\n<< /Filter /Standard /V 1 /Length 40 /R 2 >>\nendobj\n",
    ];

    $pdf = "%PDF-1.4\n";
    $offsets = [0];

    foreach ($objects as $object) {
        $offsets[] = strlen($pdf);
        $pdf .= $object;
    }

    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 6\n0000000000 65535 f \n";

    foreach (array_slice($offsets, 1) as $offset) {
        $pdf .= sprintf("%010d 00000 n \n", $offset);
    }

    return $pdf."trailer\n<< /Size 6 /Root 1 0 R /Encrypt 5 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";
}