<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Models\TrainerEarning;
use App\Notifications\PayoutStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayoutController extends Controller
{
    /**
     * List all payout requests
     */
    public function index()
    {
        $requests = PayoutRequest::with(['trainer.trainerProfile'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json(['payout_requests' => $requests]);
    }

    /**
     * Approve a payout request
     */
    public function approve(PayoutRequest $request)
    {
        DB::transaction(function () use ($request) {
            $request->update(['status' => 'approved']);

            // Update the trainer earnings ledger
            TrainerEarning::where('id', $request->trainer_earning_id)
                ->update(['payout_status' => 'paid', 'paid_at' => now()]);

            // Notify the trainer
            $request->trainer->notify(
                new PayoutStatusNotification('approved', 'Your payout request of ' . $request->amount . ' has been approved and processed.')
            );
        });

        return response()->json(['message' => 'Payout request approved successfully']);
    }

    /**
     * Reject a payout request
     */
    public function reject(PayoutRequest $request, Request $httpRequest)
    {
        $httpRequest->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        DB::transaction(function () use ($request, $httpRequest) {
            $request->update([
                'status' => 'rejected',
                'rejection_reason' => $httpRequest->reason
            ]);

            // Notify the trainer
            $message = $httpRequest->reason 
                ? 'Your payout request was rejected. Reason: ' . $httpRequest->reason 
                : 'Your payout request was rejected.';
            
            $request->trainer->notify(
                new PayoutStatusNotification('rejected', $message)
            );
        });

        return response()->json(['message' => 'Payout request rejected']);
    }
}
