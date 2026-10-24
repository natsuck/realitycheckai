# AI Quiz Generator Prompt Engineering Notes

## Module

AI Quiz Generator from Uploaded Learning Materials

## Core AI Controls

- The quiz generator uses the uploaded document text as the only permitted knowledge source.
- The prompt explicitly rejects outside knowledge and unsupported questions.
- Ollama JSON mode is requested with `format: json`.
- The service extracts the first valid JSON object if the model wraps the answer in extra text.
- The decoded JSON is validated before database insertion.
- Invalid JSON, missing fields, wrong question counts, malformed choices, or MCQ answers that do not match choices trigger regeneration.
- Quiz generation runs through a queue job so upload requests stay responsive and failures can be retried.

## Quiz Generation Contract

The AI must return:

- 15 multiple choice questions for a graded quiz
- 10 flashcards for a separate, ungraded study mode
- a short source-grounded explanation for every MCQ answer
- a generation confidence score from `0` to `100`

The expected JSON object is:

```json
{
  "confidence": 0,
  "mcqs": [
    {
      "question": "",
      "choices": ["", "", "", ""],
      "answer": "",
      "explanation": ""
    }
  ],
  "flashcards": [
    {
      "front": "",
      "back": ""
    }
  ]
}
```

Students take the quiz before reviewing generated questions. Answer keys and explanations are
revealed after the first completed attempt. Flashcards are studied separately and are not included
in graded attempts. New generations do not include difficulty labels or identification questions;
existing identification questions remain supported for historical quizzes.

## Hallucination Prevention

- Prompt rule: questions and answers must come only from the uploaded document.
- Prompt rule: unsupported questions must not be generated.
- Strict JSON validation prevents storing vague prose responses.
- MCQ answer validation requires the answer key to exactly match one of four distinct choices.
- MCQ explanations and flashcard backs must be non-empty and grounded in the uploaded material.
- Extracted text is cleaned and length-limited through configuration to fit the local model context.

## Semantic Answer Evaluation

Existing identification answers are evaluated by the answer-checking service with a second strict
JSON prompt:

```json
{
  "is_correct": true,
  "confidence": 0,
  "explanation": ""
}
```

The evaluator accepts semantically equivalent answers, abbreviations, harmless word order
differences, and minor grammar differences. It rejects vague, contradictory, or incomplete
answers. New quiz generations use MCQs instead; this evaluation remains for older quizzes.

## Deployment Variables

Configure Ollama in `.env`:

```env
OLLAMA_BASE_URL=http://localhost:11434
OLLAMA_MODEL=llama3
OLLAMA_TIMEOUT=90
OLLAMA_RETRIES=2
OLLAMA_TEMPERATURE=0.2
OLLAMA_MAX_DOCUMENT_CHARS=12000
OLLAMA_QUIZ_JSON_RETRIES=3
OLLAMA_ANSWER_JSON_RETRIES=2
```

For local processing, run:

```bash
php artisan queue:work --queue=ai,default --tries=3
```

The `composer dev` script already starts a worker listening on `ai,default`.

## Generation Speed Tuning (CPU)

On CPU-only machines, generation time is dominated by the number of tokens the
model produces. The app applies these optimizations:

- **Validated-result caching** — `QuizGeneratorService` caches validated quizzes
  keyed by model + document text (`OLLAMA_CACHE_TTL`, default 7 days, `0`
  disables). Re-uploading identical material returns instantly.
- **Brevity-constrained prompts** — questions, choices, explanations, and flashcard content are
  kept concise so fewer tokens are generated.
- **Queue priority** — the worker consumes `ai` before `default`, and the dev
  script targets both queues.
- **Warm model** — `OLLAMA_KEEP_ALIVE=30m` avoids model reloads between requests.

Additional Ollama server-side levers (set before starting `ollama serve`):

```bash
# Use physical cores only; hyperthreads usually slow token generation
OLLAMA_NUM_THREAD=8 ollama serve

# Avoid splitting compute across parallel slots when serving one user
OLLAMA_NUM_PARALLEL=1 ollama serve

# Optional: flash attention / quantized KV cache (smaller memory footprint)
OLLAMA_FLASH_ATTENTION=1 OLLAMA_KV_CACHE_TYPE=q8_0 ollama serve
```

Verify what is active with `ollama ps`. If quality allows, switching
`OLLAMA_MODEL` to `llama3.2:1b` roughly doubles or triples tokens/sec on CPU.

## Program Recommendation Output

The assessment module now requests structured JSON from Ollama instead of plain text. This makes the recommendation easier to audit and display in the UI.

Expected recommendation object:

```json
{
  "recommended_program": {
    "code": "BSIT",
    "name": "Bachelor of Science in Information Technology"
  },
  "confidence": 0,
  "fit_level": "Strong Fit",
  "reason": "",
  "strengths": ["", "", ""],
  "development_areas": ["", ""],
  "feedback": "",
  "evidence": ["", "", ""],
  "next_steps": ["", "", ""],
  "alternative_programs": [
    {
      "code": "BSCS",
      "name": "Bachelor of Science in Computer Science",
      "reason": ""
    }
  ]
}
```

If Ollama is unavailable or returns malformed JSON, the app falls back to a deterministic assessment-pattern recommendation. The fallback is clearly marked in the data as `source: fallback`, so panelists can distinguish true AI output from continuity-safe output during demos.
