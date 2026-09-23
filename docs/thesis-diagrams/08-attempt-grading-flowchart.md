# Quiz Attempt and Grading Flowchart

```mermaid
flowchart TD
    START([Student starts attempt on a ready quiz])
    LOAD["Load 10 MCQ + 5 identification<br/>start countdown timer"]
    ANSW[Submit answers]
    GMCQ["Grade MCQs deterministically<br/>normalized string match"]
    RESO["Identification: resolve blanks and<br/>exact-normalized matches locally"]
    BATCH["Batch remaining identifications<br/>into one AI grading request"]
    AVAIL{AI available?}
    SEM["Ollama semantic verdicts<br/>{is_correct, confidence, explanation}"]
    FALL["Deterministic fallback per item<br/>exact · substring · Levenshtein ≥ 0.82"]
    SCORE["Compute score, feedback tiers<br/>persist quiz_attempts + quiz_answers<br/>(transaction)"]
    SHOW["Show score card<br/>per-question review with AI explanations"]
    END([End])

    START --> LOAD --> ANSW --> GMCQ --> RESO --> BATCH --> AVAIL
    AVAIL -- "Yes" --> SEM --> SCORE
    AVAIL -- "No" --> FALL --> SCORE
    SCORE --> SHOW --> END
```

- MCQs are graded with deterministic string normalization (no AI call per MCQ).
- Identification answers are graded in **one batched AI request**; blanks and exact
  matches are settled locally first, so a typical submission requires only 0–1 AI calls.
- If batch grading fails, each item falls back to exact / substring / Levenshtein
  similarity matching so a score is always produced.