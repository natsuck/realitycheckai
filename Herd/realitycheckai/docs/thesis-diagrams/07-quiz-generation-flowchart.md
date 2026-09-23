# AI Quiz Generator Flowchart

```mermaid
flowchart TD
    START([Student uploads<br/>learning material])
    VAL{Valid file?<br/>pdf · docx · txt<br/>≤ 10 MB}
    ERR[Reject with validation error]
    STORE["Store file to private storage<br/>(local / uploads)"]
    CREATE["Create quiz record<br/>status = 'queued'"]
    DISP["Dispatch GenerateQuizFromMaterial<br/>on the 'ai' queue"]
    PROC["Queue worker picks job<br/>status = 'processing'"]
    EXTR["TextExtractionService<br/>PDF / DOCX / TXT → cleaned text<br/>(limit 12,000 chars)"]
    READ{Readable text ≥ 100 chars?}
    PROMPT["QuizGeneratorService prompt<br/>anti-hallucination rules<br/>10 MCQ + 5 identification"]
    GEN["POST Ollama /api/generate<br/>(JSON mode)"]
    SCH{Schema valid?<br/>exact counts · 4 choices ·<br/>difficulty enum}
    RTRY{Attempt ≤ 3?}
    REPAIR["Rebuild repair prompt with<br/>validation error + 2,500-char preview"]
    INS["Transactional insert<br/>quiz_questions → status = 'ready'"]
    FOLL["Browser polls /status<br/>every 2 s until ready"]
    TAKER["Student opens quiz and takes it"]
    FAIL["status = 'failed'<br/>generation_error recorded"]
    END([End])

    START --> VAL
    VAL -- "No" --> ERR --> END
    VAL -- "Yes" --> STORE
    STORE --> CREATE --> DISP --> PROC --> EXTR
    EXTR --> READ
    READ -- "No" --> FAIL --> END
    READ -- "Yes" --> PROMPT --> GEN --> SCH
    SCH -- "Yes" --> INS
    SCH -- "No" --> RTRY
    RTRY -- "Yes" --> REPAIR --> GEN
    RTRY -- "No" --> FAIL
    INS --> FOLL --> TAKER --> END
```

- The upload endpoint responds immediately; generation runs asynchronously on the queue
  worker so the UI is not blocked.
- Generated questions are validated against a strict JSON contract and repaired/regenerated
  up to three times before the quiz is marked `ready`.