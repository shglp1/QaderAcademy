<?php

namespace App\Jobs;

use App\Models\Enrollment;
use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class GenerateCertificateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $enrollment;

    /**
     * Create a new job instance.
     */
    public function __construct(Enrollment $enrollment)
    {
        $this->enrollment = $enrollment;
    }

    /**
     * Execute the job.
     * 
     * Generates a PDF certificate with:
     * - Student name
     * - Course title
     * - Trainer name
     * - Completion date
     * - Unique verification code
     */
    public function handle(): void
    {
        $enrollment = $this->enrollment;
        
        Log::info("Generating certificate for enrollment #{$enrollment->id}", [
            'student_id' => $enrollment->student_id,
            'course_id' => $enrollment->course_id,
        ]);

        // Generate unique verification code
        $verificationCode = $this->generateVerificationCode();

        // Ensure no duplicate certificates
        $existingCertificate = Certificate::where('enrollment_id', $enrollment->id)->first();
        if ($existingCertificate) {
            Log::warning("Certificate already exists for enrollment #{$enrollment->id}");
            return;
        }

        // Gather certificate data
        $student = $enrollment->student;
        $course = $enrollment->course;
        $trainer = $course->trainer;
        $trainerProfile = $trainer->trainerProfile;

        $data = [
            'student_name' => $student->name,
            'course_title' => app()->getLocale() === 'ar' ? $course->title_ar : $course->title_en,
            'trainer_name' => $trainer->name,
            'completion_date' => $enrollment->completed_at ?? now(),
            'verification_code' => $verificationCode,
            'certificate_number' => $verificationCode, // Same as verification code for simplicity
        ];

        // Generate PDF using DomPDF
        $pdf = Pdf::loadView('certificates.certificate', $data);
        
        // Set paper size and orientation
        $pdf->setPaper('A4', 'landscape');

        // Save PDF to storage
        $filename = "certificate_{$verificationCode}.pdf";
        $path = "certificates/{$filename}";
        
        Storage::disk('public')->put($path, $pdf->output());

        // Create certificate record in database
        $certificate = Certificate::create([
            'enrollment_id' => $enrollment->id,
            'student_id' => $enrollment->student_id,
            'course_id' => $enrollment->course_id,
            'certificate_number' => $verificationCode,
            'verification_code' => $verificationCode,
            'issued_at' => now(),
            'file_path' => $path,
        ]);

        Log::info("Certificate generated successfully", [
            'certificate_id' => $certificate->id,
            'verification_code' => $verificationCode,
            'student_id' => $enrollment->student_id,
            'course_id' => $enrollment->course_id,
        ]);

        // Notify student that certificate is ready
        $student->notify(new \App\Notifications\CertificateReady($certificate));
    }

    /**
     * Generate a unique verification code.
     * Format: CERT-YYYY-XXXXXX (e.g., CERT-2025-A1B2C3)
     */
    private function generateVerificationCode(): string
    {
        $year = date('Y');
        $randomPart = strtoupper(substr(uniqid(), -6));
        
        // Ensure uniqueness
        do {
            $code = "CERT-{$year}-{$randomPart}";
        } while (Certificate::where('verification_code', $code)->exists());

        return $code;
    }
}
