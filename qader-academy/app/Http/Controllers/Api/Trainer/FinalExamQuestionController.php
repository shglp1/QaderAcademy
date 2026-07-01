<?php

namespace App\Http\Controllers\Api\Trainer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trainer\StoreFinalExamQuestionRequest;
use App\Http\Requests\Trainer\UpdateFinalExamQuestionRequest;
use App\Http\Resources\FinalExamQuestionResource;
use App\Models\FinalExam;
use App\Models\FinalExamQuestion;
use Illuminate\Support\Facades\Auth;

class FinalExamQuestionController extends Controller
{
    public function index()
    {
        $trainerId = Auth::id();

        $questions = FinalExamQuestion::whereHas('finalExam.course', function ($query) use ($trainerId) {
            $query->where('trainer_id', $trainerId);
        })
            ->with(['finalExam.course'])
            ->orderBy('order')
            ->get();

        return response()->json([
            'questions' => FinalExamQuestionResource::collection($questions),
        ]);
    }

    public function store(StoreFinalExamQuestionRequest $request)
    {
        $validated = $request->validated();

        $finalExam = FinalExam::whereHas('course', function ($query) {
            $query->where('trainer_id', Auth::id());
        })->findOrFail($validated['final_exam_id']);

        $maxOrder = FinalExamQuestion::where('final_exam_id', $finalExam->id)->max('order') ?? 0;

        $question = FinalExamQuestion::create([
            'final_exam_id' => $finalExam->id,
            'question_en' => $validated['question'],
            'question_ar' => $validated['question_ar'] ?? $validated['question'],
            'type' => $validated['type'],
            'options' => $validated['options'] ?? null,
            'correct_answer_en' => $validated['model_answer'] ?? $validated['correct_answer'],
            'correct_answer_ar' => $validated['model_answer'] ?? $validated['correct_answer'],
            'points' => $validated['points'] ?? 1,
            'order' => $maxOrder + 1,
        ]);

        return response()->json([
            'message' => __('messages.final_exam_question_created'),
            'question' => new FinalExamQuestionResource($question->fresh(['finalExam'])),
        ], 201);
    }

    public function show(FinalExamQuestion $finalExamQuestion)
    {
        if ($finalExamQuestion->finalExam->course->trainer_id !== Auth::id()) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        return response()->json([
            'question' => new FinalExamQuestionResource($finalExamQuestion->load(['finalExam'])),
        ]);
    }

    public function update(UpdateFinalExamQuestionRequest $request, FinalExamQuestion $finalExamQuestion)
    {
        if ($finalExamQuestion->finalExam->course->trainer_id !== Auth::id()) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        $validated = $request->validated();

        $finalExamQuestion->update([
            'question_en' => $validated['question'] ?? $finalExamQuestion->question_en,
            'question_ar' => $validated['question_ar'] ?? $finalExamQuestion->question_ar,
            'type' => $validated['type'] ?? $finalExamQuestion->type,
            'options' => $validated['options'] ?? $finalExamQuestion->options,
            'correct_answer_en' => $validated['model_answer'] ?? $validated['correct_answer'] ?? $finalExamQuestion->correct_answer_en,
            'correct_answer_ar' => $validated['model_answer'] ?? $validated['correct_answer'] ?? $finalExamQuestion->correct_answer_ar,
            'points' => $validated['points'] ?? $finalExamQuestion->points,
        ]);

        return response()->json([
            'message' => __('messages.final_exam_question_updated'),
            'question' => new FinalExamQuestionResource($finalExamQuestion->fresh(['finalExam'])),
        ]);
    }

    public function destroy(FinalExamQuestion $finalExamQuestion)
    {
        if ($finalExamQuestion->finalExam->course->trainer_id !== Auth::id()) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        $finalExamQuestion->delete();

        return response()->json([
            'message' => __('messages.final_exam_question_deleted'),
        ]);
    }
}
