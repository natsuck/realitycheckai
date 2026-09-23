# Sequence Diagram — Quiz Attempt and Grading

```mermaid
sequenceDiagram
    autonumber
    participant B as Browser (Student)
    participant QA as QuizAttemptController
    participant AS as AnswerEvaluationService
    participant OL as Ollama (localhost:11434)
    participant DB as MySQL

    B->>QA: POST /quizzes/{quiz}/attempts (10 MCQ + 5 ID answers)
    QA->>QA: grade MCQs deterministically (normalized string match)
    QA->>QA: resolve blanks and exact-normalized identifications locally
    QA->>AS: evaluateBatch(remaining identification answers)
    alt AI available
        AS->>OL: POST /api/generate (batch grading, temp 0.0)
        OL-->>AS: JSON verdicts [{is_correct, confidence, explanation}]
    else not available or invalid
        AS->>AS: per-item fallback: exact · substring · Levenshtein ≥ 0.82
    end
    AS-->>QA: per-answer verdicts
    QA->>QA: compute score = correct/total × 100 and feedback tiers
    QA->>DB: insert quiz_attempts + quiz_answers (transaction)
    QA-->>B: redirect to attempt review (score card, AI explanations)
```

**Key behaviors:** MCQs never call the AI; identification grading is batched into a single
request so a typical attempt incurs 0–1 AI calls; grading happens inside a transaction so
the attempt and its per-answer rows are stored atomically.