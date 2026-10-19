<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use Illuminate\View\View;

class ResourceController extends Controller
{
    public function index(): View
    {
        $published = Resource::query()
            ->published()
            ->with('schools')
            ->ordered()
            ->get();

        return view('resources.index', [
            'collegeExams' => $published->where('type', Resource::TYPE_CET)->values(),
            'scholarships' => $published->where('type', Resource::TYPE_SCHOLARSHIP)->values(),
            'universities' => $published->where('type', Resource::TYPE_UNIVERSITY)->values(),
            'strands' => $published->where('type', Resource::TYPE_STRAND)->values(),
        ]);
    }

    /**
     * Build the payload consumed by the public detail modal.
     *
     * @param  array<int, array{label: string, value: mixed}>  $sections
     * @param  array<int, string>  $schools
     * @return array<string, mixed>
     */
    public static function detailPayload(string $title, ?string $badge, array $sections, array $schools, ?string $link): array
    {
        return [
            'title' => $title,
            'badge' => $badge,
            'sections' => array_values(array_filter(
                $sections,
                fn (array $section): bool => filled($section['value']),
            )),
            'schools' => $schools,
            'link' => $link,
        ];
    }
}
