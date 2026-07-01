<?php

namespace App\Http\Controllers\Api\Trainer;

use App\Http\Controllers\Controller;
use App\Models\QuizQuestion;
use App\Models\Quiz;
use App\Http\Requests\Trainer\StoreQuizQuestionRequest;
use Illuminate\Support\Facades\Auth;

class QuizQuestionController extends Controller
{
    public function index()
    {
        $trainerId = Auth::id();
        
        $questions = QuizQuestion::whereHas('quiz.chapter.course', function ($q) use ($trainerId) {
                $q->where('trainer_id', $trainerId);
            })
            ->with(['quiz.chapter.course'])
            ->get();

        return response()->json([
            'questions' => $questions,
        ]);
    }

    public function store(StoreQuizQuestionRequest $request)
    {
        $validated = $request->validated();
        $quiz = Quiz::whereHas('chapter.course', function ($q) {
                $q->where('trainer_id', Auth::id());
            })
            ->findOrFail($validated['quiz_id']);

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'type' => $validated['question_type'],
            'question_en' => $validated['question_text_en'],
            'question_ar' => $validated['question_text_ar'] ?? $validated['question_text_en'],
            'options' => $validated['question_type'] === 'mcq' ? $validated['options'] : null,
            'correct_answer_en' => $validated['question_type'] === 'written'
                ? $validated['correct_answer']
                : null,
            'correct_answer_ar' => $validated['question_type'] === 'written'
                ? $validated['correct_answer']
                : null,
            'points' => $validated['points'] ?? 1,
        ]);

        return response()->json([
            'message' => __('messages.question_created'),
            'question' => $question->fresh(['quiz']),
        ], 201);
    }

    public function show(QuizQuestion $quizQuestion)
    {
        if ($quizQuestion->quiz->chapter->course->trainer_id !== Auth::id()) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        return response()->json([
            'question' => $quizQuestion->load(['quiz']),
        ]);
    }

    public function update(StoreQuizQuestionRequest $request, QuizQuestion $quizQuestion)
    {
        if ($quizQuestion->quiz->chapter->course->trainer_id !== Auth::id()) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        $validated = $request->validated();
        $quizQuestion->update([
            'type' => $validated['question_type'],
            'question_en' => $validated['question_text_en'],
            'question_ar' => $validated['question_text_ar'] ?? $validated['question_text_en'],
            'options' => $validated['question_type'] === 'mcq' ? $validated['options'] : null,
            'correct_answer_en' => $validated['question_type'] === 'written'
                ? $validated['correct_answer']
                : null,
            'correct_answer_ar' => $validated['question_type'] === 'written'
                ? $validated['correct_answer']
                : null,
            'points' => $validated['points'] ?? $quizQuestion->points,
        ]);

        return response()->json([
            'message' => __('messages.question_updated'),
            'question' => $quizQuestion->fresh(['quiz']),
        ]);
    }

    public function destroy(QuizQuestion $quizQuestion)
    {
        if ($quizQuestion->quiz->chapter->course->trainer_id !== Auth::id()) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        $quizQuestion->delete();

        return response()->json([
            'message' => __('messages.question_deleted'),
        ]);
    }
}
