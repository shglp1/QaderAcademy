<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Http\Resources\Student\EnrollmentResource;
use App\Http\Requests\Student\StoreEnrollmentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class EnrollmentController extends Controller
{
    /**
     * Display list of student's enrollments with progress.
     * GET /api/student/enrollments
     */
    public function myEnrollments()
    {
        $studentId = Auth::id();
        
        $enrollments = Enrollment::with(['course.trainer.trainerProfile', 'course.category'])
            ->with(['course.chapters', 'payment', 'certificate.enrollment.course'])
            ->where('student_id', $studentId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'enrollments' => EnrollmentResource::collection($enrollments),
        ]);
    }

    /**
     * Display a specific enrollment.
     * GET /api/student/enrollments/{enrollment}
     */
    public function show(Enrollment $enrollment)
    {
        // Ensure student can only view their own enrollments
        if ($enrollment->student_id !== Auth::id()) {
            return response()->json([
                'message' => __('messages.unauthorized'),
            ], 403);
        }

        $enrollment->load([
            'course.trainer.trainerProfile',
            'course.category',
            'course.chapters.videos',
            'course.chapters.quizzes.questions',
            'payment',
            'certificate.enrollment.course',
        ]);

        return response()->json([
            'enrollment' => new EnrollmentResource($enrollment),
        ]);
    }

    /**
     * Create a new enrollment and initiate payment.
     * POST /api/student/enrollments
     * 
     * Note: Enrollment is created with status "pending_payment".
     * It will be activated only after the payment webhook confirms success.
     */
    public function store(StoreEnrollmentRequest $request)
    {
        $validated = $request->validated();
        $studentId = Auth::id();
        $courseId = $validated['course_id'];

        // Check if already enrolled
        $existingEnrollment = Enrollment::where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->first();

        if ($existingEnrollment) {
            if ($existingEnrollment->status === 'active') {
                return response()->json([
                    'message' => __('messages.already_enrolled'),
                ], 400);
            } elseif ($existingEnrollment->status === 'pending_payment') {
                // Return existing pending enrollment
                return response()->json([
                    'message' => __('messages.pending_payment'),
                    'enrollment' => new EnrollmentResource($existingEnrollment),
                ], 200);
            }
        }

        $course = \App\Models\Course::findOrFail($courseId);

        DB::beginTransaction();
        try {
            // Create enrollment in pending_payment status
            $enrollment = Enrollment::create([
                'student_id' => $studentId,
                'course_id' => $courseId,
                'status' => 'pending_payment',
                'progress_percentage' => 0,
            ]);

            // Create payment record
            $payment = Payment::create([
                'student_id' => $studentId,
                'course_id' => $courseId,
                'enrollment_id' => $enrollment->id,
                'amount' => $course->price,
                'currency' => 'SAR',
                'status' => 'pending',
                'payment_method' => 'myfatoorah',
            ]);

            $enrollment->update([
                'payment_id' => $payment->id,
            ]);

            DB::commit();

            // Initiate MyFatoorah payment session
            $paymentSession = $this->initiateMyFatoorahPayment($payment, $enrollment, $course);

            return response()->json([
                'message' => __('messages.enrollment_created_pending_payment'),
                'enrollment' => new EnrollmentResource($enrollment->fresh()),
                'payment_session' => $paymentSession,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Enrollment creation failed: ' . $e->getMessage());
            
            return response()->json([
                'message' => __('messages.enrollment_failed'),
            ], 500);
        }
    }

    /**
     * Initiate MyFatoorah payment session.
     * 
     * @param Payment $payment
     * @param Enrollment $enrollment
     * @param \App\Models\Course $course
     * @return array
     */
    private function initiateMyFatoorahPayment(Payment $payment, Enrollment $enrollment, \App\Models\Course $course): array
    {
        // MyFatoorah API configuration
        $apiKey = config('services.myfatoorah.api_key');
        $isTest = config('services.myfatoorah.is_test', true);
        $baseUrl = $isTest 
            ? 'https://apitest.myfatoorah.com' 
            : 'https://api.myfatoorah.com';

        $callbackUrl = route('webhooks.payment');
        
        // Build request payload for MyFatoorah
        $payload = [
            'CustomerName' => Auth::user()->name,
            'DisplayCurrencyIso' => 'SAR',
            'CountryCodeIso' => 'SA',
            'NotificationType' => 'Link',
            'CallBackUrl' => $callbackUrl,
            'ErrorUrl' => $callbackUrl,
            'Language' => app()->getLocale() === 'ar' ? 'ar' : 'en',
            'Items' => [
                [
                    'Name' => $course->title_en,
                    'Quantity' => 1,
                    'UnitPrice' => $course->price,
                ]
            ],
            'ClientIp' => request()->ip(),
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post("{$baseUrl}/v2/SendPayment", $payload);

            if ($response->successful() && isset($response['Data']['InvoiceURL'])) {
                // Update payment with MyFatoorah invoice ID
                $payment->update([
                    'gateway_reference' => $response['Data']['InvoiceID'] ?? null,
                    'gateway_url' => $response['Data']['InvoiceURL'] ?? null,
                ]);

                return [
                    'invoice_id' => $response['Data']['InvoiceID'] ?? null,
                    'checkout_url' => $response['Data']['InvoiceURL'] ?? null,
                    'payment_id' => $payment->id,
                ];
            }

            throw new \Exception('MyFatoorah API error: ' . json_encode($response->json()));

        } catch (\Exception $e) {
            \Log::error('MyFatoorah payment initiation failed: ' . $e->getMessage());

            $mockInvoiceId = 'MF-' . time();
            $mockCheckoutUrl = '#mock-checkout';

            $payment->update([
                'gateway_reference' => $mockInvoiceId,
                'gateway_url' => $mockCheckoutUrl,
            ]);
            
            // Return mock checkout URL for development
            return [
                'invoice_id' => $mockInvoiceId,
                'checkout_url' => $mockCheckoutUrl,
                'payment_id' => $payment->id,
                'note' => 'Mock payment session (MyFatoorah not configured)',
            ];
        }
    }
}
