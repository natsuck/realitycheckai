# Assessment Methodology

This document describes how Aguhon converts a student's self-report answers into an
academic program recommendation. It is intended as supporting material for the thesis defense.

## 1. Instrument Design

The assessment consists of **26 Likert-scale items** (1 = Strongly disagree … 5 = Strongly
agree) across **six categories**, seeded by `database/seeders/QuestionSeeder.php`:

| Category | Items | Sample construct |
|---|---|---|
| Skills | 4 | Logical/analytical problem solving, communication, collaboration |
| Interests | 4 | Technology, design, business, people-oriented work |
| Personality | 5 | Independence, teamwork, detail orientation, adaptability |
| Work style | 5 | Hands-on preference, planning, fast-paced environments |
| Career values | 4 | Stability, compensation, work-life balance, growth |
| Technical aptitude | 4 | Interest in computer systems, software, programming |

### Reverse-scored items

Three negatively-worded items are flagged `is_reverse = true` in the `questions` table:

- "I avoid tasks involving technology." (technical)
- "I dislike working in groups." (work_style)
- "I find it difficult to adapt to sudden changes." (personality)

Before scoring, their values are inverted (`6 − answer`) so that a **higher stored value always
means more of the underlying trait**. The flag lives in the database — not in code — so items can
be added or reworded without touching application logic.

Item order is randomized on each presentation to reduce order bias; scoring uses canonical
question IDs.

## 2. Category Summary and Strongest Trait

For each category the mean answer is computed and expressed as a percentage of the scale
maximum:

```
percentage = (mean / 5) × 100
```

Levels: Very strong ≥ 84%, Strong ≥ 68%, Moderate ≥ 52%, Needs exploration below.

The **strongest trait** is the category with the highest percentage; ties break on the number of
backing items, then alphabetically. It is computed server-side in
`AssessmentController::strongestTrait()` and persisted with every result.

## 3. Response-Validity Checks

Before trusting any recommendation, submissions are screened for careless responding
(`AssessmentController::detectResponseFlags()`):

| Flag | Condition | Rationale |
|---|---|---|
| `uniform_responses` | All 26 answers identical | Straight-lining; answers carry no discriminating information |
| `near_uniform_responses` | One value covers ≥ 90% of answers | Effectively straight-lined |
| `rapid_completion` | Completed in < 10 seconds | Implausible reading time for 26 items |

Flagged results are still shown, but the student sees a "Response pattern notice" stating the
recommendation may be less reliable, and counselors see a flag count in the analytics dashboard.
Completion duration is measured between rendering the form and submission.

## 4. AI Recommendation Pipeline

1. Answers are serialized with question text, category, category label, numeric answer, and
   scale label.
2. `OllamaService::analyze()` prompts the local LLM (temperature 0.15) with a strict JSON
   contract: recommended program, confidence, fit level, reason, strengths, development areas,
   feedback, evidence, next steps, alternative programs.
3. Output is defensively normalized (`normalizeRecommendation()`):
   - program codes are uppercased and validated against the six-program catalog;
     unknown codes trigger the deterministic scorer instead
   - confidence values are clamped to 0–100
   - list fields fall back to sensible defaults when missing or malformed
   - markdown fences / trailing commas in model output are repaired before decoding
4. Every result is tagged `source: ollama`; UI badges distinguish live AI output from fallback.

**Confidence caveat:** the reported confidence is the LLM's *self-assessment*, a heuristic —
not a calibrated probability. The UI states this on the results page.

## 5. Deterministic Fallback Scorer

When Ollama is unreachable or returns unusable output, `fallbackProgramCode()` scores five
traits from the same answers and matches them against fixed program profiles.

**Trait elicitation (per item):** keyword matching on question text assigns each item to up to
five traits (analytical, technical, social, organizational, practical). Any item matching no
keyword falls back to a stable per-category weight distribution
(`categoryTraitDefaults()`) so rewording a question can never silently remove its weight.
`values` items are excluded entirely — career-value preferences are not trait evidence.

**Program profile matrix** (weights sum to 1.0 per program):

| Program | analytical | technical | social | organizational | practical |
|---|---|---|---|---|---|
| BSCS | 0.40 | 0.35 | 0.05 | 0.10 | 0.10 |
| BSIT | 0.20 | 0.35 | 0.05 | 0.10 | 0.30 |
| BSAIS | 0.30 | 0.20 | 0.05 | 0.35 | 0.10 |
| BSHM | 0.10 | 0.05 | 0.40 | 0.25 | 0.20 |
| BSTM | 0.10 | 0.10 | 0.40 | 0.20 | 0.20 |
| ACT | 0.20 | 0.30 | 0.10 | 0.10 | 0.30 |

```
program_score(p) = Σ_trait  trait_total(t) × weight(p, t)
recommended      = argmax_p program_score(p)
```

Fallback output carries `fit_level: Preliminary Fit`, `source: fallback`, and the triggering
error for transparency.

## 6. Results-Page Metrics: Aptitude Score and Program Matches

Two derived metrics are computed when rendering results; neither is persisted — they are
derived on demand from stored data.

**Aptitude score (`AssessmentController::computeAptitude()`)** is a weighted composite of the
stored category-summary percentages:

```
aptitude = Σ_category  weight(c) × percentage(c)
weights: technical 0.30 · skills 0.25 · interests 0.20 · personality 0.15 · work_style 0.10
```

Career-value items are excluded (values describe preferences, not ability). Weights are
renormalized over whichever categories are present, so partial submissions still produce a
valid 0–99 score, and `null` when no scorable categories exist.

**Program matches (`OllamaService::programMatches()`)** rank all six programs by the same
deterministic trait scorer used by the fallback path. Compatibility percentages normalize each
program's weighted score against its theoretical maximum:

```
compatibility(p) = clamp( round( program_score(p) / (n_items × 5 × Σ weights(p)) × 100 ), 1, 99 )
```

When the AI recommendation passed validity screening it is pinned to the top of the match list
(`is_top_match`), so students always see their official recommendation first, followed by the
statistically nearest alternatives.

## 7. Data Persistence and Privacy

- Each submission inserts one `assessment_results` row: program, confidence, fit level, source,
  strongest trait (+ %), validity flags, completion seconds, full AI payload JSON, and category
  summary JSON. Retakes append history rather than overwrite.
- Raw per-item answers remain upserted per user in `answers`.
- Results pages read from the database, so recommendations survive session expiry.
- Before any collection, students must accept the **RA 10173 (Data Privacy Act of 2012)**
  privacy notice at `/assessment/consent`; acceptance is timestamped on the user record
  (`users.privacy_consented_at`). Both the assessment page and its submit endpoint enforce it.

## 8. Limitations

- Self-report instrument: no ability or achievement measures are collected.
- Small item counts per category limit internal-consistency reliability; results should be read
  as directional guidance.
- LLM recommendations are stochastic even at low temperature; the deterministic scorer provides
  reproducibility when the AI path is unavailable, but AI/fallback agreement was not calibrated.
- Confidence figures are uncalibrated model self-reports.
