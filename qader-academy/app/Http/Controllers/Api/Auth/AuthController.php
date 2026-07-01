<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\StudentProfile;
use App\Models\TrainerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    /**
     * Register a new student user
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:student,trainer'],
            // Student specific fields
            'age' => ['nullable', 'integer', 'min:1'],
            'birth_date' => ['nullable', 'date'],
            'university' => ['nullable', 'string', 'max:255'],
            'graduation_status' => ['nullable', 'in:graduating,graduated,not_graduated'],
            'graduation_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'city' => ['nullable', 'string', 'max:255'],
            // Trainer specific fields
            'bio' => ['nullable', 'string', 'max:1000'],
            'specialization' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        if ($request->role === 'student') {
            StudentProfile::create([
                'user_id' => $user->id,
                'age' => $request->age,
                'birth_date' => $request->birth_date,
                'university' => $request->university,
                'graduation_status' => $request->graduation_status,
                'graduation_year' => $request->graduation_year,
                'city' => $request->city,
            ]);
        } elseif ($request->role === 'trainer') {
            TrainerProfile::create([
                'user_id' => $user->id,
                'bio' => $request->bio,
                'specialization' => $request->specialization,
                'approval_status' => 'pending', // Requires admin approval
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => __('auth.registration_success'),
            'user' => $user,
            'profile' => $request->role === 'student' ? $user->studentProfile : $user->trainerProfile,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => __('auth.failed'),
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        
        // Check if trainer is approved
        if ($user->role === 'trainer' && $user->trainerProfile && $user->trainerProfile->approval_status === 'rejected') {
            return response()->json([
                'message' => __('Your trainer account has been rejected. Please contact support.'),
            ], 403);
        }

        if ($user->role === 'trainer' && $user->trainerProfile && $user->trainerProfile->approval_status === 'pending') {
            return response()->json([
                'message' => __('Your trainer account is pending approval.'),
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => __('auth.login_success'),
            'user' => $user,
            'profile' => $user->role === 'student' ? $user->studentProfile : ($user->trainerProfile ?? null),
            'access_token' => $token,
            'token_type' => 'Bearer',
            'role' => $user->role,
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => __('auth.logout_success'),
        ]);
    }

    /**
     * Send password reset link
     */
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => __($status)])
            : response()->json(['message' => __($status)], 400);
    }

    /**
     * Reset password with token
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => __($status)])
            : response()->json(['message' => __($status)], 400);
    }
}
