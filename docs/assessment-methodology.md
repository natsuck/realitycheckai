# Assessment Methodology

This document describes the current 150-item Aguhon College Career Guidance Assessment and how
the app stores and reports its results. It does not replace the psychometrician-approved
administration and scoring protocol.

## 1. Instrument and versioning

The current instrument version is `aguhon-150-v1`, seeded by
`database/seeders/QuestionSeeder.php`. It contains:

| Section | Items | Scoring |
|---|---:|---|
| RIASEC career-interest inventory | 90 (15 per RIASEC scale) | Student rating from 1 (Strongly Dislike) to 5 (Strongly Like) |
| Verbal reasoning and reading comprehension | 20 | Multiple choice, keyed correct/incorrect |
| Quantitative reasoning | 20 | Multiple choice, keyed correct/incorrect |
| Analytical and scientific reasoning | 20 | Multiple choice, keyed correct/incorrect |

The approved PDF's order is preserved using the question `item_number`. Each question stores its
instrument version, section, item type, RIASEC scale when applicable, choices, and answer key.
The legacy 26-item question bank is retained as unversioned historical content; it is not included
in the current questionnaire.

## 2. Scoring

`App\Services\AssessmentScoringService` enforces the expected total and section sizes before
producing a score profile.

For each RIASEC scale:

```text
scale average = sum of the 15 ratings / 15
display percentage = (scale average / 5) × 100
```

For each aptitude section:

```text
section score = number of correct responses out of 20
section percentage = (correct responses / 20) × 100
```

The RIASEC and aptitude results remain separate; the app does not combine them into a single
overall score or compare them with a norm group. Responses are stored as selected, each aptitude
response also records whether it matched the key, and each result stores its computed score
breakdown.

The assessment requires all 150 responses. Every submission creates a distinct
`assessment_results` record and associates that attempt's responses with it, preserving retake
history instead of overwriting earlier answers.

## 3. Exploratory program matches and AI explanation

The supplied instrument defines item groupings and aptitude answers but does not provide an
approved mapping from score profiles to BSAIS, BSIT, BSCS, BSTM, or BSHM. The app therefore
generates **exploratory AI matches**, not a validated NCAE score-to-program mapping.

The AI receives the computed scores and the app's brief program descriptions. It ranks the five
programs, explains possible fit using reported score evidence, and suggests ways to explore each
option. RIASEC scores are the primary matching context; aptitude scores remain separate and are
supporting context only. The AI is instructed not to calculate combined scores, invent weights,
produce compatibility probabilities, infer eligibility, or claim psychometric validation. The
service validates each returned program code against the five-program catalog and uses canonical
program names. If the AI response is invalid or unavailable, the app shows the profile without
inventing program matches.

The result page explicitly describes matches as exploratory, not school-validated, placement,
admission, or a prediction of academic success. A student's interests, goals, and own choice take
precedence, and counselor discussion is encouraged. Program descriptions are brief summaries and
may not reflect the school's complete curriculum.

The AI's ranked output can be nondeterministic and should be evaluated with counselors and
students before making claims about its usefulness. If the school or psychometrician later
approves a score-to-program mapping, that is a separate, versioned method and must not be
confused with these exploratory AI suggestions.

## 4. Response flags

The current flow flags a uniform or near-uniform pattern across the 90 RIASEC ratings. It does not
apply that check to aptitude answers because their numeric values represent answer choices rather
than an agreement scale. The former rapid-completion threshold was defined for the shorter
legacy questionnaire and is not applied to the 150-item version without an approved timing rule.

## 5. Privacy and interpretation

- Students must accept the RA 10173 (Data Privacy Act of 2012) privacy notice before assessment
  data is collected. The application stores account details, item responses, computed profiles,
  suggestions, and assessment history; authorized school administrators can view identifiable
  recent submissions.
- For current-instrument AI guidance, the computed score profile and program descriptions are sent
  to the configured AI provider (OpenAI when selected). Student names, email addresses, and
  individual item responses are not included in that AI request. Provider processing is subject to
  the provider's applicable terms and privacy practices.
- Results are academic guidance, not a definitive enrollment, admission, or placement decision.
- The item wording, answer key, and administration protocol are governed by the approved
  psychometrician-validated instrument. Changes require a new instrument version and review.
- Historical legacy results remain available and are distinguishable from the current version by
  their `instrument_version`.
