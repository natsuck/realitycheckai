# Paper Alignment Guide

Step-by-step guidance for updating *"AI-Powered Reality Check System for College Program
Suitability"* so the written chapters match the diagrams in this package (and the
as-built system). You are editing the paper yourself — this is your checklist.

## Why this is needed

Your proposal (Chapter 3, "Software Requirements") currently describes:

| Proposal says | As-built system actually uses |
|---|---|
| Python + Scikit-learn + TensorFlow for the AI | Local **Ollama** LLM (`llama3`) over HTTP, called from Laravel (PHP), with a deterministic PHP fallback scorer |
| **PostgreSQL** database | **MySQL** database |
| Hosting on **Amazon Web Services (EC2)** | **Local hosting** (Laravel built-in server / queue worker + Vite) |

Leaving the old stack in the text while the figures show Ollama/MySQL will be caught by
the panel. Update the prose to match the diagrams.

## 1. Figures to replace in Chapter 3

| Thesis figure | Current (broken/placeholder) | Replace with file |
|---|---|---|
| Figure 2 — System flowchart | placeholder | `generated/05-system-flowchart.png` |
| Figure 3 — Data Flow Diagram | placeholder | `generated/03-context-dfd.png` (Level 0) and `generated/04-dfd-level-1.png` (Level 1) — caption as "Figure 3a / 3b" or re-number |
| Figure 4 — Method diagram (IPO) | handwritten boxes | `generated/02-conceptual-framework-ipo.png` |

## 2. New figures you may add (recommended for a stronger Chapter 3)

Use these in this order after Figure 4 (re-number the List of Figures accordingly):

- `generated/01-system-architecture.png` — System architecture (best figure to open the
  Methodology/System Design section).
- `generated/06-assessment-flowchart.png` — Assessment module flowchart.
- `generated/07-quiz-generation-flowchart.png` — AI quiz generator flowchart.
- `generated/08-attempt-grading-flowchart.png` — Attempt grading flowchart.
- `generated/11-usecase-diagram.png` — Use case diagram.
- `generated/12-erd.png` — Entity Relationship Diagram.
- `generated/13-sequence-assessment.png`, `14-sequence-quiz-generation.png`,
  `15-sequence-grading.png` — Sequence diagrams (optional, can move to Chapter 4).
- `generated/10-quiz-status-state-diagram.png` — Quiz lifecycle state diagram (optional).

> Tip: PNG files were rendered at 3× scale on white background — print-quality for Word.
> SVG versions exist too if you need lossless zoom in the digital copy.

## 3. Text edits in Chapter 3 (Software Requirements)

Change these paragraphs/sentences:

1. **Main framework sentence.** Keep "PHP Laravel MVC". Add: *"The back end is built with
   Laravel 12 (PHP ≥ 8.2); the user interface uses Laravel Blade templates with Tailwind
   CSS and Alpine.js for responsiveness."*

2. **Remove/rewrite the PostgreSQL paragraph.** Replace with:
   *"The database layer uses **MySQL**, an open-source relational database management
   system. MySQL stores all structured data — user accounts, the 26 assessment questions,
   raw answers, assessment results, quizzes, quiz questions, attempts, and per-answer
   grading records. Laravel's Eloquent ORM manages the schema and relationships, and the
   database driver is also used for queues, cache, and sessions."*

3. **Remove/rewrite the TensorFlow/Scikit-learn paragraph.** Replace with:
   *"The AI component runs on **Ollama**, a local inference engine serving the `llama3`
   language model over HTTP (`localhost:11434`). Laravel services (`OllamaService`,
   `QuizGeneratorService`, `AnswerEvaluationService`) send structured prompts and receive
   strict JSON contracts for program recommendations, quiz generation, and semantic
   grading. When the local AI service is unavailable, a deterministic trait-weighted
   scorer in PHP guarantees an 'offline estimate' result, so the system always produces a
   recommendation."*

4. **Remove/rewrite the AWS paragraph.** Replace with:
   *"The system runs on a **local development server** (`php artisan serve`) with a
   dedicated queue worker (`php artisan queue:work --queue=ai,default`) for asynchronous
   quiz generation, and Vite for front-end asset bundling. This setup keeps the AI
   inference on the local machine, eliminating external API costs and keeping student data
   on campus."* (Adjust if you later deploy to a real server.)

5. **Consent section (scope).** Your Overview mentions "data privacy and security
   measures" — strengthen with: *"All assessment data is gated behind an informed consent
   notice compliant with Republic Act No. 10173 (Data Privacy Act of 2012), recorded on
   the user's account before any assessment is taken."*

6. **Keep** the ISO/IEC 25010 evaluation framework section as-is (it applies regardless of
   stack). Mention that functional suitability was verified by the feature-level tests.

## 4. List of Figures / Tables update

- Update the Table of Contents + List of Figures page numbers after inserting figures.
- Add any new figure captions in the style already used (e.g., "Figure 5: System
  Architecture", "Figure 6: Assessment Module Flowchart", ...).
- If you add the ERD, consider a "List of Tables" row describing each database table name.

## 5. Defense talking points (what the panel will ask)

- "How does the AI work?" → *Local Ollama LLM `llama3`; structured JSON prompts; measured
  temperature 0.15 for recommendations, 0.0 for grading; deterministic fallback when
  offline.*
- "Why not TensorFlow/Cloud?" → *The proposal originally planned it; the implementation
  chose a local LLM for: no external API costs, data privacy (RA 10173), and simplicity —
  with a deterministic scorer guaranteeing output even without AI.*
- "Is the recommendation reliable?" → *It's advisory ("guidance, not verdicts"), includes
  confidence + fit level + evidence, has response-validity flags (straight-lining, <10 s
  completion), and results link to a counselor analytics dashboard.*

## 6. Optional: verification tasks

- Walk the panel demo through: consent gate → 26-item Likert → recommendation report →
  upload a PDF → queued → ready → take quiz → graded review → admin analytics.
- Screenshots for Chapter 4 (Results and Discussion) should show the "AI-generated" vs
  "Offline estimate" badges and the privacy consent gate.