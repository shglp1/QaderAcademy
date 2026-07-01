<?php

namespace App\Http\Controllers\Api\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\Chapter;
use App\Http\Requests\Trainer\StoreQuizRequest;
use App\Http\Resources\QuizResource;
use Illuminate\Support\Facades\Auth;

class QuizController extends Controller
{
    public function index()
    {
        $trainerId = Auth::id();
        
        $quizzes = Quiz::whereHas('chapter.course', function ($q) use ($trainerId) {
                $q->where('trainer_id', $trainerId);
            })
            ->with(['chapter.course'])
            ->get();

        return response()->json([
            'quizzes' => QuizResource::collection($quizzes),
        ]);
    }

    public function store(StoreQuizRequest $request)
    {
        $validated = $request->validated();
        $chapter = Chapter::whereHas('course', function ($q) {
                $q->where('trainer_id', Auth::id());
            })
            ->findOrFail($validated['chapter_id']);

        $quiz = Quiz::create([
            'chapter_id' => $chapter->id,
            'title_en' => $validated['title_en'],
            'title_ar' => $validated['title_ar'] ?? $validated['title_en'],
            'description_en' => $validated['description_en'] ?? null,
            'description_ar' => $validated['description_ar'] ?? null,
            'passing_score' => $validated['passing_score'] ?? 70,
        ]);

        return response()->json([
            'message' => __('messages.quiz_created'),
            'quiz' => new QuizResource($quiz->fresh(['chapter.course'])),
        ], 201);
    }

    public function show(Quiz $quiz)
    {
        if ($quiz->chapter->course->trainer_id !== Auth::id()) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        $quiz->load(['chapter.course', 'questions']);

        return response()->json([
            'quiz' => new QuizResource($quiz),
        ]);
    }

    public function update(StoreQuizRequest $request, Quiz $quiz)
    {
        if ($quiz->chapter->course->trainer_id !== Auth::id()) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        $validated = $request->validated();
        $quiz->update($validated);

        return response()->json([
            'message' => __('messages.quiz_updated'),
            'quiz' => new QuizResource($quiz->fresh(['chapter.course', 'questions'])),
        ]);
    }

    public function destroy(Quiz $quiz)
    {
        if ($quiz->chapter->course->trainer_id !== Auth::id()) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        $quiz->delete();

        return response()->json([
            'message' => __('messages.quiz_deleted'),
        ]);
    }
}
