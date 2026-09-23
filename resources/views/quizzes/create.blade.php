<x-app-layout title="Upload Material">
    <div class="flex-grow w-full max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-10 flex flex-col gap-6">

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row justify-between sm:items-end gap-4 pb-4 border-b border-surface-low">
            <div>
                <h1 class="font-headline text-2xl sm:text-3xl font-bold text-primary">Upload Learning Material</h1>
                <p class="text-sm text-ink-muted mt-1">PDF, DOCX, or TXT source for AI quiz generation</p>
            </div>
            <a href="{{ route('quizzes.index') }}" class="text-sm font-semibold text-primary-container hover:text-primary shrink-0">Back to quizzes</a>
        </div>

        <form id="upload-form" method="POST" action="{{ route('quizzes.store') }}" enctype="multipart/form-data" class="card p-6 mb-8">
            @csrf

            <div>
                <label for="title" class="block text-sm font-semibold text-primary-container">Quiz Title</label>
                <input id="title" name="title" type="text" value="{{ old('title') }}" class="mt-2 block w-full rounded-xl border-surface-high bg-white text-ink placeholder:text-ink-faint shadow-sm focus:border-accent focus:ring-accent" placeholder="e.g. Introduction to Computing">
                @error('title')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6">
                <label for="material" class="block text-sm font-semibold text-primary-container">Learning Material</label>
                <label id="drop-zone" for="material" class="mt-2 flex min-h-64 cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-surface-high bg-surface px-6 py-10 text-center transition hover:border-accent hover:bg-accent/10">
                    <x-icon name="file-text" class="h-12 w-12 text-accent"/>
                    <span id="file-label" class="mt-4 text-base font-semibold text-primary">Drop learning material</span>
                    <span class="mt-1 text-sm text-ink-muted">PDF, DOCX, TXT up to 10 MB</span>
                    <input id="material" name="material" type="file" accept=".pdf,.docx,.txt" class="sr-only">
                </label>
                @error('material')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div id="upload-progress" class="mt-6 hidden">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium text-primary-container">Preparing AI generation</span>
                    <span class="text-ink-muted">Queued</span>
                </div>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-surface-high">
                    <div class="h-full w-1/2 animate-pulse rounded-full bg-accent"></div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('quizzes.index') }}" class="rounded-xl border border-surface-high bg-white px-4 py-2 text-sm font-semibold text-primary-container hover:bg-surface-low transition">Cancel</a>
                <button id="submit-button" type="submit" class="rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-white shadow-md transition hover:bg-primary-container">
                    Generate Quiz
                </button>
            </div>
        </form>
    </div>

    <script>
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('material');
        const fileLabel = document.getElementById('file-label');
        const uploadForm = document.getElementById('upload-form');
        const uploadProgress = document.getElementById('upload-progress');
        const submitButton = document.getElementById('submit-button');

        function updateFileLabel() {
            fileLabel.textContent = fileInput.files.length ? fileInput.files[0].name : 'Drop learning material';
        }

        ['dragenter', 'dragover'].forEach((eventName) => {
            dropZone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropZone.classList.add('border-accent', 'bg-accent/10');
            });
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            dropZone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropZone.classList.remove('border-accent', 'bg-accent/10');
            });
        });

        dropZone.addEventListener('drop', (event) => {
            if (event.dataTransfer.files.length) {
                fileInput.files = event.dataTransfer.files;
                updateFileLabel();
            }
        });

        fileInput.addEventListener('change', updateFileLabel);

        uploadForm.addEventListener('submit', () => {
            uploadProgress.classList.remove('hidden');
            submitButton.disabled = true;
            submitButton.textContent = 'Uploading...';
            submitButton.classList.add('opacity-70');
        });
    </script>
</x-app-layout>
