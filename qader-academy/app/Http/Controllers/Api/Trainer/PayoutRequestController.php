<?php

namespace App\Http\Controllers\Api\Trainer;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Http\Requests\Trainer\StorePayoutRequest;
use App\Http\Resources\Trainer\PayoutRequestResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayoutRequestController extends Controller
{
    /**
     * Display payout requests for the authenticated trainer.
     */
    public function index()
    {
        $payoutRequests = PayoutRequest::where('trainer_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'payout_requests' => PayoutRequestResource::collection($payoutRequests),
        ]);
    }

    /**
     * Store a newly created payout request.
     */
    public function store(StorePayoutRequest $request)
    {
        $validated = $request->validated();
        $trainerId = Auth::id();
        
        // Check available pending earnings
        $availableBalance = DB::table('trainer_earnings')
            ->where('trainer_id', $trainerId)
            ->sum('pending_amount');
        
        if ($validated['amount'] > $availableBalance) {
            return response()->json([
                'message' => __('messages.insufficient_balance'),
                'available_balance' => $availableBalance,
            ], 422);
        }
        
        DB::beginTransaction();
        try {
            $payoutRequest = PayoutRequest::create([
                'trainer_id' => $trainerId,
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'account_details' => $validated['account_details'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
            ]);
            
            // Update trainer_earnings ledger
            DB::table('trainer_earnings')
                ->where('trainer_id', $trainerId)
                ->update([
                    'pending_amount' => DB::raw('pending_amount - ' . $validated['amount']),
                    'requested_amount' => DB::raw('requested_amount + ' . $validated['amount']),
                ]);
            
            DB::commit();
            
            return response()->json([
                'message' => __('messages.payout_request_created'),
                'payout_request' => new PayoutRequestResource($payoutRequest->fresh()),
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => __('messages.error_creating_payout_request'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified payout request.
     */
    public function show(PayoutRequest $payoutRequest)
    {
        $this->authorize('view', $payoutRequest);
        
        return response()->json([
            'payout_request' => new PayoutRequestResource($payoutRequest),
        ]);
    }
}
