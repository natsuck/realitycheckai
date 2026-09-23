# Sequence Diagram — Assessment Submission

```mermaid
sequenceDiagram
    autonumber
    participant B as Browser (Student)
    participant AC as AssessmentController
    participant OS as OllamaService
    participant OL as Ollama (localhost:11434)
    participant DB as MySQL

    B->>AC: POST /assessment/submit (26 Likert answers)
    AC->>AC: reverse-score reversed items (6 - answer)
    AC->>DB: upsert answers rows
    AC->>OS: analyze(answers)
    alt Ollama reachable
        OS->>OL: POST /api/generate (recommendationSchema, temp 0.15)
        OL-->>OS: structured JSON recommendation
    else unreachable or invalid JSON after retries
        OS->>OS: deterministic trait-weighted scorer (source = fallback)
    end
    OS-->>AC: normalized recommendation payload
    AC->>AC: response flags + category summary + strongest trait
    AC->>DB: insert assessment_results (history row)
    AC-->>B: redirect to /results
    B->>AC: GET /results
    AC->>DB: read latest assessment_results
    DB-->>AC: persisted result (survives session expiry)
    AC-->>B: render recommendation report (AI / Offline badge)
```

**Key behaviors:** reverse scoring happens before persistence; the AI result is
*persisted*, so a later session reload reads the record from the database rather than from
the session; validity flags (uniform / near-uniform / < 10 s completion) are computed
server side and attached to the saved record.