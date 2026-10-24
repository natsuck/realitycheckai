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
    PROMPT["QuizGeneratorService prompt<br/>source-grounding rules<br/>15 MCQ + 10 flashcards"]
    GEN["POST OpenAI chat completions<br/>(structured JSON)"]
    SCH{Schema valid?<br/>15 MCQs · 4 distinct choices ·<br/>10 flashcards · explanations}
    RTRY{Attempt ≤ 3?}
    REPAIR["Rebuild repair prompt with<br/>validation error + 2,500-char preview"]
    INS["Transactional insert<br/>questions + flashcards → status = 'ready'"]
    FOLL["Browser polls /status<br/>every 2 s until ready"]
    TAKER["Student takes quiz<br/>server starts timer; refresh preserves remaining time"]
    SUBMIT["Student submits answers"]
    VALIDATE{"Server checks started attempt,<br/>all answers, and allowed MCQ choices"}
    RETRY["Show validation errors;<br/>attempt remains open"]
    TIMEOUT["Time limit expired;<br/>allow unanswered items"]
    GRADE["Grade and save completed attempt<br/>using server-recorded start time"]
    REVIEW["Review questions, correct answers,<br/>and explanations after attempt 1"]
    STUDY["Flashcards available<br/>in separate ungraded study mode"]
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
    INS --> FOLL --> TAKER --> SUBMIT --> VALIDATE
    VALIDATE -- "Complete and valid" --> GRADE
    VALIDATE -- "Incomplete before timeout" --> RETRY --> TAKER
    VALIDATE -- "Expired" --> TIMEOUT --> GRADE
    GRADE --> REVIEW --> END
    FOLL --> STUDY --> END
```

- The upload endpoint responds immediately; generation runs asynchronously on the queue
  worker so the UI is not blocked.
- Generated questions and flashcards are validated against a strict JSON contract and
  repaired/regenerated up to three times before the quiz is marked `ready`.
- Students must complete an attempt before generated questions, answer keys, and explanations are
  available for review.
- The server enforces the attempt start time, duration, answer completeness before timeout, and
  that MCQ responses match the generated choices. After the server-measured time expires, unanswered
  items may be submitted and are scored as unanswered.
- Answer keys and explanations are revealed after the first completed attempt.