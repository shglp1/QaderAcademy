<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Overview analytics dashboard data
     */
    public function overview()
    {
        $totalEnrollments = Enrollment::count();
        $totalRevenue = Payment::where('status', 'completed')->sum('amount');
        $totalCourses = Course::where('status', 'published')->count();
        $totalStudents = User::where('role', 'student')->count();
        $totalTrainers = User::where('role', 'trainer')->whereHas('trainerProfile', function ($q) {
            $q->where('approval_status', 'approved');
        })->count();

        $completionRate = Enrollment::whereNotNull('completed_at')
            ->count() / max(1, Enrollment::count()) * 100;

        return response()->json([
            'total_enrollments' => $totalEnrollments,
            'total_revenue' => $totalRevenue,
            'total_courses' => $totalCourses,
            'total_students' => $totalStudents,
            'total_trainers' => $totalTrainers,
            'completion_rate' => round($completionRate, 2)
        ]);
    }

    /**
     * Revenue analytics over time
     */
    public function revenue()
    {
        $revenue = Payment::where('status', 'completed')
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        return response()->json(['revenue' => $revenue]);
    }

    /**
     * Enrollment trends over time
     */
    public function enrollments()
    {
        $enrollments = Enrollment::selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        return response()->json(['enrollments' => $enrollments]);
    }

    /**
     * Top courses by enrollment count
     */
    public function topCourses()
    {
        $courses = Course::withCount('enrollments')
            ->where('status', 'published')
            ->orderBy('enrollments_count', 'desc')
            ->limit(10)
            ->get();

        return response()->json(['courses' => $courses]);
    }

    /**
     * Top trainers by enrollment count across their courses
     */
    public function topTrainers()
    {
        $trainers = User::where('role', 'trainer')
            ->with(['trainerProfile'])
            ->withCount(['courses' => function ($query) {
                $query->where('status', 'published');
            }])
            ->with(['courses' => function ($query) {
                $query->select('id', 'trainer_id')
                    ->withCount('enrollments');
            }])
            ->get()
            ->map(function ($trainer) {
                $totalEnrollments = $trainer->courses->sum('enrollments_count');
                return [
                    'id' => $trainer->id,
                    'name' => $trainer->name,
                    'email' => $trainer->email,
                    'profile' => $trainer->trainerProfile,
                    'courses_count' => $trainer->courses_count,
                    'total_enrollments' => $totalEnrollments
                ];
            })
            ->sortByDesc('total_enrollments')
            ->take(10)
            ->values();

        return response()->json(['trainers' => $trainers]);
    }
}
