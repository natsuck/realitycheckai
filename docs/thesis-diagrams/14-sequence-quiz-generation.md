# Sequence Diagram — AI Quiz Generation

```mermaid
sequenceDiagram
    autonumber
    participant B as Browser (Student)
    participant QC as QuizController
    participant Q as MySQL (quizzes · jobs)
    participant W as Queue Worker (GenerateQuizFromMaterial)
    participant TE as TextExtractionService
    participant QG as QuizGeneratorService
    participant OL as Ollama (localhost:11434)

    B->>QC: POST /quizzes (file ≤ 10 MB, title)
    QC->>QC: validate pdf/docx/txt and size
    QC->>Q: store file and insert quiz (status = queued)
    QC->>Q: dispatch GenerateQuizFromMaterial on the 'ai' queue
    QC-->>B: redirect /quizzes/{quiz} (status polling begins)
    Q->>W: job claimed
    W->>Q: status = processing
    W->>TE: extract(source_file)
    TE->>TE: PDF / DOCX / TXT → cleaned text (limit 12,000 chars)
    TE-->>W: readable text (≥ 100 chars)
    W->>QG: generate(extractedText, quizId)
    loop up to 3 attempts
        QG->>OL: POST /api/generate (strict JSON contract: 10 MCQ + 5 ID)
        OL-->>QG: JSON question set
        QG->>QG: validate schema and on failure build repair prompt
    end
    QG-->>W: validated questions
    W->>Q: transactional insert quiz_questions and status = ready
    loop browser polling every 2 s
        B->>QC: GET /quizzes/{quiz}/status
        QC-->>B: { status: "ready" }
    end
    B->>QC: GET /quizzes/{quiz}
    QC-->>B: quiz page with question browser
```

**Key behaviors:** the upload endpoint returns immediately and all heavy work runs on the
queue worker; validity is guaranteed by a strict JSON schema with a self-repairing prompt
loop (max 3 attempts); a raw generation result may be cached for 7 days to speed up repeat
generations.