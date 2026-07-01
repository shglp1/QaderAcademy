<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Student\CourseController as StudentCourseController;
use App\Http\Controllers\Api\Student\EnrollmentController;
use App\Http\Controllers\Api\Student\QuizController;
use App\Http\Controllers\Api\Trainer\CourseController as TrainerCourseController;
use App\Http\Controllers\Api\Trainer\GradingController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Admin\CourseModerationController;
use App\Http\Controllers\Api\Admin\AnalyticsController;

Route::get('/', function () {
    return response()->json([
        'app' => 'QaderAcademy API',
        'version' => '1.0.0',
        'status' => 'active'
    ]);
});

// ==================== Public Auth Routes ====================
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('password/reset', [AuthController::class, 'sendResetLink']);
    Route::post('password/reset-token', [AuthController::class, 'resetPassword']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::get('user', [AuthController::class, 'me']);
});

Route::get('categories', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'index']);

Route::prefix('student')->group(function () {
    Route::get('courses', [StudentCourseController::class, 'index']);
    Route::get('courses/search', [StudentCourseController::class, 'search']);
    Route::get('courses/{course}', [StudentCourseController::class, 'show']);
});

// ==================== Student Routes ====================
Route::middleware(['auth:sanctum', 'role:student'])->prefix('student')->group(function () {
    // Enrollment & Payment
    Route::post('enrollments', [EnrollmentController::class, 'store']);
    Route::get('enrollments', [EnrollmentController::class, 'myEnrollments']);
    Route::get('enrollments/{enrollment}', [EnrollmentController::class, 'show']);
    
    // Quiz & Exam Attempts
    Route::get('quizzes/{quiz}', [QuizController::class, 'show']);
    Route::post('quiz-attempts', [QuizController::class, 'submitQuiz']);
    Route::post('final-exam-attempts', [QuizController::class, 'submitFinalExam']);
    
    // Video Progress Tracking
    Route::post('videos/{video}/progress', [\App\Http\Controllers\Api\Student\VideoProgressController::class, 'markComplete']);
    Route::get('videos/{video}/progress', [\App\Http\Controllers\Api\Student\VideoProgressController::class, 'showProgress']);
    
    // Certificates
    Route::get('certificates', [StudentCourseController::class, 'myCertificates']);
    Route::get('certificates/{certificate}/download', [\App\Http\Controllers\Api\CertificateController::class, 'download'])
        ->name('student.certificates.download');
    
    // Ratings
    Route::post('ratings', [StudentCourseController::class, 'submitRating']);
    
    // Q&A
    Route::apiResource('qa-threads', \App\Http\Controllers\Api\Student\QAController::class)->only(['index', 'store', 'show']);
});

// ==================== Trainer Routes ====================
Route::middleware(['auth:sanctum', 'role:trainer'])->prefix('trainer')->group(function () {
    // Course Management
    Route::apiResource('courses', TrainerCourseController::class);
    Route::post('courses/{course}/submit-for-approval', [TrainerCourseController::class, 'submitForApproval']);
    
    // Chapter & Video Management
    Route::apiResource('chapters', \App\Http\Controllers\Api\Trainer\ChapterController::class);
    Route::apiResource('videos', \App\Http\Controllers\Api\Trainer\VideoController::class);
    
    // Quiz Management
    Route::apiResource('quizzes', \App\Http\Controllers\Api\Trainer\QuizController::class);
    Route::apiResource('quiz-questions', \App\Http\Controllers\Api\Trainer\QuizQuestionController::class);
    
    // Final Exam Management
    Route::apiResource('final-exams', \App\Http\Controllers\Api\Trainer\FinalExamController::class);
    Route::apiResource('final-exam-questions', \App\Http\Controllers\Api\Trainer\FinalExamQuestionController::class);
    Route::get('categories', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'index']);
    
    // Grading
    Route::get('grading-queue', [GradingController::class, 'queue']);
    Route::post('grading/{attempt}/grade', [GradingController::class, 'grade']);
    Route::post('grade/{attempt}', [GradingController::class, 'grade']);
    
    // Attachments
    Route::apiResource('attachments', \App\Http\Controllers\Api\Trainer\AttachmentController::class);
    
    // Earnings & Payouts
    Route::get('earnings', [TrainerCourseController::class, 'earnings']);
    Route::apiResource('payout-requests', \App\Http\Controllers\Api\Trainer\PayoutRequestController::class)->only(['index', 'store', 'show']);
    
    // Student Q&A
    Route::get('qa-threads', [GradingController::class, 'qaThreads']);
    Route::post('qa-threads/{thread}/answer', [GradingController::class, 'answerQA']);
});

// ==================== Admin Routes ====================
Route::middleware(['auth:sanctum', 'role:admin,super_admin'])->prefix('admin')->group(function () {
    // User Management
    Route::get('trainers', [UserManagementController::class, 'trainers']);
    Route::get('trainers/{user}', [UserManagementController::class, 'trainerDetail']);
    Route::post('trainers/{user}/approve', [UserManagementController::class, 'approveTrainer']);
    Route::post('trainers/{user}/reject', [UserManagementController::class, 'rejectTrainer']);
    Route::post('trainers/{user}/suspend', [UserManagementController::class, 'suspendUser']);
    
    Route::get('students', [UserManagementController::class, 'students']);
    Route::post('students/{user}/suspend', [UserManagementController::class, 'suspendUser']);
    
    // Course Moderation
    Route::get('courses/pending', [CourseModerationController::class, 'pendingCourses']);
    Route::post('courses/{course}/approve', [CourseModerationController::class, 'approveCourse']);
    Route::post('courses/{course}/reject', [CourseModerationController::class, 'rejectCourse']);
    Route::put('courses/{course}', [CourseModerationController::class, 'update']);
    
    // Analytics
    Route::get('analytics/overview', [AnalyticsController::class, 'overview']);
    Route::get('analytics/revenue', [AnalyticsController::class, 'revenue']);
    Route::get('analytics/enrollments', [AnalyticsController::class, 'enrollments']);
    Route::get('analytics/top-courses', [AnalyticsController::class, 'topCourses']);
    
    // Financial Management
    Route::get('payout-requests', [\App\Http\Controllers\Api\Admin\PayoutController::class, 'index']);
    Route::post('payout-requests/{request}/approve', [\App\Http\Controllers\Api\Admin\PayoutController::class, 'approve']);
    Route::post('payout-requests/{request}/reject', [\App\Http\Controllers\Api\Admin\PayoutController::class, 'reject']);
    
    // Settings
    Route::get('settings', [\App\Http\Controllers\Api\Admin\SettingController::class, 'index']);
    Route::put('settings/{key}', [\App\Http\Controllers\Api\Admin\SettingController::class, 'update']);
    
    // Categories
    Route::apiResource('categories', \App\Http\Controllers\Api\Admin\CategoryController::class);
    
    // Notifications
    Route::post('notifications/broadcast', [\App\Http\Controllers\Api\Admin\NotificationController::class, 'broadcast']);
});

// ==================== Public Certificate Verification ====================
Route::get('verify-certificate/{certificateNumber}', [\App\Http\Controllers\Api\CertificateController::class, 'verify']);

// ==================== Payment Webhook (MyFatoorah) ====================
Route::post('webhooks/payment', [\App\Http\Controllers\Api\PaymentWebhookController::class, 'handle'])->name('webhooks.payment');
