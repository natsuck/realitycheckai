# Quiz Status State Diagram

```mermaid
stateDiagram-v2
    [*] --> queued: quiz created on upload
    queued --> processing: queue worker starts
    processing --> ready: 10 MCQ + 5 ID validated and inserted
    processing --> failed: text too short ( < 100 chars) /<br/>schema invalid after 3 attempts / exception
    failed --> queued: student clicks "Retry" (re-dispatch)
    ready --> ready: student takes or retakes quiz
    ready --> [*]
    queued --> failed: job exhausted / transfer failed
```

- `queued` → `processing` → `ready` is the happy path; generation is fully asynchronous.
- A `failed` quiz retains the stored error message and can be retried, re-dispatching the
  job without re-uploading the file.