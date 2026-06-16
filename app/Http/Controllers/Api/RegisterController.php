<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ApiKeyMail;
use App\Mail\OtpMail;
use App\Models\RegistrationRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class RegisterController extends Controller
{
    /**
     * Step 1 — Send OTP to email.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
        ]);

        // Block if email already has an active account
        if (User::where('email', $validated['email'])->exists()) {
            return response()->json([
                'data'  => null,
                'error' => ['code' => 'EMAIL_EXISTS', 'message' => 'An account with this email already exists.'],
            ], 422);
        }

        // Rate limit: max 3 OTP sends per email per 10 minutes
        $key = 'otp_send:' . md5(strtolower($validated['email']));
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'data'  => null,
                'error' => ['code' => 'TOO_MANY_REQUESTS', 'message' => "Too many attempts. Try again in {$seconds} seconds."],
            ], 429);
        }
        RateLimiter::hit($key, 600);

        // Create or update registration request
        $reg = RegistrationRequest::updateOrCreate(
            ['email' => $validated['email']],
            ['name' => $validated['name'], 'status' => 'pending', 'email_verified_at' => null]
        );

        $otp = $reg->generateOtp();

        Mail::to($validated['email'])->send(new OtpMail($validated['name'], $otp));

        return response()->json([
            'data'  => ['message' => 'Verification code sent to your email.'],
            'error' => null,
        ]);
    }

    /**
     * Step 2 — Verify OTP, create account, email API key.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|string|size:6',
        ]);

        $reg = RegistrationRequest::where('email', $validated['email'])->first();

        if (!$reg || $reg->status === 'rejected') {
            return response()->json([
                'data'  => null,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'No pending registration found for this email.'],
            ], 404);
        }

        // Already approved (resend scenario)
        if ($reg->isApproved()) {
            return response()->json([
                'data'  => null,
                'error' => ['code' => 'ALREADY_VERIFIED', 'message' => 'This email is already verified. Check your inbox for your API key.'],
            ], 422);
        }

        // Too many wrong attempts
        if ($reg->otp_attempts >= 5) {
            return response()->json([
                'data'  => null,
                'error' => ['code' => 'TOO_MANY_ATTEMPTS', 'message' => 'Too many incorrect attempts. Please request a new code.'],
            ], 429);
        }

        if (!$reg->isOtpValid($validated['otp'])) {
            $reg->increment('otp_attempts');
            $remaining = 5 - ($reg->otp_attempts);
            return response()->json([
                'data'  => null,
                'error' => ['code' => 'INVALID_OTP', 'message' => "Incorrect or expired code. {$remaining} attempts remaining."],
            ], 422);
        }

        // OTP valid — create account
        $apiKey = User::generateApiKey();

        User::create([
            'name'    => $reg->name,
            'email'   => $reg->email,
            'api_key' => $apiKey,
        ]);

        $reg->update([
            'status'             => 'approved',
            'api_key'            => $apiKey,
            'email_verified_at'  => now(),
            'reviewed_at'        => now(),
            'otp'                => null,
        ]);

        // Send API key email
        $chatUrl = config('app.url');
        Mail::to($reg->email)->send(new ApiKeyMail($reg->name, $apiKey, $chatUrl));

        return response()->json([
            'data'  => ['message' => 'Email verified! Your API key has been sent to ' . $reg->email],
            'error' => null,
        ]);
    }
}
