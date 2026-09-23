# Thesis Design Package — AI-Powered Reality Check System for College Program Suitability

Diagram set for the thesis *"AI-Powered Reality Check System for College Program Suitability"*
(STI College Pasay-EDSA, BS Computer Science).

All diagrams are authored in **Mermaid** (markdown) and rendered to **PNG/SVG**.
The diagrams describe the **real implementation**:

- Laravel 12 (PHP) back end + Blade / Tailwind / Alpine.js front end
- Local **Ollama** LLM (`llama3`) reached over HTTP on `localhost:11434`
- MySQL relational database (database driver for queues, cache, sessions)
- PHP `artisan` queue worker for the quiz generator

> **Note for the paper:** Chapter 3 currently describes TensorFlow / PostgreSQL / AWS.
> These figures match the as-built system (Ollama / MySQL / local hosting). Align the
> Chapter 3 prose before final submission — see `paper-alignment.md` in this folder.

## Figure numbering (matches List of Figures)

| Thesis figure | Diagram file | Title |
|---|---|---|
| Figure 2 (replace) | `05-system-flowchart.md` | System flowchart |
| Figure 3a | `03-context-dfd.md` | Data Flow Diagram — Level 0 (Context) |
| Figure 3b | `04-dfd-level-1.md` | Data Flow Diagram — Level 1 |
| Figure 4 (replace) | `02-conceptual-framework-ipo.md` | Method diagram (Input–Process–Output) |
| New | `01-system-architecture.md` | System architecture |
| New | `06-assessment-flowchart.md` | Assessment module flowchart |
| New | `07-quiz-generation-flowchart.md` | AI quiz generation flowchart |
| New | `08-attempt-grading-flowchart.md` | Attempt grading flowchart |
| New | `09-admin-analytics-flowchart.md` | Admin analytics flowchart |
| New | `10-quiz-status-state-diagram.md` | Quiz status state diagram |
| New | `11-usecase-diagram.md` | Use case diagram |
| New | `12-erd.md` | Entity relationship diagram (ERD) |
| New | `13-sequence-assessment.md` | Sequence diagram — assessment submission |
| New | `14-sequence-quiz-generation.md` | Sequence diagram — quiz generation |
| New | `15-sequence-grading.md` | Sequence diagram — answer grading |

## Rendering to images

```bash
# one-time install of mermaid-cli (downloads a bundled Chromium)
npx -y @mermaid-js/mermaid-cli

# render a single file (outputs PNG + SVG into docs/thesis-diagrams/generated/)
npx -y @mermaid-js/mermaid-cli -i docs/thesis-diagrams/05-system-flowchart.md \
  -o docs/thesis-diagrams/generated/05-system-flowchart.png \
  --scale 3 --backgroundColor white -t neutral --width 1400
```

Or use the VS Code "Mermaid Preview" extension / <https://mermaid.live> for one-off edits.

## Facts encoded in these diagrams (from the code)

- Assessment: 26-item Likert (1–5), 6 categories, 3 reverse-scored items (`6 − answer`),
  consent gate (RA 10173), response-validity flags (uniform / near-uniform / rapid <10 s),
  deterministic trait-weighted fallback when Ollama is unreachable (`source: fallback`).
- Recommendation universe: **BSCS, BSIT, BSAIS, BSHM, BSTM, ACT**.
- Quiz generator: uploads PDF/DOCX/TXT ≤ 10 MB → queued job → text extraction
  (≤ 12,000 chars) → strict JSON contract (**10 MCQ + 5 identification**) with repair/retry
  → answered MCQs graded deterministically; identification graded by one batched AI call
  with normalized-match / Levenshtein fallback.
- Admin analytics: counselor-only aggregate dashboard (totals, program distribution,
  live-AI vs fallback share, flagged responses).