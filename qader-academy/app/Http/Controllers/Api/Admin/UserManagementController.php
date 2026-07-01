<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use App\Notifications\UserStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    /**
     * List all trainers with their status
     */
    public function trainers()
    {
        $trainers = User::where('role', 'trainer')
            ->with('trainerProfile')
            ->paginate(20);

        return UserResource::collection($trainers);
    }

    /**
     * Get trainer details
     */
    public function trainerDetail(User $user)
    {
        $this->authorize('view', $user);
        
        return new UserResource($user->load('trainerProfile'));
    }

    /**
     * Approve a trainer account
     */
    public function approveTrainer(User $user)
    {
        $this->authorize('update', $user);

        if ($user->role !== 'trainer') {
            return response()->json(['error' => 'User is not a trainer'], 422);
        }

        $user->trainerProfile()->updateOrCreate(
            ['user_id' => $user->id],
            ['approval_status' => 'approved']
        );

        $user->notify(new UserStatusChanged('approved', 'Your trainer account has been approved. You can now create courses.'));

        return response()->json(['message' => 'Trainer approved successfully']);
    }

    /**
     * Reject a trainer application
     */
    public function rejectTrainer(User $user, Request $request)
    {
        $this->authorize('update', $user);

        if ($user->role !== 'trainer') {
            return response()->json(['error' => 'User is not a trainer'], 422);
        }

        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $user->trainerProfile()->updateOrCreate(
            ['user_id' => $user->id],
            ['approval_status' => 'rejected']
        );

        $user->notify(new UserStatusChanged('rejected', 'Your trainer application was rejected. Reason: ' . $request->reason));

        return response()->json(['message' => 'Trainer application rejected']);
    }

    /**
     * Suspend a user (trainer or student)
     */
    public function suspendUser(User $user, Request $request)
    {
        $this->authorize('update', $user);

        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        $user->update(['status' => 'suspended']);

        $message = $request->reason 
            ? 'Your account has been suspended. Reason: ' . $request->reason 
            : 'Your account has been suspended.';

        $user->notify(new UserStatusChanged('suspended', $message));

        return response()->json(['message' => 'User suspended successfully']);
    }

    /**
     * List all students
     */
    public function students()
    {
        $students = User::where('role', 'student')
            ->with('studentProfile')
            ->paginate(20);

        return UserResource::collection($students);
    }
}
