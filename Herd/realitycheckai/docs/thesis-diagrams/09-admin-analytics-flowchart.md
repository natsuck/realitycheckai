# Admin Analytics Flowchart (Guidance Counselor)

```mermaid
flowchart TD
    START([Counselor opens /admin/analytics])
    ADM{user_type = admin?}
    AGG["Aggregate from tables:<br/>assessment_results · users · quiz_attempts"]
    KPI["KPIs: total submissions · unique students ·<br/>average confidence · live-AI vs fallback share"]
    PROG["Program distribution counts<br/>BSCS · BSIT · BSAIS · BSHM · BSTM · ACT"]
    FLAG["Flagged response count<br/>uniform · near-uniform · rapid completion"]
    REC["Recent submissions table<br/>(searchable)"]
    FEED["Return program distribution<br/>JSON feed (/admin/analytics/programs)"]
    REND["Render analytics dashboard"]
    DEN(("403<br/>Forbidden"))
    END([End])

    START --> ADM
    ADM -- "No" --> DEN --> END
    ADM -- "Yes" --> AGG --> KPI
    KPI --> PROG --> FLAG --> REC --> REND
    REND --> FEED --> END
```

- Read-only access: the counselor views aggregates and per-program breakdowns; no
  individual student records are exposed beyond the summary table.
- The distribution feed powers the front end charts as JSON.