<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'bio',
        'specialization',
        'payout_method',
        'payout_details',
        'approval_status',
        'total_earnings',
    ];

    protected $casts = [
        'total_earnings' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
