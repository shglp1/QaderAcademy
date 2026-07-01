<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Rating;
use App\Models\Certificate;
use App\Http\Resources\CourseResource;
use App\Http\Resources\CertificateResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    /**
     * Display a listing of courses with filters.
     * GET /api/student/courses
     */
    public function index(Request $request)
    {
        $query = Course::with(['trainer.trainerProfile', 'category', 'chapters'])
            ->where('status', 'published');

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by year
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        // Filter by semester
        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }

        // Filter by price range
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Filter by minimum rating
        if ($request->filled('min_rating')) {
            $query->where('rating_average', '>=', $request->min_rating);
        }

        // Sort options
        $sortBy = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        $validSorts = ['created_at', 'price', 'rating_average', 'title_en', 'title_ar'];
        
        if (in_array($sortBy, $validSorts)) {
            $query->orderBy($sortBy, $direction);
        }

        $courses = $query->paginate($request->get('per_page', 15));

        return CourseResource::collection($courses);
    }

    /**
     * Display course details.
     * GET /api/student/courses/{course}
     */
    public function show(Course $course)
    {
        $course->load([
            'trainer.trainerProfile',
            'category',
            'chapters.videos',
            'attachments',
            'ratings.student'
        ]);

        // Check if student is enrolled
        $isEnrolled = false;
        $enrollment = null;
        
        if (Auth::check() && Auth::user()->isStudent()) {
            $enrollment = $course->enrollments()
                ->where('student_id', Auth::id())
                ->first();
            $isEnrolled = $enrollment !== null;
        }

        // If not enrolled, only show intro video and limited content
        if (!$isEnrolled) {
            $course->makeHidden(['chapters']);
        }

        return response()->json([
            'course' => new CourseResource($course),
            'is_enrolled' => $isEnrolled,
            'enrollment' => $enrollment ? [
                'id' => $enrollment->id,
                'progress_percentage' => $enrollment->progress_percentage,
                'status' => $enrollment->status,
            ] : null,
        ]);
    }

    /**
     * Search courses by keyword (bilingual).
     * GET /api/student/courses/search
     */
    public function search(Request $request)
    {
        $keyword = $request->get('q', '');
        
        if (empty($keyword)) {
            return CourseResource::collection(Course::where('status', 'published')->paginate(15));
        }

        $query = Course::with(['trainer.trainerProfile', 'category'])
            ->where('status', 'published')
            ->where(function ($q) use ($keyword) {
                $q->where('title_en', 'LIKE', "%{$keyword}%")
                  ->orWhere('title_ar', 'LIKE', "%{$keyword}%")
                  ->orWhere('description_en', 'LIKE', "%{$keyword}%")
                  ->orWhere('description_ar', 'LIKE', "%{$keyword}%");
            });

        // Also search in trainer names
        $trainerIds = \App\Models\User::where('role', 'trainer')
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'LIKE', "%{$keyword}%");
            })
            ->pluck('id');
            
        if ($trainerIds->isNotEmpty()) {
            $query->orWhereIn('trainer_id', $trainerIds);
        }

        $courses = $query->paginate($request->get('per_page', 15));

        return CourseResource::collection($courses);
    }

    /**
     * Get student's certificates.
     * GET /api/student/certificates
     */
    public function myCertificates()
    {
        $studentId = Auth::id();
        
        $certificates = Certificate::with(['enrollment.course', 'enrollment.student'])
            ->whereHas('enrollment', function ($q) use ($studentId) {
                $q->where('student_id', $studentId);
            })
            ->orderBy('issued_at', 'desc')
            ->get();

        return response()->json([
            'certificates' => CertificateResource::collection($certificates),
        ]);
    }

    /**
     * Submit a course rating.
     * POST /api/student/ratings
     */
    public function submitRating(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $courseId = $validated['course_id'];
        $studentId = Auth::id();

        // Check if student has completed or is enrolled in the course
        $enrollment = \App\Models\Enrollment::where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->first();

        if (!$enrollment) {
            return response()->json([
                'message' => __('messages.rating_enrollment_required'),
            ], 403);
        }

        // Create or update rating
        $rating = Rating::updateOrCreate(
            ['student_id' => $studentId, 'course_id' => $courseId],
            [
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]
        );

        // Recalculate course average rating
        $averageRating = Rating::where('course_id', $courseId)
            ->avg('rating');
        $ratingCount = Rating::where('course_id', $courseId)->count();

        $course = Course::findOrFail($courseId);
        $course->update([
            'rating_average' => round($averageRating, 2),
            'rating_count' => $ratingCount,
        ]);

        return response()->json([
            'message' => __('messages.rating_submitted'),
            'rating' => $rating,
            'course_rating_average' => round($averageRating, 2),
            'course_rating_count' => $ratingCount,
        ]);
    }
}
