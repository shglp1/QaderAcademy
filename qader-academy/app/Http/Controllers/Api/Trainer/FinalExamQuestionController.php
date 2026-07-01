<?php

namespace App\Http\Controllers\Api\Trainer;

use App\Http\Controllers\Controller;
use App\Models\FinalExamQuestion;
use App\Models\FinalExam;
use App\Http\Requests\Trainer\StoreFinalExamQuestionRequest;
use App\Http\Requests\Trainer\UpdateFinalExamQuestionRequest;
use App\Http\Resources\FinalExamQuestionResource;
use Illuminate\Support\Facades\Auth;

class FinalExamQuestionController extends Controller
{
    /**
     * Display questions for the trainer's final exams.
     */
    public function index()
    {
        $trainerId = Auth::id();
        
        $questions = FinalExamQuestion::whereHas('finalExam.course', function ($q) use ($trainerId) {
                $q->where('trainer_id', $trainerId);
            })
            ->with(['finalExam.course'])
            ->orderBy('order')
            ->get();
        
        return response()->json([
            'questions' => FinalExamQuestionResource::collection($questions),
        ]);
    }

    /**
     * Store a newly created final exam question.
     */
    public function store(StoreFinalExamQuestionRequest $request)
    {
        $validated = $request->validated();
        
        // Verify final exam ownership
        $finalExam = FinalExam::whereHas('course', function ($q) use ($trainerId = Auth::id()) {
                $q->where('trainer_id', $trainerId);
            })
            ->findOrFail($validated['final_exam_id']);
        
        // Get the next order number
        $maxOrder = FinalExamQuestion::where('final_exam_id', $finalExam->id)->max('order') ?? 0;
        
        $question = FinalExamQuestion::create([
            'final_exam_id' => $finalExam->id,
            'question' => $validated['question'],
            'question_ar' => $validated['question_ar'] ?? null,
            'type' => $validated['type'],
            'options' => $validated['options'] ?? null,
            'correct_answer' => $validated['correct_answer'],
            'model_answer' => $validated['model_answer'] ?? null,
            'points' => $validated['points'] ?? 1,
            'order' => $maxOrder + 1,
        ]);
        
        return response()->json([
            'message' => __('messages.final_exam_question_created'),
            'question' => new FinalExamQuestionResource($question->fresh(['finalExam'])),
        ], 201);
    }

    /**
     * Display the specified final exam question.
     */
    public function show(FinalExamQuestion $finalExamQuestion)
    {
        $this->authorize('view', $finalExamQuestion);
        
        return response()->json([
            'question' => new FinalExamQuestionResource($finalExamQuestion->load(['finalExam'])),
        ]);
    }

    /**
     * Update the specified final exam question.
     */
    public function update(UpdateFinalExamQuestionRequest $request, FinalExamQuestion $finalExamQuestion)
    {
        $this->authorize('update', $finalExamQuestion);
        
        $validated = $request->validated();
        
        $finalExamQuestion->update([
            'question' => $validated['question'] ?? $finalExamQuestion->question,
            'question_ar' => $validated['question_ar'] ?? $finalExamQuestion->question_ar,
            'type' => $validated['type'] ?? $finalExamQuestion->type,
            'options' => $validated['options'] ?? $finalExamQuestion->options,
            'correct_answer' => $validated['correct_answer'] ?? $finalExamQuestion->correct_answer,
            'model_answer' => $validated['model_answer'] ?? $finalExamQuestion->model_answer,
            'points' => $validated['points'] ?? $finalExamQuestion->points,
        ]);
        
        return response()->json([
            'message' => __('messages.final_exam_question_updated'),
            'question' => new FinalExamQuestionResource($finalExamQuestion->fresh(['finalExam'])),
        ]);
    }

    /**
     * Remove the specified final exam question.
     */
    public function destroy(FinalExamQuestion $finalExamQuestion)
    {
        $this->authorize('delete', $finalExamQuestion);
        
        $finalExamQuestion->delete();
        
        return response()->json([
            'message' => __('messages.final_exam_question_deleted'),
        ]);
    }
}
