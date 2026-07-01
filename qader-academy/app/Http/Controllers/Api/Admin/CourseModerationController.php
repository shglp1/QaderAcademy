<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Notifications\CourseModerationNotification;
use Illuminate\Http\Request;

class CourseModerationController extends Controller
{
    /**
     * List all pending courses awaiting approval
     */
    public function pendingCourses()
    {
        $courses = Course::where('status', 'pending')
            ->with(['trainer.trainerProfile', 'category'])
            ->paginate(20);

        return CourseResource::collection($courses);
    }

    /**
     * Approve a course and publish it
     */
    public function approveCourse(Course $course)
    {
        $this->authorize('update', $course);

        $course->update(['status' => 'published']);

        // Notify the trainer
        $course->trainer->notify(
            new CourseModerationNotification('approved', 'Your course "' . $course->title . '" has been approved and is now published.')
        );

        return response()->json(['message' => 'Course approved and published successfully']);
    }

    /**
     * Reject a course with a required reason
     */
    public function rejectCourse(Course $course, Request $request)
    {
        $this->authorize('update', $course);

        $request->validate([
            'reason' => 'required|string|max:1000'
        ]);

        $course->update(['status' => 'rejected']);

        // Notify the trainer with the rejection reason
        $course->trainer->notify(
            new CourseModerationNotification('rejected', 'Your course "' . $course->title . '" was rejected. Reason: ' . $request->reason)
        );

        return response()->json(['message' => 'Course rejected']);
    }

    /**
     * Update a course (admin can edit any course)
     */
    public function update(Request $request, Course $course)
    {
        $this->authorize('update', $course);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric|min:0',
            'category_id' => 'sometimes|required|exists:categories,id'
        ]);

        $course->update($validated);

        return new CourseResource($course);
    }
}
