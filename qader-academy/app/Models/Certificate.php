<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Certificate extends Model
{
    protected $fillable = [
        'student_id',
        'course_id',
        'enrollment_id',
        'certificate_number',
        'verification_code',
        'qr_code_path',
        'file_path',
        'issued_at',
        'issued_date',
        'is_valid',
        'revoked_at',
        'revocation_reason',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'issued_date' => 'date',
        'is_valid' => 'boolean',
        'revoked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Certificate $certificate) {
            if ($certificate->enrollment_id) {
                $enrollment = Enrollment::with('course')->find($certificate->enrollment_id);

                if ($enrollment) {
                    $certificate->student_id ??= $enrollment->student_id;
                    $certificate->course_id ??= $enrollment->course_id;
                }
            }

            $certificate->verification_code ??= $certificate->certificate_number;
            $certificate->certificate_number ??= $certificate->verification_code;

            if (!$certificate->issued_at) {
                $certificate->issued_at = $certificate->issued_date
                    ? Carbon::parse($certificate->issued_date)
                    : now();
            }

            $certificate->issued_date ??= $certificate->issued_at->toDateString();
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
}
