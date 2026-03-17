<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Step 1: Send OTP to the user's email.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user = User::firstOrCreate(
            ['email' => $request->email],
            ['name'  => Str::before($request->email, '@')]
        );

        $user->update([
            'otp'            => bcrypt($otp),
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        // Send email (configure SMTP in .env)
        Mail::raw(
            "Your Dazzle Drys OTP is: {$otp}\n\nValid for 10 minutes.",
            fn ($m) => $m->to($user->email)->subject('Dazzle Drys – OTP Verification')
        );

        return response()->json(['message' => 'OTP sent to your email.']);
    }

    /**
     * Step 2: Verify OTP and return API token.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|digits:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if (Carbon::now()->gt($user->otp_expires_at)) {
            return response()->json(['message' => 'OTP has expired.'], 422);
        }

        if (!\Hash::check($request->otp, $user->otp)) {
            return response()->json(['message' => 'Invalid OTP.'], 422);
        }

        $user->update(['is_verified' => true, 'otp' => null, 'otp_expires_at' => null]);

        $token = $user->createToken('dazzle-drys-app')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token'   => $token,
            'user'    => $user->only(['id', 'name', 'email', 'phone', 'address']),
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'name'    => 'sometimes|string|max:100',
            'phone'   => 'sometimes|string|max:20',
            'address' => 'sometimes|string|max:500',
        ]);

        $user = $request->user();
        $user->update($request->only(['name', 'phone', 'address']));

        return response()->json(['message' => 'Profile updated.', 'user' => $user]);
    }

    /**
     * Register / update FCM token.
     */
    public function registerDeviceToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string',
            'platform'  => 'required|in:android,ios',
        ]);

        $user = $request->user();
        $user->deviceTokens()->updateOrCreate(
            ['fcm_token' => $request->fcm_token],
            ['platform'  => $request->platform]
        );

        return response()->json(['message' => 'Device token registered.']);
    }

    /**
     * Logout – revoke current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out.']);
    }
}
