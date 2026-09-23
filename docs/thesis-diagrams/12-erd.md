# Entity Relationship Diagram (ERD)

```mermaid
erDiagram
    users {
        bigint id PK
        string name
        string email UK
        timestamp email_verified_at
        string password
        string user_type "user | admin"
        timestamp privacy_consented_at
        timestamp created_at
        timestamp updated_at
    }

    questions {
        bigint id PK
        string question_text
        string category "skills|interest|personality|work_style|values|technical"
        boolean is_reverse
        timestamp created_at
        timestamp updated_at
    }

    answers {
        bigint id PK
        bigint user_id FK
        bigint question_id FK
        integer answer "1-5 after inversion"
        timestamp created_at
        timestamp updated_at
    }

    assessment_results {
        bigint id PK
        bigint user_id FK
        string program_code "BSCS|BSIT|BSAIS|BSHM|BSTM|ACT"
        string program_name
        decimal confidence "0-100"
        string fit_level
        string source "ollama | fallback"
        string strongest_trait
        decimal strongest_trait_percentage
        json response_flags
        integer completion_seconds
        json result
        json category_summary
        timestamp completed_at
        timestamp created_at
        timestamp updated_at
    }

    quizzes {
        bigint id PK
        bigint user_id FK
        string title
        string source_file
        string original_filename
        text extracted_text
        enum status "queued|processing|ready|failed"
        string ai_model
        decimal ai_confidence
        text generation_error
        timestamp generated_at
        timestamp created_at
        timestamp updated_at
    }

    quiz_questions {
        bigint id PK
        bigint quiz_id FK
        enum type "mcq|identification"
        text question
        json choices "MCQ only"
        text correct_answer
        enum difficulty "easy|medium|hard"
        timestamp created_at
        timestamp updated_at
    }

    quiz_attempts {
        bigint id PK
        bigint user_id FK
        bigint quiz_id FK
        decimal score "percentage"
        integer total_questions
        integer correct_count
        integer mcq_score
        integer identification_score
        decimal ai_confidence
        text feedback
        timestamp started_at
        timestamp completed_at
        timestamp created_at
        timestamp updated_at
    }

    quiz_answers {
        bigint id PK
        bigint attempt_id FK
        bigint question_id FK
        text user_answer
        boolean is_correct
        text ai_explanation
        decimal ai_confidence
        timestamp created_at
        timestamp updated_at
    }

    users ||--o{ answers : "provides"
    questions ||--o{ answers : "answered on"
    users ||--o{ assessment_results : "earns"
    users ||--o{ quizzes : "owns"
    quizzes ||--o{ quiz_questions : "contains"
    quizzes ||--o{ quiz_attempts : "attempted via"
    users ||--o{ quiz_attempts : "takes"
    quiz_attempts ||--o{ quiz_answers : "records"
    quiz_questions ||--o{ quiz_answers : "answered in"
```

- **Assessment domain:** `users → answers → questions` (raw 1–5 Likert responses, reverse
  scored), and `users → assessment_results` (one row per submission = history).
- **Quiz domain:** `quizzes → quiz_questions`, `quizzes → quiz_attempts`, and
  `quiz_answers` join attempts to questions with per-answer AI explanations.
- Supporting Laravel tables (`password_reset_tokens`, `sessions`, `jobs`, `cache`, etc.)
  also exist but are framework infrastructure rather than domain data.