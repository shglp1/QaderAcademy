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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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

        $response = $this->actingAs($student)->postJson('/api/enrollments', ['course_id' => $course->id]);
        $response->assertStatus(201);
        $enrollment = Enrollment::find($response->json('enrollment.id'));
        $this->assertEquals('pending_payment', $enrollment->status);

        $payment = Payment::where('enrollment_id', $enrollment->id)->first();
        $this->postJson('/api/webhooks/payment', [
            'PaymentId' => $payment->transaction_id,
            'ReferenceId' => $enrollment->id,
            'Status' => 'Success'
        ])->assertStatus(200);

        $enrollment->refresh();
        $this->assertEquals('active', $enrollment->status);
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

        $response = $this->actingAs($student)->postJson('/api/quiz-attempts', [
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

        $response = $this->actingAs($student)->postJson('/api/quiz-attempts', [
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
}
