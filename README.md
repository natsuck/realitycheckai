# Aguhon

AI-assisted academic guidance platform for incoming college students, built with Laravel 12.

Students take a Likert-scale career assessment and receive an AI-generated program
recommendation (with confidence level, strongest trait, evidence, and alternatives), and can
upload learning materials (PDF/DOCX/TXT) that the OpenAI API turns into practice quizzes with
automatic grading and analytics.

The AI layer uses per-task models — a fast, low-cost model for answer grading and stronger
models for assessment recommendations and quiz generation — with constrained JSON outputs
(`json_schema` structured outputs) so the app receives valid, parseable data every time.

> **Guidance, not verdicts.** All recommendations are explicitly advisory and must be combined
> with adviser consultation, grades, and personal interest. The system states this disclaimer on
> every result screen.

## Features

### 1. Program Recommendation Assessment
- 26-item Likert instrument across 6 categories (skills, interests, personality, work style,
  career values, technical aptitude), including reverse-scored negatively-worded items
- **OpenAI** API (`gpt-4o-mini` by default) produces structured JSON recommendations:
  program, confidence, fit level, strengths, development areas, evidence, next steps, and
  alternative programs
- Deterministic trait-weighted fallback scorer when the AI service is unavailable — the app
  always produces a recommendation, clearly badged `AI-generated` vs `Offline estimate`
- Server-computed **strongest trait** per assessment
- Response-validity checks (straight-lining / near-uniform patterns / implausibly fast
  completion) flagged to the student
- Full retake history persisted in the database (results survive session expiry)
- Data privacy consent gate compliant with **RA 10173** (Data Privacy Act of 2012) before any
  assessment data is collected

### 2. AI Quiz Generator
- Upload PDF / DOCX / TXT learning material (max 10 MB); files are stored in private storage and
  served only to their owner
- Queued job extracts text (`smalot/pdfparser`, `phpoffice/phpword`), prompts OpenAI for exactly
  10 MCQs + 5 identification questions in strict JSON with anti-hallucination rules, validates
  and repairs model output transactionally
- Students take quizzes; MCQs are graded deterministically, identification answers semantically
  by AI (with normalized-match / Levenshtein fallback)
- Per-attempt analytics: score trends, accuracy by difficulty, AI confidence

### 3. Guidance Counselor Analytics (`/admin/analytics`)
Admin-only dashboard aggregating all assessment results: totals, unique students, average
confidence, program distribution, live-AI vs fallback share, and flagged response counts.

## Architecture

```
Browser ── Tailwind/Alpine UI (Breeze)
   │
   ├─ AssessmentController ──► OpenAIService ──► OpenAI API
   │        │                        └─ deterministic fallback scorer
   │        └─► assessment_results (persisted history + validity flags)
   │
   └─ QuizController ──► GenerateQuizFromMaterial job ('ai' queue)
                              ├─ TextExtractionService (PDF/DOCX/TXT)
                              ├─ QuizGeneratorService (strict JSON contract)
                              └─ AnswerEvaluationService (semantic grading)
```

Key source paths:

| Area | Path |
|---|---|
| Controllers | `app/Http/Controllers` |
| Services | `app/Services/OpenAIService.php`, `QuizGeneratorService.php`, `TextExtractionService.php`, `AnswerEvaluationService.php` |
| Queue job | `app/Jobs/GenerateQuizFromMaterial.php` |
| Migrations | `database/migrations` |
| Methodology docs | `docs/assessment-methodology.md`, `docs/ai-quiz-generator-prompt-engineering.md` |

## Requirements

- PHP >= 8.2, Composer
- Node.js >= 20, npm
- MySQL
- OpenAI API key ([platform.openai.com/api-keys](https://platform.openai.com/api-keys))

## Setup

```bash
composer install
cp .env.example .env          # edit DB credentials and add OPENAI_API_KEY
php artisan key:generate
php artisan migrate --seed    # seeds 26 assessment questions + demo accounts
npm install && npm run build
```

Demo accounts created by the seeder (password `password`):

| Role | Email |
|---|---|
| Student | `test@example.com` |
| Guidance counselor / admin | `admin@realitycheck.test` |

### Running locally

```bash
composer dev
# starts: artisan serve + queue worker (ai queue) + vite dev server
```

The quiz generator requires the queue worker; the assessment runs synchronously.

### Configuration

All AI settings live under the `OPENAI_*` keys in `.env` (see `.env.example`):

- `OPENAI_API_KEY` — required API key
- `OPENAI_ORGANIZATION`, `OPENAI_BASE_URL` — optional org / OpenAI-compatible gateway override
- `OPENAI_MODEL_DEFAULT`, `OPENAI_MODEL_ASSESSMENT`, `OPENAI_MODEL_QUIZ`,
  `OPENAI_MODEL_ANSWER` — per-task models (grading uses the fast default `gpt-4o-mini`)
- `OPENAI_TIMEOUT`, `OPENAI_RETRIES`, `OPENAI_TEMPERATURE` — request behavior
- `OPENAI_ASSESSMENT_MAX_TOKENS`, `OPENAI_QUIZ_MAX_TOKENS` — completion budgets per task
- `OPENAI_QUIZ_CACHE_TTL` — seconds to reuse a validated quiz for identical material
- `OPENAI_MAX_DOCUMENT_CHARS` — character limit of extracted text sent to the model

## Testing

```bash
composer test     # config:clear + artisan test (Pest)
vendor/bin/pint   # code style
```

Coverage includes the full assessment flow (consent gating, reverse scoring, persistence,
retake history, validity flags), admin authorization, quiz upload/download authorization,
attempt grading, auth scaffolding, and `OpenAIService` normalization/fallback behavior via
HTTP fakes.
