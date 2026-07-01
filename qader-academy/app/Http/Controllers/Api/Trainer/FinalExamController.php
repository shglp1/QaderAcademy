<?php

namespace App\Http\Controllers\Api\Trainer;

use App\Http\Controllers\Controller;
use App\Models\FinalExam;
use App\Models\Course;
use Illuminate\Support\Facades\Auth;

class FinalExamController extends Controller
{
    public function index()
    {
        $trainerId = Auth::id();
        
        $exams = FinalExam::whereHas('course', function ($q) use ($trainerId) {
                $q->where('trainer_id', $trainerId);
            })
            ->with(['course'])
            ->get();

        return response()->json([
            'exams' => $exams,
        ]);
    }

    public function store($courseId)
    {
        $course = Course::where('trainer_id', Auth::id())->findOrFail($courseId);

        $exam = FinalExam::create([
            'course_id' => $course->id,
            'title_en' => 'Final Exam',
            'title_ar' => 'الاختبار النهائي',
            'passing_score' => 50,
        ]);

        return response()->json([
            'message' => __('messages.final_exam_created'),
            'exam' => $exam->fresh(['course']),
        ], 201);
    }

    public function show(FinalExam $finalExam)
    {
        if ($finalExam->course->trainer_id !== Auth::id()) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        return response()->json([
            'exam' => $finalExam->load(['course', 'questions']),
        ]);
    }

    public function update($courseId, FinalExam $finalExam)
    {
        if ($finalExam->course->trainer_id !== Auth::id()) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        $validated = request()->validate([
            'title_en' => 'sometimes|string|max:255',
            'title_ar' => 'nullable|string|max:255',
            'passing_score' => 'sometimes|integer|min:0|max:100',
        ]);

        $finalExam->update($validated);

        return response()->json([
            'message' => __('messages.final_exam_updated'),
            'exam' => $finalExam->fresh(['course', 'questions']),
        ]);
    }

    public function destroy(FinalExam $finalExam)
    {
        if ($finalExam->course->trainer_id !== Auth::id()) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        $finalExam->delete();

        return response()->json([
            'message' => __('messages.final_exam_deleted'),
        ]);
    }
}
