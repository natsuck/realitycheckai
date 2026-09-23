# Data Flow Diagram — Level 0 (Context)

```mermaid
flowchart TD
    S(["STUDENT"])
    C(["GUIDANCE COUNSELOR / ADMIN"])
    O(["OLLAMA LLM  (local host)"])
    P0(("0.0<br/>AI-Powered Reality Check<br/>System"))

    S -- "student details,<br/>assessment responses,<br/>uploaded learning materials" --> P0
    P0 -- "recommendation report,<br/>practice quizzes,<br/>grades and feedback" --> S
    C -- "analytics queries" --> P0
    P0 -- "aggregate analytics dashboard" --> C
    P0 -- "assessment prompt /<br/>quiz-generation prompt /<br/>grading prompt" --> O
    O -- "structured JSON<br/>(recommendations, questions,<br/>grading verdicts)" --> P0
```