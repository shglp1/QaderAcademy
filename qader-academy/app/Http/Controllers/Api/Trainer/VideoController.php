<?php

namespace App\Http\Controllers\Api\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\Chapter;
use App\Http\Requests\Trainer\StoreVideoRequest;
use App\Http\Resources\VideoResource;
use Illuminate\Support\Facades\Auth;

class VideoController extends Controller
{
    public function index()
    {
        $trainerId = Auth::id();
        
        $videos = Video::whereHas('chapter.course', function ($q) use ($trainerId) {
                $q->where('trainer_id', $trainerId);
            })
            ->with(['chapter.course'])
            ->orderBy('order')
            ->get();

        return response()->json([
            'videos' => VideoResource::collection($videos),
        ]);
    }

    public function store(StoreVideoRequest $request)
    {
        $validated = $request->validated();
        $chapter = Chapter::whereHas('course', function ($q) {
                $q->where('trainer_id', Auth::id());
            })
            ->findOrFail($validated['chapter_id']);

        $maxOrder = Video::where('chapter_id', $chapter->id)->max('order') ?? 0;
        
        $video = Video::create([
            'chapter_id' => $chapter->id,
            'title_en' => $validated['title_en'],
            'title_ar' => $validated['title_ar'] ?? $validated['title_en'],
            'video_url' => $validated['video_url'],
            'duration_seconds' => $validated['duration_seconds'] ?? (($validated['duration'] ?? 0) * 60),
            'order' => $validated['order'] ?? ($maxOrder + 1),
            'is_intro_video' => $validated['is_intro'] ?? false,
        ]);

        return response()->json([
            'message' => __('messages.video_created'),
            'video' => new VideoResource($video->fresh(['chapter.course'])),
        ], 201);
    }

    public function show(Video $video)
    {
        if ($video->chapter->course->trainer_id !== Auth::id()) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        $video->load(['chapter.course']);

        return response()->json([
            'video' => new VideoResource($video),
        ]);
    }

    public function update(StoreVideoRequest $request, Video $video)
    {
        if ($video->chapter->course->trainer_id !== Auth::id()) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        $validated = $request->validated();
        $video->update([
            'title_en' => $validated['title_en'],
            'title_ar' => $validated['title_ar'] ?? $validated['title_en'],
            'video_url' => $validated['video_url'],
            'duration_seconds' => $validated['duration_seconds'] ?? (($validated['duration'] ?? 0) * 60),
            'order' => $validated['order'] ?? $video->order,
            'is_intro_video' => $validated['is_intro'] ?? $video->is_intro_video,
        ]);

        return response()->json([
            'message' => __('messages.video_updated'),
            'video' => new VideoResource($video->fresh(['chapter.course'])),
        ]);
    }

    public function destroy(Video $video)
    {
        if ($video->chapter->course->trainer_id !== Auth::id()) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        $video->delete();

        return response()->json([
            'message' => __('messages.video_deleted'),
        ]);
    }
}
