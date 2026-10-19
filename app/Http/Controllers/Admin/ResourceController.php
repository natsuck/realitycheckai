<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ResourceController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->string('type')->toString();
        $type = array_key_exists($type, Resource::TYPES) ? $type : Resource::TYPE_CET;

        $query = Resource::query()->ofType($type)->with('schools')->ordered();

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($inner) use ($search): void {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('grantor', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $resources = $query->paginate(12)->withQueryString();

        $counts = Resource::query()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $resourcePayload = $resources->getCollection()->map(fn (Resource $resource): array => [
            'id' => $resource->id,
            'type' => $resource->type,
            'title' => $resource->title,
            'summary' => $resource->summary,
            'link' => $resource->link,
            'is_published' => $resource->is_published,
            'sort_order' => $resource->sort_order,
            'period' => $resource->period,
            'coverage' => $resource->coverage,
            'tips' => $resource->tips,
            'grantor' => $resource->grantor,
            'criteria' => $resource->criteria,
            'opens_at' => $resource->opens_at?->format('Y-m-d'),
            'closes_at' => $resource->closes_at?->format('Y-m-d'),
            'short_name' => $resource->short_name,
            'location' => $resource->location,
            'institution_type' => $resource->institution_type,
            'top_programs' => $resource->top_programs ? implode(', ', $resource->top_programs) : '',
            'entrance_exam' => $resource->entrance_exam,
            'tuition_range' => $resource->tuition_range,
            'website' => $resource->website,
            'logo_bg' => $resource->logo_bg,
            'emoji' => $resource->emoji,
            'subtitle' => $resource->subtitle,
            'alignments' => $resource->alignments,
            'school_ids' => $resource->schools->pluck('id')->all(),
        ])->values()->all();

        return view('admin.resources.index', [
            'resources' => $resources,
            'resourcePayload' => $resourcePayload,
            'type' => $type,
            'types' => Resource::TYPES,
            'counts' => $counts,
            'schools' => School::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateResource($request);

        DB::transaction(function () use ($validated, $request): void {
            $resource = Resource::query()->create($this->attributes($validated));
            $resource->schools()->sync($validated['school_ids'] ?? []);
        });

        return redirect()
            ->route('admin.resources.index', ['type' => $validated['type']])
            ->with('status', 'Resource created successfully.');
    }

    public function update(Request $request, Resource $resource): RedirectResponse
    {
        $validated = $this->validateResource($request, $resource);

        DB::transaction(function () use ($resource, $validated): void {
            $resource->update($this->attributes($validated));
            $resource->schools()->sync($validated['school_ids'] ?? []);
        });

        return redirect()
            ->route('admin.resources.index', ['type' => $resource->type])
            ->with('status', 'Resource updated successfully.');
    }

    public function destroy(Resource $resource): RedirectResponse
    {
        $type = $resource->type;
        $title = $resource->title;
        $resource->delete();

        return redirect()
            ->route('admin.resources.index', ['type' => $type])
            ->with('status', '"'.$title.'" was deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateResource(Request $request, ?Resource $resource = null): array
    {
        $type = $request->input('type', $resource?->type);

        $rules = [
            'type' => ['required', 'in:'.implode(',', array_keys(Resource::TYPES))],
            'title' => ['required', 'string', 'max:200'],
            'summary' => ['nullable', 'string', 'max:500'],
            'link' => ['nullable', 'url', 'max:255'],
            'is_published' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'school_ids' => ['nullable', 'array'],
            'school_ids.*' => ['integer', 'exists:schools,id'],
        ];

        $typeRules = match ($type) {
            Resource::TYPE_CET => [
                'period' => ['nullable', 'string', 'max:120'],
                'coverage' => ['nullable', 'string', 'max:1000'],
                'tips' => ['nullable', 'string', 'max:2000'],
            ],
            Resource::TYPE_SCHOLARSHIP => [
                'grantor' => ['nullable', 'string', 'max:200'],
                'coverage' => ['nullable', 'string', 'max:1000'],
                'criteria' => ['nullable', 'string', 'max:2000'],
                'opens_at' => ['nullable', 'date'],
                'closes_at' => ['nullable', 'date', 'after_or_equal:opens_at'],
            ],
            Resource::TYPE_UNIVERSITY => [
                'short_name' => ['nullable', 'string', 'max:20'],
                'location' => ['nullable', 'string', 'max:200'],
                'institution_type' => ['nullable', 'string', 'max:120'],
                'top_programs' => ['nullable', 'string', 'max:1000'],
                'entrance_exam' => ['nullable', 'string', 'max:120'],
                'tuition_range' => ['nullable', 'string', 'max:200'],
                'website' => ['nullable', 'url', 'max:255'],
                'logo_bg' => ['nullable', 'string', 'max:20'],
            ],
            Resource::TYPE_STRAND => [
                'emoji' => ['nullable', 'string', 'max:10'],
                'subtitle' => ['nullable', 'string', 'max:200'],
                'alignments' => ['nullable', 'string', 'max:2000'],
            ],
            default => [],
        };

        return $request->validate($rules + $typeRules);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributes(array $validated): array
    {
        $attributes = [
            'type' => $validated['type'],
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'summary' => $validated['summary'] ?? null,
            'link' => $validated['link'] ?? null,
            'is_published' => (bool) ($validated['is_published'] ?? false),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ];

        $fields = match ($validated['type']) {
            Resource::TYPE_CET => ['period', 'coverage', 'tips'],
            Resource::TYPE_SCHOLARSHIP => ['grantor', 'coverage', 'criteria', 'opens_at', 'closes_at'],
            Resource::TYPE_UNIVERSITY => ['short_name', 'location', 'institution_type', 'entrance_exam', 'tuition_range', 'website', 'logo_bg'],
            Resource::TYPE_STRAND => ['emoji', 'subtitle', 'alignments'],
            default => [],
        };

        foreach ($fields as $field) {
            $attributes[$field] = $validated[$field] ?? null;
        }

        if ($validated['type'] === Resource::TYPE_UNIVERSITY) {
            $programs = trim((string) ($validated['top_programs'] ?? ''));
            $attributes['top_programs'] = $programs === ''
                ? null
                : array_values(array_filter(array_map('trim', explode(',', $programs))));
        }

        return $attributes;
    }
}
