# Use Case Diagram

```mermaid
flowchart LR
    S([STUDENT])
    C([GUIDANCE COUNSELOR / ADMIN])

    subgraph SYSTEM["AI-Powered Reality Check System"]
        UC1("Register and log in")
        UC2("Give privacy consent (RA 10173)")
        UC3("Take program suitability assessment<br/>(26-item Likert)")
        UC4("View recommendation report and history")
        UC5("Upload learning material<br/>(PDF / DOCX / TXT ≤ 10 MB)")
        UC6("Generate AI practice quizzes")
        UC7("Take quizzes with countdown timer")
        UC8("View graded attempts and AI explanations")
        UC9("View personal quiz analytics")
        UC10("Manage own uploaded quizzes")
        UC11("Admin: view guidance counselor analytics")
        UC12("Manage profile, password, account")
    end

    S --- UC1
    S --- UC2
    S --- UC3
    S --- UC4
    S --- UC5
    S --- UC6
    S --- UC7
    S --- UC8
    S --- UC9
    S --- UC10
    S --- UC12
    C --- UC11
    C --- UC12
```

**Actors:** the *Student* (senior high school, incoming, or freshman college enrollee)
performs the assessment and quiz flows; the *Guidance Counselor / Admin* additionally
accesses the aggregate analytics dashboard (use case 11).