<?php

namespace App\Http\Controllers\Api\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Course;
use App\Http\Requests\Trainer\StoreChapterRequest;
use App\Http\Requests\Trainer\UpdateChapterRequest;
use App\Http\Resources\ChapterResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChapterController extends Controller
{
    /**
     * Display chapters for the authenticated trainer's courses.
     * GET /api/trainer/chapters
     */
    public function index()
    {
        $trainerId = Auth::id();
        
        $chapters = Chapter::whereHas('course', function ($q) use ($trainerId) {
                $q->where('trainer_id', $trainerId);
            })
            ->with(['course', 'videos'])
            ->orderBy('order')
            ->get();

        return response()->json([
            'chapters' => ChapterResource::collection($chapters),
        ]);
    }

    /**
     * Store a new chapter.
     * POST /api/trainer/chapters
     */
    public function store(StoreChapterRequest $request)
    {
        $validated = $request->validated();
        $course = Course::where('trainer_id', Auth::id())
            ->findOrFail($validated['course_id']);

        // Get the next order number
        $maxOrder = Chapter::where('course_id', $course->id)->max('order') ?? 0;
        
        $chapter = Chapter::create([
            'course_id' => $course->id,
            'title_en' => $validated['title_en'],
            'title_ar' => $validated['title_ar'] ?? null,
            'description_en' => $validated['description_en'] ?? null,
            'description_ar' => $validated['description_ar'] ?? null,
            'order' => $maxOrder + 1,
        ]);

        return response()->json([
            'message' => __('messages.chapter_created'),
            'chapter' => new ChapterResource($chapter->fresh(['course', 'videos'])),
        ], 201);
    }

    /**
     * Display the specified chapter.
     * GET /api/trainer/chapters/{chapter}
     */
    public function show(Chapter $chapter)
    {
        // Ensure trainer owns the chapter's course
        if ($chapter->course->trainer_id !== Auth::id()) {
            return response()->json([
                'message' => __('messages.unauthorized'),
            ], 403);
        }

        $chapter->load(['course', 'videos']);

        return response()->json([
            'chapter' => new ChapterResource($chapter),
        ]);
    }

    /**
     * Update the specified chapter.
     * PUT /api/trainer/chapters/{chapter}
     */
    public function update(UpdateChapterRequest $request, Chapter $chapter)
    {
        // Ensure trainer owns the chapter's course
        if ($chapter->course->trainer_id !== Auth::id()) {
            return response()->json([
                'message' => __('messages.unauthorized'),
            ], 403);
        }

        $validated = $request->validated();
        $chapter->update($validated);

        return response()->json([
            'message' => __('messages.chapter_updated'),
            'chapter' => new ChapterResource($chapter->fresh(['course', 'videos'])),
        ]);
    }

    /**
     * Remove the specified chapter.
     * DELETE /api/trainer/chapters/{chapter}
     */
    public function destroy(Chapter $chapter)
    {
        // Ensure trainer owns the chapter's course
        if ($chapter->course->trainer_id !== Auth::id()) {
            return response()->json([
                'message' => __('messages.unauthorized'),
            ], 403);
        }

        $chapter->delete();

        return response()->json([
            'message' => __('messages.chapter_deleted'),
        ]);
    }
}
