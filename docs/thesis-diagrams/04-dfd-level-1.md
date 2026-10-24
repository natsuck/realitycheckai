# Data Flow Diagram — Level 1

```mermaid
flowchart TD
    S(["STUDENT"])
    C(["GUIDANCE COUNSELOR / ADMIN"])
    O(["OLLAMA LLM  (local host)"])

    P1(("1.0<br/>Registration and<br/>Authentication"))
    P2(("2.0<br/>Assessment<br/>Management"))
    P3(("3.0<br/>AI Recommendation<br/>Engine"))
    P4(("4.0<br/>Quiz<br/>Generation"))
    P5(("5.0<br/>Quiz Attempt<br/>and Grading"))
    P6(("6.0<br/>Counselor Analytics"))

    D1[("D1 · users")]
    D2[("D2 · questions")]
    D3[("D3 · answers")]
    D4[("D4 · assessment_results")]
    D5[("D5 · quizzes")]
    D6[("D6 · quiz_questions")]
    D7[("D7 · quiz_attempts")]
    D8[("D8 · quiz_answers")]

    S <--> P1
    P1 --> D1

    S -- "privacy consent (RA 10173),<br/>26 Likert responses" --> P2
    D2 -- "26 Likert items" --> P2
    P2 -- "raw answers" --> D3
    P2 -- "consented responses" --> P3

    P3 <--> O
    P3 -- "recommendation<br/>(program, confidence, fit)" --> D4

    S -- "upload PDF / DOCX / TXT" --> P4
    P4 -- "extracted text" --> P4
    P4 <--> O
    P4 -- "quiz header" --> D5
    P4 -- "10 MCQ + 5 identification" --> D6

    S -- "attempt answers<br/>(15 questions)" --> P5
    P5 -- "identification batch verdict" --> O
    P5 -- "attempt + score" --> D7
    P5 -- "per-answer results" --> D8
    P5 -- "grades, feedback" --> S

    C -- "aggregate analytics query" --> P6
    P6 -- "aggregates" --> D4
    P6 -- "analytics dashboard" --> C
```

**Processes:** (1.0) authentication, (2.0) assessment capture with reverse scoring,
(3.0) AI recommendation with deterministic fallback, (4.0) quiz generation via the queue
worker, (5.0) attempt grading, (6.0) counselor assessment analytics. The counselor is an
admin `user_type` who reads aggregate assessment reports; students review their quiz results
on each completed attempt.