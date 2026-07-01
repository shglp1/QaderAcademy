<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PayoutRequest;

class PayoutRequestPolicy
{
    /**
     * Determine if the user can view the payout request.
     */
    public function view(User $user, PayoutRequest $payoutRequest): bool
    {
        // Trainer can view their own requests
        if ($user->id === $payoutRequest->trainer_id) {
            return true;
        }

        // Admins can view all requests
        return $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine if the user can create payout requests.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['trainer']);
    }

    /**
     * Determine if the user can approve/reject the payout request.
     * Only admins can manage payouts.
     */
    public function approve(User $user, PayoutRequest $payoutRequest): bool
    {
        return $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine if the user can reject the payout request.
     */
    public function reject(User $user, PayoutRequest $payoutRequest): bool
    {
        return $user->hasRole(['admin', 'super_admin']);
    }
}
