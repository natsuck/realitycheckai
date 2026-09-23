# System Flowchart

```mermaid
flowchart TD
    START([Start])
    HOME["Open home / landing page"]
    AUTH{Registered?}
    REG["Register / login<br/>email verification"]
    DASH["Student dashboard"]
    MOD{Select module}
    M1["Program Suitability Assessment"]
    M2["AI Quiz Generator"]
    CHK{user_type = admin?}
    DEN(("403<br/>Forbidden"))
    M3["Guidance Counselor<br/>Analytics Dashboard"]
    END([End])

    START --> HOME
    HOME --> AUTH
    AUTH -- "No" --> REG
    AUTH -- "Yes" --> DASH
    REG --> DASH
    DASH --> MOD
    MOD -- "Assess my program fit" --> M1
    MOD -- "Study documents / take quizzes" --> M2
    MOD -- "Counselor analytics" --> CHK
    M1 --> END
    M2 --> END
    CHK -- "Yes" --> M3 --> END
    CHK -- "No" --> DEN --> END
```

- Authenticated students may take the suitability assessment or use the AI quiz generator.
- Only accounts with `user_type = admin` (the guidance counselor) may open the analytics
  dashboard; others receive `403 Forbidden`.