<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\StudentProfile;
use App\Models\TrainerProfile;
use App\Models\Category;
use App\Models\Course;
use App\Models\Chapter;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\QuizAttempt;
use App\Models\QuizAnswer;
use App\Models\Certificate;
use App\Models\Video;
use App\Models\VideoCompletion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Scout\EngineManager;
use Tests\TestCase;

class QaderAcademyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        Category::create([
            'name_en' => 'University Courses',
            'name_ar' => 'كورسات جامعية',
            'type' => 'university'
        ]);
    }

    public function test_student_registration(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test Student',
            'email' => 'student@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'student',
            'age' => 22,
            'birth_date' => '2002-01-15',
            'university' => 'Cairo University',
            'graduation_status' => 'not_graduated',
            'city' => 'Cairo'
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('user.role', 'student');
        
        $this->assertDatabaseHas('users', ['email' => 'student@example.com', 'role' => 'student']);
    }

    public function test_trainer_registration_pending(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test Trainer',
            'email' => 'trainer@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'trainer',
            'bio' => 'Experienced instructor',
            'specialization' => 'Programming'
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('trainer_profiles', ['approval_status' => 'pending']);
    }

    public function test_admin_approves_trainer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainer = User::factory()->create(['role' => 'trainer']);
        TrainerProfile::create(['user_id' => $trainer->id, 'bio' => 'Bio', 'specialization' => 'Spec', 'approval_status' => 'pending']);

        $response = $this->actingAs($admin)->postJson("/api/admin/trainers/{$trainer->id}/approve");
        $response->assertStatus(200);
        $this->assertDatabaseHas('trainer_profiles', ['user_id' => $trainer->id, 'approval_status' => 'approved']);
    }

    public function test_login_all_roles(): void
    {
        $student = User::factory()->create(['role' => 'student', 'password' => bcrypt('password')]);
        StudentProfile::create(['user_id' => $student->id, 'university' => 'Test', 'city' => 'Cairo', 'age' => 20, 'graduation_status' => 'not_graduated']);
        $this->postJson('/api/auth/login', ['email' => $student->email, 'password' => 'password'])->assertStatus(200);

        $trainer = User::factory()->create(['role' => 'trainer', 'password' => bcrypt('password')]);
        TrainerProfile::create(['user_id' => $trainer->id, 'bio' => 'Bio', 'specialization' => 'Spec', 'approval_status' => 'approved']);
        $this->postJson('/api/auth/login', ['email' => $trainer->email, 'password' => 'password'])->assertStatus(200);

        $admin = User::factory()->create(['role' => 'admin', 'password' => bcrypt('password')]);
        $this->postJson('/api/auth/login', ['email' => $admin->email, 'password' => 'password'])->assertStatus(200);
    }

    public function test_enrollment_payment_webhook_flow(): void
    {
        $student = User::factory()->create(['role' => 'student', 'password' => bcrypt('password')]);
        StudentProfile::create(['user_id' => $student->id, 'university' => 'Test', 'city' => 'Cairo', 'age' => 20, 'graduation_status' => 'not_graduated']);
        
        $trainer = User::factory()->create(['role' => 'trainer']);
        TrainerProfile::create(['user_id' => $trainer->id, 'bio' => 'Bio', 'specialization' => 'Spec', 'approval_status' => 'approved']);
        
        $course = Course::create([
            'trainer_id' => $trainer->id,
            'category_id' => Category::first()->id,
            'title_en' => 'Test Course',
            'title_ar' => 'كورس تجريبي',
            'description_en' => 'Description',
            'description_ar' => 'وصف',
            'price' => 100,
            'status' => 'published'
        ]);

        $response = $this->actingAs($student)->postJson('/api/student/enrollments', ['course_id' => $course->id]);
        $response->assertStatus(201);
        $enrollment = Enrollment::find($response->json('enrollment.id'));
        $this->assertEquals('pending_payment', $enrollment->status);

        $payment = Payment::where('enrollment_id', $enrollment->id)->first();
        $payment->update(['gateway_reference' => 'INV-123']);
        
        $this->postJson('/api/webhooks/payment', [
            'InvoiceId' => 'INV-123',
            'PaymentStatus' => 'Success'
        ])->assertStatus(200);

        $enrollment->refresh();
        $this->assertEquals('active', $enrollment->status);
    }

    public function test_myfatoorah_webhook_uses_invoice_id_and_payment_status(): void
    {
        $student = User::factory()->create(['role' => 'student', 'password' => bcrypt('password')]);
        StudentProfile::create(['user_id' => $student->id, 'university' => 'Test', 'city' => 'Cairo', 'age' => 20, 'graduation_status' => 'not_graduated']);
        
        $trainer = User::factory()->create(['role' => 'trainer']);
        TrainerProfile::create(['user_id' => $trainer->id, 'bio' => 'Bio', 'specialization' => 'Spec', 'approval_status' => 'approved']);
        
        $course = Course::create([
            'trainer_id' => $trainer->id,
            'category_id' => Category::first()->id,
            'title_en' => 'Test Course',
            'title_ar' => 'كورس تجريبي',
            'description_en' => 'Description',
            'description_ar' => 'وصف',
            'price' => 100,
            'status' => 'published'
        ]);

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'pending_payment'
        ]);
        
        $payment = Payment::create([
            'student_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'amount' => 100,
            'currency' => 'SAR',
            'status' => 'pending',
            'payment_method' => 'myfatoorah',
            'gateway_reference' => 'INV-456'
        ]);

        // Test successful payment activates enrollment
        $response = $this->postJson('/api/webhooks/payment', [
            'InvoiceId' => 'INV-456',
            'PaymentStatus' => 'Success'
        ]);
        $response->assertStatus(200);
        $enrollment->refresh();
        $this->assertEquals('active', $enrollment->status);

        // Test failed payment does not activate enrollment
        $enrollment2 = Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'pending_payment'
        ]);
        $payment2 = Payment::create([
            'student_id' => $student->id,
            'enrollment_id' => $enrollment2->id,
            'amount' => 100,
            'currency' => 'SAR',
            'status' => 'pending',
            'payment_method' => 'myfatoorah',
            'gateway_reference' => 'INV-789'
        ]);

        $response = $this->postJson('/api/webhooks/payment', [
            'InvoiceId' => 'INV-789',
            'PaymentStatus' => 'Failed'
        ]);
        $response->assertStatus(200);
        $enrollment2->refresh();
        $this->assertEquals('pending_payment', $enrollment2->status);
    }

    public function test_route_webhooks_payment_resolves(): void
    {
        $route = route('webhooks.payment');
        $this->assertEquals(url('/api/webhooks/payment'), $route);
    }

    public function test_quiz_mcq_auto_grade(): void
    {
        $student = User::factory()->create(['role' => 'student', 'password' => bcrypt('password')]);
        StudentProfile::create(['user_id' => $student->id, 'university' => 'Test', 'city' => 'Cairo', 'age' => 20, 'graduation_status' => 'not_graduated']);
        
        $trainer = User::factory()->create(['role' => 'trainer']);
        TrainerProfile::create(['user_id' => $trainer->id, 'bio' => 'Bio', 'specialization' => 'Spec', 'approval_status' => 'approved']);
        
        $course = Course::create([
            'trainer_id' => $trainer->id,
            'category_id' => Category::first()->id,
            'title_en' => 'Test Course',
            'title_ar' => 'كورس تجريبي',
            'description_en' => 'Desc',
            'description_ar' => 'وصف',
            'price' => 100,
            'status' => 'published'
        ]);
        
        $chapter = Chapter::create(['course_id' => $course->id, 'title_en' => 'Chapter 1', 'title_ar' => 'فصل 1', 'order' => 1]);
        $quiz = Quiz::create(['chapter_id' => $chapter->id, 'title_en' => 'Quiz 1', 'title_ar' => 'اختبار']);
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'type' => 'mcq',
            'question_en' => 'What is 2+2?',
            'question_ar' => 'كم 2+2؟',
            'correct_answer_en' => '4',
            'correct_answer_ar' => '4',
            'points' => 10
        ]);
        
        Enrollment::create(['student_id' => $student->id, 'course_id' => $course->id, 'status' => 'active']);

        $response = $this->actingAs($student)->postJson('/api/student/quiz-attempts', [
            'quiz_id' => $quiz->id,
            'answers' => [$question->id => '4']
        ]);

        $response->assertStatus(201);
        $attempt = QuizAttempt::find($response->json('attempt.id'));
        $this->assertEquals('graded', $attempt->status);
        $this->assertEquals(10, $attempt->score);
    }

    public function test_quiz_written_hidden_until_graded(): void
    {
        $student = User::factory()->create(['role' => 'student', 'password' => bcrypt('password')]);
        StudentProfile::create(['user_id' => $student->id, 'university' => 'Test', 'city' => 'Cairo', 'age' => 20, 'graduation_status' => 'not_graduated']);
        
        $trainer = User::factory()->create(['role' => 'trainer']);
        TrainerProfile::create(['user_id' => $trainer->id, 'bio' => 'Bio', 'specialization' => 'Spec', 'approval_status' => 'approved']);
        
        $course = Course::create([
            'trainer_id' => $trainer->id,
            'category_id' => Category::first()->id,
            'title_en' => 'Test Course',
            'title_ar' => 'كورس تجريبي',
            'description_en' => 'Desc',
            'description_ar' => 'وصف',
            'price' => 100,
            'status' => 'published'
        ]);
        
        $chapter = Chapter::create(['course_id' => $course->id, 'title_en' => 'Chapter 1', 'title_ar' => 'فصل 1', 'order' => 1]);
        $quiz = Quiz::create(['chapter_id' => $chapter->id, 'title_en' => 'Quiz 1', 'title_ar' => 'اختبار']);
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'type' => 'written',
            'question_en' => 'Explain OOP',
            'question_ar' => 'اشرح البرمجة كائنية التوجه',
            'correct_answer_en' => 'Object Oriented Programming',
            'correct_answer_ar' => 'البرمجة كائنية التوجه',
            'points' => 20
        ]);
        
        Enrollment::create(['student_id' => $student->id, 'course_id' => $course->id, 'status' => 'active']);

        $response = $this->actingAs($student)->postJson('/api/student/quiz-attempts', [
            'quiz_id' => $quiz->id,
            'answers' => [$question->id => 'My answer']
        ]);

        $response->assertStatus(201);
        $attempt = QuizAttempt::find($response->json('attempt.id'));
        $this->assertEquals('pending_review', $attempt->status);
        $this->assertNull($attempt->score);
    }

    public function test_trainer_grades_written(): void
    {
        Notification::fake();
        
        $student = User::factory()->create(['role' => 'student', 'password' => bcrypt('password')]);
        StudentProfile::create(['user_id' => $student->id, 'university' => 'Test', 'city' => 'Cairo', 'age' => 20, 'graduation_status' => 'not_graduated']);
        
        $trainer = User::factory()->create(['role' => 'trainer']);
        TrainerProfile::create(['user_id' => $trainer->id, 'bio' => 'Bio', 'specialization' => 'Spec', 'approval_status' => 'approved']);
        
        $course = Course::create([
            'trainer_id' => $trainer->id,
            'category_id' => Category::first()->id,
            'title_en' => 'Test Course',
            'title_ar' => 'كورس تجريبي',
            'description_en' => 'Desc',
            'description_ar' => 'وصف',
            'price' => 100,
            'status' => 'published'
        ]);
        
        $chapter = Chapter::create(['course_id' => $course->id, 'title_en' => 'Chapter 1', 'title_ar' => 'فصل 1', 'order' => 1]);
        $quiz = Quiz::create(['chapter_id' => $chapter->id, 'title_en' => 'Quiz 1', 'title_ar' => 'اختبار']);
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'type' => 'written',
            'question_en' => 'Explain OOP',
            'question_ar' => 'اشرح',
            'correct_answer_en' => 'OOP principles',
            'correct_answer_ar' => 'مبادئ',
            'points' => 20
        ]);
        
        $enrollment = Enrollment::create(['student_id' => $student->id, 'course_id' => $course->id, 'status' => 'active', 'progress_percentage' => 5]);
        $attempt = QuizAttempt::create(['quiz_id' => $quiz->id, 'student_id' => $student->id, 'status' => 'pending_review']);
        QuizAnswer::create(['attempt_id' => $attempt->id, 'question_id' => $question->id, 'student_answer' => 'My answer']);

        $response = $this->actingAs($trainer)->postJson("/api/trainer/grading/{$attempt->id}/grade", [
            'score' => 18,
            'feedback' => 'Good!'
        ]);

        $response->assertStatus(200);
        $attempt->refresh();
        $this->assertEquals('graded', $attempt->status);
        $this->assertEquals(18, $attempt->score);
        
        Notification::assertSentTo($student, \App\Notifications\GradePosted::class);
    }

    public function test_admin_course_moderation(): void
    {
        Notification::fake();
        
        $admin = User::factory()->create(['role' => 'admin']);
        $trainer = User::factory()->create(['role' => 'trainer']);
        TrainerProfile::create(['user_id' => $trainer->id, 'bio' => 'Bio', 'specialization' => 'Spec', 'approval_status' => 'approved']);
        
        $course = Course::create([
            'trainer_id' => $trainer->id,
            'category_id' => Category::first()->id,
            'title_en' => 'Pending Course',
            'title_ar' => 'كورس معلق',
            'description_en' => 'Desc',
            'description_ar' => 'وصف',
            'price' => 100,
            'status' => 'pending'
        ]);

        // Approve
        $this->actingAs($admin)->postJson("/api/admin/courses/{$course->id}/approve")->assertStatus(200);
        $course->refresh();
        $this->assertEquals('published', $course->status);
        Notification::assertSentTo($trainer, \App\Notifications\CourseApproved::class);

        // Create another for reject test
        $course2 = Course::create([
            'trainer_id' => $trainer->id,
            'category_id' => Category::first()->id,
            'title_en' => 'Pending Course 2',
            'title_ar' => 'كورس معلق 2',
            'description_en' => 'Desc',
            'description_ar' => 'وصف',
            'price' => 100,
            'status' => 'pending'
        ]);

        $this->actingAs($admin)->postJson("/api/admin/courses/{$course2->id}/reject", ['reason' => 'Quality issues'])
            ->assertStatus(200);
        $course2->refresh();
        $this->assertEquals('rejected', $course2->status);
        Notification::assertSentTo($trainer, \App\Notifications\CourseRejected::class);
    }

    public function test_certificate_verification(): void
    {
        $student = User::factory()->create(['role' => 'student', 'password' => bcrypt('password')]);
        StudentProfile::create(['user_id' => $student->id, 'university' => 'Test', 'city' => 'Cairo', 'age' => 20, 'graduation_status' => 'not_graduated']);
        
        $trainer = User::factory()->create(['role' => 'trainer']);
        TrainerProfile::create(['user_id' => $trainer->id, 'bio' => 'Bio', 'specialization' => 'Spec', 'approval_status' => 'approved']);
        
        $course = Course::create([
            'trainer_id' => $trainer->id,
            'category_id' => Category::first()->id,
            'title_en' => 'Complete Course',
            'title_ar' => 'كورس مكتمل',
            'description_en' => 'Desc',
            'description_ar' => 'وصف',
            'price' => 100,
            'status' => 'published'
        ]);
        
        $enrollment = Enrollment::create(['student_id' => $student->id, 'course_id' => $course->id, 'status' => 'active', 'progress_percentage' => 100]);
        $certificate = Certificate::create([
            'enrollment_id' => $enrollment->id,
            'verification_code' => 'CERT-TEST123',
            'issued_at' => now()
        ]);

        $this->getJson("/api/verify-certificate/{$certificate->verification_code}")
            ->assertStatus(200)->assertJson(['valid' => true]);
        
        $this->getJson('/api/verify-certificate/INVALID')
            ->assertStatus(404)->assertJson(['valid' => false]);
    }

    public function test_dompdf_certificate_generation(): void
    {
        $student = User::factory()->create(['role' => 'student', 'password' => bcrypt('password')]);
        StudentProfile::create(['user_id' => $student->id, 'university' => 'Test', 'city' => 'Cairo', 'age' => 20, 'graduation_status' => 'not_graduated']);
        
        $trainer = User::factory()->create(['role' => 'trainer']);
        TrainerProfile::create(['user_id' => $trainer->id, 'bio' => 'Bio', 'specialization' => 'Spec', 'approval_status' => 'approved']);
        
        $course = Course::create([
            'trainer_id' => $trainer->id,
            'category_id' => Category::first()->id,
            'title_en' => 'Certificate Course',
            'title_ar' => 'كورس شهادة',
            'description_en' => 'Desc',
            'description_ar' => 'وصف',
            'price' => 100,
            'status' => 'published'
        ]);
        
        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'active',
            'progress_percentage' => 100,
            'completed_at' => now()
        ]);

        // Verify DomPDF facade is available (no Class not found error)
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('certificates.certificate', [
            'student' => $student,
            'course' => $course,
            'certificate' => Certificate::create([
                'enrollment_id' => $enrollment->id,
                'verification_code' => 'CERT-DOMPDF-TEST',
                'issued_at' => now()
            ])
        ]);
        
        $this->assertInstanceOf(\Barryvdh\DomPDF\PDF::class, $pdf);
    }

    public function test_scout_database_search_returns_english_and_arabic_matches(): void
    {
        config(['scout.driver' => 'database']);
        
        $trainer = User::factory()->create(['role' => 'trainer']);
        TrainerProfile::create(['user_id' => $trainer->id, 'bio' => 'Bio', 'specialization' => 'Spec', 'approval_status' => 'approved']);
        
        // Create courses with English and Arabic titles
        Course::create([
            'trainer_id' => $trainer->id,
            'category_id' => Category::first()->id,
            'title_en' => 'Laravel Development',
            'title_ar' => 'تطوير لارافيل',
            'description_en' => 'Learn Laravel framework',
            'description_ar' => 'تعلم إطار عمل لارافيل',
            'price' => 100,
            'status' => 'published'
        ]);
        
        Course::create([
            'trainer_id' => $trainer->id,
            'category_id' => Category::first()->id,
            'title_en' => 'Python Programming',
            'title_ar' => 'برمجة بايثون',
            'description_en' => 'Learn Python programming',
            'description_ar' => 'تعلم برمجة بايثون',
            'price' => 150,
            'status' => 'published'
        ]);

        // Search in English
        $results = Course::search('Laravel')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('Laravel Development', $results->first()->title_en);

        // Search in Arabic
        $results = Course::search('لارافيل')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('تطوير لارافيل', $results->first()->title_ar);
    }

    public function test_video_completion_requires_active_enrollment(): void
    {
        $student = User::factory()->create(['role' => 'student', 'password' => bcrypt('password')]);
        StudentProfile::create(['user_id' => $student->id, 'university' => 'Test', 'city' => 'Cairo', 'age' => 20, 'graduation_status' => 'not_graduated']);
        
        $trainer = User::factory()->create(['role' => 'trainer']);
        TrainerProfile::create(['user_id' => $trainer->id, 'bio' => 'Bio', 'specialization' => 'Spec', 'approval_status' => 'approved']);
        
        $course = Course::create([
            'trainer_id' => $trainer->id,
            'category_id' => Category::first()->id,
            'title_en' => 'Video Course',
            'title_ar' => 'كورس فيديو',
            'description_en' => 'Desc',
            'description_ar' => 'وصف',
            'price' => 100,
            'status' => 'published'
        ]);
        
        $chapter = Chapter::create(['course_id' => $course->id, 'title_en' => 'Chapter 1', 'title_ar' => 'فصل 1', 'order' => 1]);
        $video = Video::create([
            'chapter_id' => $chapter->id,
            'title_en' => 'Video 1',
            'title_ar' => 'فيديو 1',
            'duration_seconds' => 300
        ]);

        // Create inactive enrollment
        Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'pending_payment'
        ]);

        $response = $this->actingAs($student)->postJson("/api/student/videos/{$video->id}/progress", [
            'watched_seconds' => 300,
            'is_completed' => true
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Your enrollment is not active']);
    }

    public function test_video_completion_updates_progress_percentage(): void
    {
        $student = User::factory()->create(['role' => 'student', 'password' => bcrypt('password')]);
        StudentProfile::create(['user_id' => $student->id, 'university' => 'Test', 'city' => 'Cairo', 'age' => 20, 'graduation_status' => 'not_graduated']);
        
        $trainer = User::factory()->create(['role' => 'trainer']);
        TrainerProfile::create(['user_id' => $trainer->id, 'bio' => 'Bio', 'specialization' => 'Spec', 'approval_status' => 'approved']);
        
        $course = Course::create([
            'trainer_id' => $trainer->id,
            'category_id' => Category::first()->id,
            'title_en' => 'Video Course',
            'title_ar' => 'كورس فيديو',
            'description_en' => 'Desc',
            'description_ar' => 'وصف',
            'price' => 100,
            'status' => 'published'
        ]);
        
        $chapter = Chapter::create(['course_id' => $course->id, 'title_en' => 'Chapter 1', 'title_ar' => 'فصل 1', 'order' => 1]);
        $video = Video::create([
            'chapter_id' => $chapter->id,
            'title_en' => 'Video 1',
            'title_ar' => 'فيديو 1',
            'duration_seconds' => 300
        ]);

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'active',
            'progress_percentage' => 0
        ]);

        $initialProgress = $enrollment->progress_percentage;
        
        $response = $this->actingAs($student)->postJson("/api/student/videos/{$video->id}/progress", [
            'watched_seconds' => 300,
            'is_completed' => true
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('enrollment_progress.progress_percentage', fn($value) => $value >= $initialProgress);
        $this->assertTrue(isset($response->json('enrollment_progress')['status']));
    }

    public function test_student_cannot_access_trainer_routes(): void
    {
        $student = User::factory()->create(['role' => 'student', 'password' => bcrypt('password')]);
        StudentProfile::create(['user_id' => $student->id, 'university' => 'Test', 'city' => 'Cairo', 'age' => 20, 'graduation_status' => 'not_graduated']);

        $response = $this->actingAs($student)->getJson('/api/trainer/courses');
        $response->assertStatus(403);
    }

    public function test_trainer_cannot_access_student_routes(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        TrainerProfile::create(['user_id' => $trainer->id, 'bio' => 'Bio', 'specialization' => 'Spec', 'approval_status' => 'approved']);

        $response = $this->actingAs($trainer)->getJson('/api/student/courses');
        $response->assertStatus(403);
    }

    public function test_student_cannot_access_admin_routes(): void
    {
        $student = User::factory()->create(['role' => 'student', 'password' => bcrypt('password')]);
        StudentProfile::create(['user_id' => $student->id, 'university' => 'Test', 'city' => 'Cairo', 'age' => 20, 'graduation_status' => 'not_graduated']);

        $response = $this->actingAs($student)->getJson('/api/admin/analytics/overview');
        $response->assertStatus(403);
    }

    public function test_trainer_cannot_access_admin_routes(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        TrainerProfile::create(['user_id' => $trainer->id, 'bio' => 'Bio', 'specialization' => 'Spec', 'approval_status' => 'approved']);

        $response = $this->actingAs($trainer)->getJson('/api/admin/analytics/overview');
        $response->assertStatus(403);
    }
}
