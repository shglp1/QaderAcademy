<?php

namespace App\Http\Controllers\Api\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Http\Requests\Trainer\StoreCourseRequest;
use App\Http\Requests\Trainer\UpdateCourseRequest;
use App\Http\Resources\CourseResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    /**
     * Display a listing of the trainer's courses.
     */
    public function index()
    {
        $courses = Course::where('trainer_id', Auth::id())
            ->with(['category', 'trainer'])
            ->withCount(['chapters', 'enrollments'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'courses' => CourseResource::collection($courses),
        ]);
    }

    /**
     * Store a newly created course.
     */
    public function store(StoreCourseRequest $request)
    {
        $validated = $request->validated();
        
        $course = Course::create([
            'trainer_id' => Auth::id(),
            'title' => $validated['title'],
            'title_ar' => $validated['title_ar'] ?? null,
            'description' => $validated['description'],
            'description_ar' => $validated['description_ar'] ?? null,
            'category_id' => $validated['category_id'],
            'price' => $validated['price'],
            'duration' => $validated['duration'] ?? null,
            'status' => 'draft',
        ]);

        return response()->json([
            'message' => __('messages.course_created'),
            'course' => new CourseResource($course->load(['category', 'trainer'])),
        ], 201);
    }

    /**
     * Display the specified course.
     */
    public function show(Course $course)
    {
        $this->authorize('view', $course);
        
        return response()->json([
            'course' => new CourseResource($course->load(['category', 'trainer', 'chapters.videos', 'ratings'])),
        ]);
    }

    /**
     * Update the specified course.
     */
    public function update(UpdateCourseRequest $request, Course $course)
    {
        $this->authorize('update', $course);
        
        $validated = $request->validated();
        
        $course->update([
            'title' => $validated['title'] ?? $course->title,
            'title_ar' => $validated['title_ar'] ?? $course->title_ar,
            'description' => $validated['description'] ?? $course->description,
            'description_ar' => $validated['description_ar'] ?? $course->description_ar,
            'category_id' => $validated['category_id'] ?? $course->category_id,
            'price' => $validated['price'] ?? $course->price,
            'duration' => $validated['duration'] ?? $course->duration,
        ]);

        return response()->json([
            'message' => __('messages.course_updated'),
            'course' => new CourseResource($course->fresh(['category', 'trainer'])),
        ]);
    }

    /**
     * Remove the specified course.
     */
    public function destroy(Course $course)
    {
        $this->authorize('delete', $course);
        
        $course->delete();

        return response()->json([
            'message' => __('messages.course_deleted'),
        ]);
    }

    /**
     * Submit course for admin approval.
     */
    public function submitForApproval(Course $course)
    {
        $this->authorize('update', $course);
        
        if ($course->status === 'published') {
            return response()->json([
                'message' => __('messages.course_already_published'),
            ], 422);
        }

        $course->update(['status' => 'pending']);

        // Notify admins about pending course
        $admins = \App\Models\User::role('admin')->orWhere('role', 'super_admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\CoursePendingReview($course));
        }

        return response()->json([
            'message' => __('messages.course_submitted_for_approval'),
            'course' => new CourseResource($course->fresh(['category', 'trainer'])),
        ]);
    }

    /**
     * Get trainer earnings summary.
     */
    public function earnings()
    {
        $trainerId = Auth::id();
        
        $earnings = \DB::table('trainer_earnings')
            ->where('trainer_id', $trainerId)
            ->selectRaw('SUM(amount) as total_earned, SUM(paid_amount) as total_paid, SUM(pending_amount) as pending')
            ->first();
        
        $payoutRequests = \App\Models\PayoutRequest::where('trainer_id', $trainerId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'summary' => [
                'total_earned' => $earnings->total_earned ?? 0,
                'total_paid' => $earnings->total_paid ?? 0,
                'pending' => $earnings->pending ?? 0,
            ],
            'recent_payouts' => $payoutRequests,
        ]);
    }
}
