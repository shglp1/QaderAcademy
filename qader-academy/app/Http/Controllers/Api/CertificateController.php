<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    /**
     * Verify a certificate by its verification code.
     * 
     * GET /api/verify-certificate/{certificateNumber}
     * 
     * Returns certificate details if valid, or an error if invalid.
     */
    public function verify($certificateNumber)
    {
        $certificate = Certificate::where('verification_code', $certificateNumber)
            ->orWhere('certificate_number', $certificateNumber)
            ->with(['enrollment.student', 'enrollment.course.trainer'])
            ->first();

        if (!$certificate) {
            return response()->json([
                'valid' => false,
                'message' => __('messages.certificate_not_found'),
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'certificate' => [
                'certificate_number' => $certificate->certificate_number,
                'verification_code' => $certificate->verification_code,
                'issued_at' => $certificate->issued_at->toDateString(),
                'student_name' => $certificate->enrollment->student->name,
                'course_title_en' => $certificate->enrollment->course->title_en,
                'course_title_ar' => $certificate->enrollment->course->title_ar,
                'trainer_name' => $certificate->enrollment->course->trainer->name,
                'completion_date' => $certificate->enrollment->completed_at?->toDateString(),
            ],
            'message' => __('messages.certificate_verified_successfully'),
        ]);
    }

    /**
     * Get current student's certificates.
     * 
     * GET /api/student/certificates/me
     */
    public function myCertificates(Request $request)
    {
        $studentId = $request->user()->id;

        $certificates = Certificate::with(['enrollment.course'])
            ->where('student_id', $studentId)
            ->orderBy('issued_at', 'desc')
            ->get();

        return response()->json([
            'certificates' => $certificates->map(function ($certificate) {
                return [
                    'id' => $certificate->id,
                    'certificate_number' => $certificate->certificate_number,
                    'verification_code' => $certificate->verification_code,
                    'course_title' => app()->getLocale() === 'ar' 
                        ? $certificate->enrollment->course->title_ar 
                        : $certificate->enrollment->course->title_en,
                    'issued_at' => $certificate->issued_at->toDateString(),
                    'download_url' => $certificate->file_path 
                        ? asset('storage/' . $certificate->file_path) 
                        : null,
                ];
            }),
        ]);
    }

    /**
     * Download a specific certificate.
     * 
     * GET /api/student/certificates/{certificate}/download
     */
    public function download(Certificate $certificate)
    {
        // Ensure only the certificate owner can download
        if ($certificate->student_id !== auth()->id()) {
            return response()->json([
                'message' => __('messages.unauthorized'),
            ], 403);
        }

        if (!$certificate->file_path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($certificate->file_path)) {
            return response()->json([
                'message' => __('messages.certificate_file_not_found'),
            ], 404);
        }

        return \Illuminate\Support\Facades\Storage::disk('public')
            ->download($certificate->file_path, "certificate_{$certificate->certificate_number}.pdf");
    }
}
