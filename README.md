# Aguhon

Aguhon is a Laravel web application for student academic guidance. Students complete a
versioned, 150-item assessment and receive a score profile with AI-generated exploratory
suggestions for programs offered by the school. The app also generates practice quizzes and
flashcards from uploaded learning materials.

**Program suggestions are advisory, AI-generated possibilities—not validated placement,
admission, or academic-success decisions.** The current instrument has 90 RIASEC interest items
and 60 aptitude questions. The application scores those responses; it does not train a local
machine-learning model or evaluate student grades, learning styles, or existing academic records.
The AI receives the computed assessment profile and program descriptions to explain the profile
and rank possible programs. The RIASEC and aptitude results are kept separate.

## Features

### Student assessment

- 90 RIASEC career-interest items and 60 multiple-choice aptitude items (verbal, quantitative,
  and analytical)
- Server-side scoring, required responses, per-attempt history, and response-validity flags
- AI-generated program rankings and explanations, clearly marked exploratory and non-binding
- Privacy-consent notice before the assessment

### Quiz generation and study

- Upload PDF, DOCX, or TXT learning materials (up to 10 MB)
- Background generation of 15 source-grounded multiple-choice questions and 10 separate
  flashcards
- Server-side quiz timing, answer completeness checks, and MCQ choice validation
- Students complete a quiz before question review; answer keys and explanations are available
  after the first attempt
- Retakes randomize question order; flashcards are a separate, ungraded study mode

### Guidance-counselor analytics

The admin-only `/admin/analytics` dashboard summarizes assessment totals, unique students,
primary AI program suggestions, AI/fallback source counts, and response flags. Any legacy
model-reported confidence is not calibrated and is not supplied for current instrument mappings.

## How AI is used

- The application computes the assessment scores. The configured AI provider explains the
  profile and produces exploratory program rankings from the computed scores and program
  descriptions.
- Quiz generation uses extracted text from the uploaded learning material. Identification answers
  may be evaluated semantically by the configured AI provider; MCQs are graded against their
  answer key.
- The application validates AI output and does not treat a generated suggestion as a
  psychometrically validated mapping.
- Assessment profiles and uploaded learning material may be sent to the configured AI provider
  for processing. Do not send real student data until the school has approved the privacy notice,
  provider terms, and deployment arrangements.

## Technology

- PHP 8.2+ and Laravel 12
- Blade, Vite, Tailwind CSS, and Alpine.js
- MySQL (configured default; supported for local development and Laravel Cloud)
- OpenAI-compatible API for AI features
- `smalot/pdfparser` and `phpoffice/phpword` for document extraction

## Local setup

Requirements: PHP 8.2+, Composer, Node.js 20+, npm, and MySQL.

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Configure the database and AI settings in `.env`, then run:

```bash
php artisan migrate --seed
npm install
npm run build
composer dev
```

`composer dev` starts the local web server, Vite, and a queue listener for the `ai` and `default`
queues. Quiz generation requires the queue listener to be running. Assessment scoring runs during
the web request.

The general database seeder creates demo accounts with the password `password`:

| Role | Email |
|---|---|
| Student | `test@example.com` |
| Guidance admin | `admin@realitycheck.test` |

These accounts are for local development only. **Never run `php artisan db:seed` or
`php artisan migrate --seed` in production.** Production setup is described below.

## Laravel Cloud deployment

The application is **not yet ready for production deployment to Laravel Cloud without the
prerequisites below**. Cloud application filesystems are ephemeral, and the current quiz upload
and extraction flow depends on files being present on the local disk. The current locked Laravel
version also does not meet Laravel Cloud's documented managed-queue minimum.

### Required before production

1. **Make quiz uploads durable.** Add an S3-compatible filesystem adapter, attach a private
   Laravel Cloud Object Storage bucket, and update upload, download, deletion, and document
   extraction to use that storage. Text extraction currently requires a local file path, so the
   stored object must be safely streamed or copied to a temporary local file for parsing. Do not
   make student-upload buckets public.
2. **Choose and configure queue processing.**
   - For Laravel Cloud managed queues, upgrade Laravel to a Cloud-supported version (currently
     Laravel 12.63.0 or later) and add the required AWS SDK dependency. Configure a managed queue
     named `ai`, since quiz-generation jobs are dispatched to that queue. Check the selected queue
     compute class against the job's 300-second timeout; Cloud Flex queues have a 90-second job
     runtime limit.
   - Alternatively, retain the database queue and configure a Cloud worker cluster to run a
     worker for the `ai` and `default` queues with a timeout and retry policy compatible with the
     job.
3. **Use a persistent database.** Attach a Laravel Cloud MySQL or PostgreSQL resource in the same
   region as the app. This repository defaults to MySQL; do not change database engines without
   running the migration and test suite against the target engine.
4. **Create production users safely.** Run schema migrations and seed only the assessment
   instrument if needed (for example, `php artisan db:seed --class=QuestionSeeder`). Create the
   real admin account through a secure, approved process. Do not create or deploy the demo
   accounts.
5. **Obtain privacy/security approval.** Confirm the Cloud region, private file-storage location,
   AI-provider processing terms, access controls, retention/deletion behavior, and consent
   language with the school before processing identifiable student data.

### Laravel Cloud environment configuration

After completing the prerequisites, a typical Laravel Cloud configuration is:

- **Runtime:** supported PHP runtime compatible with the application
- **Build command:** `composer install --no-dev && npm ci && npm run build`
- **Deploy command:** `php artisan migrate --force`
- **Persistent data:** attached Cloud database and private object-storage bucket
- **Secrets/settings:** production `APP_ENV`, `APP_DEBUG=false`, stable `APP_KEY`, production
  `APP_URL`, and `OPENAI_API_KEY`; use the Cloud-injected database and storage credentials
- **Queue:** one of the queue configurations described above; verify quiz generation and failure
  handling in staging before launch

Never commit `.env`, API keys, database credentials, or application keys. Set production secrets
in the Laravel Cloud environment settings, not in this README or source control.

See the official [Laravel Cloud Laravel deployment guide](https://laravel.com/cloud/docs/deploy-guides/laravel.md),
[managed queues documentation](https://laravel.com/cloud/docs/queues.md), and
[Object Storage documentation](https://laravel.com/cloud/docs/resources/object-storage.md) for
current platform requirements.

## Configuration

AI settings are configured using `OPENAI_*` variables in `.env` (see `.env.example`):

- `OPENAI_API_KEY` — API secret
- `OPENAI_ORGANIZATION`, `OPENAI_BASE_URL` — optional organization or compatible gateway
- `OPENAI_MODEL_DEFAULT`, `OPENAI_MODEL_ASSESSMENT`, `OPENAI_MODEL_QUIZ`,
  `OPENAI_MODEL_ANSWER` — per-task model selection
- `OPENAI_TIMEOUT`, `OPENAI_RETRIES`, `OPENAI_TEMPERATURE` — request behavior
- `OPENAI_ASSESSMENT_MAX_TOKENS`, `OPENAI_QUIZ_MAX_TOKENS` — response budgets
- `OPENAI_QUIZ_CACHE_TTL` — cache duration for validated generated quizzes
- `OPENAI_MAX_DOCUMENT_CHARS` — maximum extracted source text sent for quiz generation

## Tests and quality checks

```bash
composer test
vendor/bin/pint
npm run build
```

The test suite covers assessment consent and scoring, per-attempt response persistence, quiz
generation and validation, timing and answer checks, answer review, authorization, and AI
normalization/fallback behavior.

## Key paths

| Area | Path |
|---|---|
| Controllers | `app/Http/Controllers` |
| Assessment scoring | `app/Services/AssessmentScoringService.php` |
| AI services | `app/Services/OpenAIService.php`, `QuizGeneratorService.php`, `AnswerEvaluationService.php` |
| Document extraction | `app/Services/TextExtractionService.php` |
| Quiz generation job | `app/Jobs/GenerateQuizFromMaterial.php` |
| Database migrations and seeders | `database/migrations`, `database/seeders` |
| Assessment methodology | `docs/assessment-methodology.md` |
| Quiz prompt design | `docs/ai-quiz-generator-prompt-engineering.md` |
