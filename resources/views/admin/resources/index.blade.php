<x-app-layout title="Resource Center · Admin">
    <x-admin-layout active="resources">
        <div x-data="resourceManager(@js($resourcePayload))" class="flex flex-col gap-8">

            {{-- Header --}}
            <div class="flex flex-col sm:flex-row justify-between sm:items-end gap-4">
                <div>
                    <h1 class="font-headline text-2xl sm:text-3xl font-bold text-primary">Resource Center</h1>
                    <p class="text-sm text-ink-muted mt-1">Manage CETs, scholarships &amp; grants, universities, and SHS strands.</p>
                </div>
                <button type="button" @click="create()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-accent hover:bg-accent-hover text-primary text-sm font-bold shadow-md transition-all shrink-0">
                    <x-icon name="sparkles" class="w-4 h-4"/>
                    New Resource
                </button>
            </div>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Type tabs --}}
            <div class="flex flex-wrap gap-2 border-b border-slate-300/40 pb-4">
                @foreach ($types as $key => $label)
                    <a href="{{ route('admin.resources.index', ['type' => $key]) }}"
                       class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-2 transition-all {{ $type === $key ? 'bg-primary text-white shadow-md' : 'bg-white text-ink-muted border border-surface-high hover:bg-surface-low' }}">
                        <span>{{ $label }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] {{ $type === $key ? 'bg-white/20' : 'bg-surface-low text-primary-container' }}">{{ $counts[$key] ?? 0 }}</span>
                    </a>
                @endforeach
            </div>

            {{-- Search --}}
            <form method="GET" action="{{ route('admin.resources.index') }}" class="flex gap-3">
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Search by title, grantor, or location..."
                       class="flex-1 rounded-xl border-surface-high bg-white text-ink placeholder:text-ink-faint shadow-sm focus:border-accent focus:ring-accent">
                <button type="submit" class="rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-white hover:bg-primary-container">Search</button>
            </form>

            {{-- List --}}
            <div class="flex flex-col gap-4 pb-8">
                @forelse ($resources as $resource)
                    <article class="card p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="font-headline font-bold text-base text-primary truncate">{{ $resource->title }}</h3>
                                @if ($resource->is_published)
                                    <span class="text-[10px] uppercase font-bold tracking-wider text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">Published</span>
                                @else
                                    <span class="text-[10px] uppercase font-bold tracking-wider text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full">Draft</span>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-ink-muted truncate">
                                {{ $resource->grantor ?? $resource->location ?? $resource->subtitle ?? $resource->period ?? '—' }}
                                @if ($resource->schools->isNotEmpty())
                                    · {{ $resource->schools->pluck('short_name')->filter()->implode(', ') ?: $resource->schools->count().' school(s)' }}
                                @endif
                            </p>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button"
                                    @click="edit({{ $resource->id }})"
                                    class="rounded-xl border border-surface-high bg-white px-3 py-1.5 text-xs font-semibold text-primary-container hover:bg-surface-low transition">
                                Edit
                            </button>
                            <button type="button"
                                    @click="confirmDelete({{ $resource->id }}, @js($resource->title))"
                                    class="rounded-xl border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition">
                                Delete
                            </button>
                        </div>
                    </article>
                @empty
                    <div class="card p-10 text-center text-ink-muted">
                        <x-icon name="book-open" class="w-10 h-10 mx-auto text-ink-faint"/>
                        <p class="mt-3 text-sm">No resources yet. Click "New Resource" to add one.</p>
                    </div>
                @endforelse

                {{ $resources->links() }}
            </div>

            {{-- Create / Edit Modal --}}
            <x-modal name="resource-form" maxWidth="2xl" focusable>
                <form method="POST"
                      :action="editing ? `{{ url('admin/resources') }}/${editing.id}` : '{{ route('admin.resources.store') }}'"
                      class="p-6">
                    @csrf
                    <template x-if="editing">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <h2 class="font-headline text-lg font-bold text-primary" x-text="editing ? 'Edit Resource' : 'New Resource'"></h2>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-primary-container">Type</label>
                            <select name="type" x-model="form.type"
                                    class="mt-2 block w-full rounded-xl border-surface-high bg-white text-ink shadow-sm focus:border-accent focus:ring-accent">
                                @foreach ($types as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-primary-container">Title / Name</label>
                            <input type="text" name="title" x-model="form.title" required
                                   class="mt-2 block w-full rounded-xl border-surface-high bg-white text-ink shadow-sm focus:border-accent focus:ring-accent">
                            @error('title') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-primary-container">Summary (optional)</label>
                            <input type="text" name="summary" x-model="form.summary"
                                   class="mt-2 block w-full rounded-xl border-surface-high bg-white text-ink shadow-sm focus:border-accent focus:ring-accent">
                        </div>

                        {{-- CET fields --}}
                        <template x-if="form.type === 'cet'">
                            <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-primary-container">Period</label>
                                    <input type="text" name="period" x-model="form.period" placeholder="e.g. August (Annual)"
                                           class="mt-2 block w-full rounded-xl border-surface-high bg-white text-ink shadow-sm focus:border-accent focus:ring-accent">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-semibold text-primary-container">Coverage</label>
                                    <textarea name="coverage" x-model="form.coverage" rows="2"
                                              class="mt-2 block w-full rounded-xl border-surface-high bg-white text-ink shadow-sm focus:border-accent focus:ring-accent"></textarea>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-semibold text-primary-container">Reviewer Tips</label>
                                    <textarea name="tips" x-model="form.tips" rows="2"
                                              class="mt-2 block w-full rounded-xl border-surface-high bg-white text-ink shadow-sm focus:border-accent focus:ring-accent"></textarea>
                                </div>
                            </div>
                        </template>

                        {{-- Scholarship fields --}}
                        <template x-if="form.type === 'scholarship'">
                            <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-semibold text-primary-container">Grantor</label>
                                    <input type="text" name="grantor" x-model="form.grantor"
                                           class="mt-2 block w-full rounded-xl border-surface-high bg-white text-ink shadow-sm focus:border-accent focus:ring-accent">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-semibold text-primary-container">Benefits / Coverage</label>
                                    <textarea name="coverage" x-model="form.coverage" rows="2"
                                              class="mt-2 block w-full rounded-xl border-surface-high bg-white text-ink shadow-sm focus:border-accent focus:ring-accent"></textarea>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-semibold text-primary-container">Eligibility Criteria</label>
                                    <textarea name="criteria" x-model="form.criteria" rows="2"
                                              class="mt-2 block w-full rounded-xl border-surface-high bg-white text-ink shadow-sm focus:border-accent focus:ring-accent"></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-primary-container">Application Opens</label>
                                    <input type="date" name="opens_at" x-model="form.opens_at"
                                           class="mt-2 block w-full rounded-xl border-surface-high bg-white text-ink shadow-sm focus:border-accent focus:ring-accent">
                                    @error('opens_at') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-primary-container">Application Closes</label>
                                    <input type="date" name="closes_at" x-model="form.closes_at"
                                           class="mt-2 block w-full rounded-xl border-surface-high bg-white text-ink shadow-sm focus:border-accent focus:ring-accent">
                                    @error('closes_at') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </template>

                        {{-- University fields --}}
                        <template x-if="form.type === 'university'">
                            <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-primary-container">Short Name</label>
                                    <input type="text" name="short_name" x-model="form.short_name" placeholder="e.g. UP"
                                           class="mt-2 block w-full rounded-xl border-surface-high bg-white text-ink shadow-sm focus:border-accent focus:ring-accent">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-primary-container">Location</label>
                                    <input type="text" name="location" x-model="form.location"
                                           class="mt-2 block w-full rounded-xl border-surface-high bg-white text-ink shadow-sm focus:border-accent focus:ring-accent">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-primary-container">Institution Type</label>
                                    <input type="text" name="institution_type" x-model="form.institution_type" placeholder="e.g. State University"
                                           class="mt-2 block w-full rounded-xl border-surface-high bg-white text-ink shadow-sm focus:border-accent focus:ring-accent">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-primary-container">Admission Exam</label>
                                    <input type="text" name="entrance_exam" x-model="form.entrance_exam" placeholder="e.g. UPCAT"
                                           class="mt-2 block w-full rounded-xl border-surface-high bg-white text-ink shadow-sm focus:border-accent focus:ring-accent">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-primary-container">Tuition Range</label>
                                    <input type="text" name="tuition_range" x-model="form.tuition_range"
                                           class="mt-2 block w-full rounded-xl border-surface-high bg-white text-ink shadow-sm focus:border-accent focus:ring-accent">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-primary-container">Logo Color (hex)</label>
                                    <input type="text" name="logo_bg" x-model="form.logo_bg" placeholder="#7b1113"
                                           class="mt-2 block w-full rounded-xl border-surface-high bg-white text-ink shadow-sm focus:border-accent focus:ring-accent">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-semibold text-primary-container">Key Programs (comma-separated)</label>
                                    <input type="text" name="top_programs" x-model="form.top_programs"
                                           class="mt-2 block w-full rounded-xl border-surface-high bg-white text-ink shadow-sm focus:border-accent focus:ring-accent">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-semibold text-primary-container">Website</label>
                                    <input type="url" name="website" x-model="form.website"
                                           class="mt-2 block w-full rounded-xl border-surface-high bg-white text-ink shadow-sm focus:border-accent focus:ring-accent">
                                </div>
                            </div>
                        </template>

                        {{-- Strand fields --}}
                        <template x-if="form.type === 'strand'">
                            <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-primary-container">Emoji</label>
                                    <input type="text" name="emoji" x-model="form.emoji" placeholder="🔬"
                                           class="mt-2 block w-full rounded-xl border-surface-high bg-white text-ink shadow-sm focus:border-accent focus:ring-accent">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-primary-container">Subtitle</label>
                                    <input type="text" name="subtitle" x-model="form.subtitle"
                                           class="mt-2 block w-full rounded-xl border-surface-high bg-white text-ink shadow-sm focus:border-accent focus:ring-accent">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-semibold text-primary-container">Top Degree Alignments</label>
                                    <textarea name="alignments" x-model="form.alignments" rows="2"
                                              class="mt-2 block w-full rounded-xl border-surface-high bg-white text-ink shadow-sm focus:border-accent focus:ring-accent"></textarea>
                                </div>
                            </div>
                        </template>

                        {{-- Shared fields --}}
                        <div>
                            <label class="block text-sm font-semibold text-primary-container">External Link</label>
                            <input type="url" name="link" x-model="form.link"
                                   class="mt-2 block w-full rounded-xl border-surface-high bg-white text-ink shadow-sm focus:border-accent focus:ring-accent">
                            @error('link') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-primary-container">Sort Order</label>
                            <input type="number" name="sort_order" x-model="form.sort_order" min="0"
                                   class="mt-2 block w-full rounded-xl border-surface-high bg-white text-ink shadow-sm focus:border-accent focus:ring-accent">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-primary-container">Applicable Schools</label>
                            <div class="mt-2 max-h-40 overflow-y-auto rounded-xl border border-surface-high bg-surface p-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @forelse ($schools as $school)
                                    <label class="flex items-center gap-2 text-sm text-ink-muted">
                                        <input type="checkbox" name="school_ids[]" value="{{ $school->id }}"
                                               x-model.number="form.school_ids"
                                               class="rounded border-surface-high text-accent focus:ring-accent">
                                        <span>{{ $school->name }}</span>
                                    </label>
                                @empty
                                    <p class="text-xs text-ink-faint">No schools yet — add schools to attach them to resources.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="sm:col-span-2 flex items-center gap-2">
                            <input type="hidden" name="is_published" value="0">
                            <input type="checkbox" id="is_published" name="is_published" value="1" x-model="form.is_published"
                                   class="rounded border-surface-high text-accent focus:ring-accent">
                            <label for="is_published" class="text-sm text-ink-muted">Published (visible on the public Resource Center)</label>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button type="button" @click="$dispatch('close-modal', 'resource-form')"
                                class="rounded-xl border border-surface-high bg-white px-4 py-2 text-sm font-semibold text-primary-container hover:bg-surface-low transition">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-white shadow-md transition hover:bg-primary-container">
                            <span x-text="editing ? 'Save Changes' : 'Create Resource'"></span>
                        </button>
                    </div>
                </form>
            </x-modal>

            {{-- Delete confirmation modal --}}
            <x-modal name="resource-delete" maxWidth="md">
                <form method="POST" :action="`{{ url('admin/resources') }}/${deleting.id}`" class="p-6">
                    @csrf
                    @method('DELETE')
                    <h2 class="font-headline text-lg font-bold text-primary">Delete resource?</h2>
                    <p class="mt-2 text-sm text-ink-muted">
                        "<span x-text="deleting.title"></span>" will be permanently removed from the Resource Center.
                    </p>
                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button type="button" @click="$dispatch('close-modal', 'resource-delete')"
                                class="rounded-xl border border-surface-high bg-white px-4 py-2 text-sm font-semibold text-primary-container hover:bg-surface-low transition">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-xl bg-rose-600 px-5 py-2 text-sm font-semibold text-white shadow-md transition hover:bg-rose-700">
                            Delete
                        </button>
                    </div>
                </form>
            </x-modal>
        </div>
    </x-admin-layout>

    <script>
        function resourceManager(items) {
            return {
                items,
                editing: null,
                deleting: { id: null, title: '' },
                form: {},
                blank() {
                    return {
                        type: '{{ $type }}',
                        title: '', summary: '', link: '',
                        is_published: false, sort_order: 0,
                        period: '', coverage: '', tips: '',
                        grantor: '', criteria: '', opens_at: '', closes_at: '',
                        short_name: '', location: '', institution_type: '',
                        top_programs: '', entrance_exam: '', tuition_range: '',
                        website: '', logo_bg: '',
                        emoji: '', subtitle: '', alignments: '',
                        school_ids: [],
                    };
                },
                create() {
                    this.editing = null;
                    this.form = this.blank();
                    this.$dispatch('open-modal', 'resource-form');
                },
                edit(id) {
                    const found = this.items.find((i) => i.id === id);
                    if (!found) return;
                    this.editing = found;
                    this.form = Object.assign(this.blank(), JSON.parse(JSON.stringify(found)));
                    this.$dispatch('open-modal', 'resource-form');
                },
                confirmDelete(id, title) {
                    this.deleting = { id, title };
                    this.$dispatch('open-modal', 'resource-delete');
                },
            };
        }
    </script>
</x-app-layout>
