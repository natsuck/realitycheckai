<?php

namespace App\Jobs;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Services\QuizGeneratorService;
use App\Services\TextExtractionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateQuizFromMaterial implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10];

    public function __construct(public int $quizId)
    {
        $this->onQueue('ai');
    }

    public function handle(TextExtractionService $extractor, QuizGeneratorService $generator): void
    {
        $quiz = Quiz::query()->findOrFail($this->quizId);

        $quiz->update([
            'status' => Quiz::STATUS_PROCESSING,
            'generation_error' => null,
            'ai_model' => config('services.openai.models.quiz', 'gpt-4o-mini'),
        ]);

        $extractedText = $extractor->extract($quiz->source_file);
        $generatedQuiz = $generator->generate($extractedText);

        DB::transaction(function () use ($quiz, $extractedText, $generatedQuiz): void {
            $quiz->questions()->delete();
            $quiz->flashcards()->delete();

            foreach ($generatedQuiz['mcqs'] as $question) {
                $quiz->questions()->create([
                    'type' => QuizQuestion::TYPE_MCQ,
                    'question' => $question['question'],
                    'choices' => $question['choices'],
                    'correct_answer' => $question['answer'],
                    'explanation' => $question['explanation'],
                ]);
            }

            foreach ($generatedQuiz['flashcards'] as $flashcard) {
                $quiz->flashcards()->create([
                    'front' => $flashcard['front'],
                    'back' => $flashcard['back'],
                ]);
            }

            $quiz->update([
                'extracted_text' => $extractedText,
                'status' => Quiz::STATUS_READY,
                'ai_confidence' => $generatedQuiz['confidence'],
                'generated_at' => now(),
            ]);
        });

        Log::info('AI quiz generated successfully.', [
            'quiz_id' => $quiz->id,
            'question_count' => count($generatedQuiz['mcqs']),
            'flashcard_count' => count($generatedQuiz['flashcards']),
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Quiz::query()
            ->whereKey($this->quizId)
            ->update([
                'status' => Quiz::STATUS_FAILED,
                'generation_error' => mb_substr($exception->getMessage(), 0, 1000),
            ]);

        Log::error('AI quiz generation job failed.', [
            'quiz_id' => $this->quizId,
            'message' => $exception->getMessage(),
        ]);
    }
}
