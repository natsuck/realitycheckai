# System Architecture

```mermaid
flowchart TD
    subgraph CLIENT["Presentation Layer  (Browser)"]
        UI["Blade + Tailwind + Alpine.js  UI<br/>Chart.js analytics charts"]
    end

    subgraph APP["Application Layer —  Laravel 12 (PHP)"]
        ROUTES["HTTP routes + middleware<br/>(auth · verified · signed · throttle)"]
        CTRL["Controllers<br/>Assessment · Quiz · QuizAttempt · QuizAnalytics · AdminAnalytics"]
        SERV["Service Layer<br/>OllamaService · TextExtractionService<br/>QuizGeneratorService · AnswerEvaluationService"]
        JOB["Queue Job  (database queue)<br/>GenerateQuizFromMaterial"]
        FALLBACK["Deterministic trait-weighted<br/>fallback scorer"]
    end

    subgraph AI["AI Layer —  local machine"]
        OLLAMA[("Ollama HTTP API<br/>localhost:11434  ·  llama3")]
    end

    subgraph DATA["Data Layer —  MySQL"]
        DB[("Database<br/>users · questions · answers · assessment_results<br/>quizzes · quiz_questions · quiz_attempts<br/>quiz_answers · jobs · cache · sessions")]
    end

    UI -- "HTTPS requests" --> ROUTES
    ROUTES --> CTRL
    CTRL -- "business logic" --> SERV
    SERV -- "POST /api/generate<br/>(structured JSON)" --> OLLAMA
    OLLAMA -- "JSON recommendation /<br/>questions / grading verdicts" --> SERV
    SERV -- "Ollama unreachable" --> FALLBACK
    CTRL -- "dispatch job" --> JOB
    JOB -- "extract → prompt →<br/>validate / repair (× 3)" --> SERV
    CTRL -- "Eloquent ORM<br/>read / write" --> DB
    SERV -- "persist results" --> DB
    JOB -- "insert quiz_questions<br/>transaction" --> DB
```

**Layers:** client (browser) → Laravel application → local Ollama LLM + MySQL. The quiz
generator runs asynchronously through the database-backed queue worker; the assessment
recommendation runs synchronously with an automatic deterministic fallback.