# Assessment Module Flowchart

```mermaid
flowchart TD
    START([Student selects<br/>Program Suitability Assessment])
    CON{Privacy consent<br/>recorded?}
    FORM["Show RA 10173 consent notice"]
    CONS{Consented?}
    STAMP["Save privacy_consented_at"]
    WIZ["Present 26-item Likert wizard<br/>one question at a time"]
    SUB[Submit responses]
    FULL{All 26 answered?}
    REV["Reverse-score reversed items<br/>answer = 6 - answer"]
    UPS["Upsert raw answers<br/>(answers table)"]
    AI["POST Ollama /api/generate<br/>recommendationSchema (JSON)"]
    VAL{Valid JSON?}
    RET{Attempts<br/>remaining?}
    FALL["Deterministic trait-weighted scorer<br/>source = 'fallback'"]
    FLAGS["Compute response flags<br/>uniform · near-uniform · rapid (< 10 s)"]
    SAVE["Insert assessment_results<br/>result + category_summary + source"]
    RPT["Show recommendation report<br/>AI-generated / Offline badge<br/>flags · history"]
    END([End])

    START --> CON
    CON -- "No" --> FORM --> CONS
    CONS -- "No" --> END
    CONS -- "Yes" --> STAMP --> WIZ
    CON -- "Yes" --> WIZ
    WIZ --> SUB --> FULL
    FULL -- "No" --> WIZ
    FULL -- "Yes" --> REV --> UPS
    UPS --> AI --> VAL
    VAL -- "Yes" --> SAVE
    VAL -- "No" --> RET
    RET -- "Yes" --> AI
    RET -- "No" --> FALL --> SAVE
    FLAGS --> SAVE
    SAVE --> RPT --> END
```

- Consent is a one-time gate; it is recorded on the `users` table before any data is
  collected (RA 10173 Data Privacy Act).
- The AI recommendation is synchronous; if Ollama is unreachable or returns invalid JSON
  after the configured retries, the deterministic trait-weighted scorer guarantees a
  result tagged `source = fallback`, shown to the student as an "Offline estimate".