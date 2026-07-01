<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Enrollment;
use App\Notifications\EnrollmentActivated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    /**
     * Handle MyFatoorah payment webhook.
     * 
     * This endpoint is called by MyFatoorah when a payment status changes.
     * It verifies the webhook signature, updates the Payment status,
     * and activates the related Enrollment ONLY on successful payment.
     * 
     * POST /api/webhooks/payment
     */
    public function handle(Request $request)
    {
        Log::info('Payment webhook received', ['payload' => $request->all()]);

        // Verify webhook signature
        if (!$this->verifySignature($request)) {
            Log::warning('Payment webhook signature verification failed');
            return response()->json([
                'message' => 'Invalid signature',
            ], 401);
        }

        $data = $request->all();

        // Extract payment details from MyFatoorah webhook payload
        $invoiceId = $data['InvoiceId'] ?? null;
        $paymentStatus = $data['PaymentStatus'] ?? null;
        $amount = $data['Amount'] ?? null;

        if (!$invoiceId) {
            Log::error('Payment webhook missing InvoiceId');
            return response()->json([
                'message' => 'Missing InvoiceId',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Find payment record by gateway reference (InvoiceId)
            $payment = Payment::where('gateway_reference', $invoiceId)->firstOrFail();

            // Map MyFatoorah status to our internal status
            $statusMap = [
                'Success' => 'success',
                'Failed' => 'failed',
                'Cancelled' => 'cancelled',
                'Pending' => 'pending',
            ];

            $newStatus = $statusMap[$paymentStatus] ?? 'unknown';

            // Update payment status
            $payment->update([
                'status' => $newStatus,
                'gateway_response' => json_encode($data),
                'paid_at' => $newStatus === 'success' ? now() : null,
            ]);

            // ONLY activate enrollment if payment is successful
            if ($newStatus === 'success') {
                $enrollment = Enrollment::where('id', $payment->enrollment_id)->first();

                if ($enrollment && $enrollment->status === 'pending_payment') {
                    $enrollment->update([
                        'status' => 'active',
                        'activated_at' => now(),
                    ]);

                    // Send notification to student
                    $enrollment->student->notify(new EnrollmentActivated($enrollment));

                    Log::info("Enrollment #{$enrollment->id} activated after successful payment", [
                        'student_id' => $enrollment->student_id,
                        'course_id' => $enrollment->course_id,
                        'payment_id' => $payment->id,
                    ]);
                }
            }

            DB::commit();

            Log::info("Payment webhook processed successfully", [
                'payment_id' => $payment->id,
                'status' => $newStatus,
                'invoice_id' => $invoiceId,
            ]);

            return response()->json([
                'message' => 'Webhook processed successfully',
                'payment_id' => $payment->id,
                'status' => $newStatus,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment webhook processing failed: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Webhook processing failed',
            ], 500);
        }
    }

    /**
     * Verify MyFatoorah webhook signature.
     * 
     * In production, this should verify the HMAC signature sent by MyFatoorah.
     * For development/testing, we accept requests with a valid test token.
     */
    private function verifySignature(Request $request): bool
    {
        // Get the signature from headers
        $signature = $request->header('X-MyFatoorah-Signature');
        $isTest = config('services.myfatoorah.is_test', true);

        // In test mode, allow requests without signature for local testing
        if ($isTest && !$signature) {
            Log::debug('Test mode: skipping signature verification');
            return true;
        }

        // In production, verify the HMAC signature
        if (!$isTest && $signature) {
            $apiKey = config('services.myfatoorah.api_key');
            $payload = $request->getContent();
            
            $expectedSignature = hash_hmac('sha256', $payload, $apiKey);
            
            if (hash_equals($expectedSignature, $signature)) {
                return true;
            }
            
            Log::warning('Signature mismatch');
            return false;
        }

        // If we're in production mode but no signature provided, reject
        if (!$isTest && !$signature) {
            return false;
        }

        // Test mode with signature provided - validate it
        if ($isTest && $signature) {
            $testToken = config('services.myfatoorah.test_webhook_token');
            return hash_equals($testToken ?? 'test-token', $signature);
        }

        return false;
    }
}
