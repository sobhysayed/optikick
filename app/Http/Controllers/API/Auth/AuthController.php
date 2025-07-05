<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class AuthController extends BaseController
{
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $identifier = $request->input('identifier');

            $user = filter_var($identifier, FILTER_VALIDATE_EMAIL)
                ? User::where('email', $identifier)->first()
                : User::where('login_id', $identifier)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return $this->errorResponse('Invalid credentials', [], 401);
            }

            $token = $user->createToken('auth-token')->plainTextToken;
            $user->load(['profile']);

            return $this->successResponse([
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'role' => $user->role,
                    'first_name' => $user->profile->first_name ?? null,
                    'last_name' => $user->profile->last_name ?? null,
                ]
            ], 'Successfully logged in');

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred', [], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->tokens()->delete();
            return $this->successResponse([], 'Successfully logged out');
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred', [], 500);
        }
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'identifier' => 'required|string'
        ]);

        try {
            $user = User::where('email', $request->identifier)
                       ->orWhere('login_id', $request->identifier)
                       ->first();

            if (!$user) {
                return $this->errorResponse('User not found', [], 404);
            }

            if (!$user->email) {
                return $this->errorResponse('This account does not have an email set. Please contact support.', [], 422);
            }

            $token = Str::random(6);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => $token,
                    'created_at' => now()
                ]
            );

            Mail::raw(
                "Your password reset code is: $token\n\nThis code will expire in 60 minutes.",
                function($message) use ($user) {
                    $message->to($user->email)
                            ->subject('Password Reset Code - Optikick');
                }
            );

            $maskedEmail = $this->maskEmail($user->email);

            return $this->successResponse(
                ['email' => $maskedEmail],
                'A 6-digit password reset code has been sent to your registered email address. Please use this code to reset your password within the next 60 minutes.'
            );


        } catch (\Exception $e) {
            return $this->errorResponse('Failed to send reset code');
        }
    }

    private function maskEmail($email): string
    {
        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1] ?? '';
        if (strlen($name) <= 2) {
            $masked = substr($name, 0, 1) . str_repeat('*', max(0, strlen($name) - 1));
        } else {
            $masked = substr($name, 0, 1) . str_repeat('*', strlen($name) - 2) . substr($name, -1);
        }
        return $masked . '@' . $domain;
    }

    public function verifyResetToken(Request $request): JsonResponse
    {
        $request->validate([
            'identifier' => 'required|string',
            'token' => 'required|string',
        ]);

        try {
            $user = filter_var($request->identifier, FILTER_VALIDATE_EMAIL)
                ? User::where('email', $request->identifier)->first()
                : User::where('login_id', $request->identifier)->first();

            if (!$user) {
                return $this->errorResponse('User not found', [], 404);
            }

            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $user->email)
                ->where('token', $request->token)
                ->where('created_at', '>', now()->subHours(1))
                ->first();

            if (!$resetRecord) {
                return $this->errorResponse('Invalid or expired code', [], 400);
            }

            return $this->successResponse(
                [
                    'email' => $user->email,
                    'token' => $request->token
                ],
                'Code verified successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to verify code');
        }
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'identifier' => 'required|string',
            'token' => 'required|string',
            'password' => 'required|min:8|confirmed',
        ], [
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        try {
            $user = filter_var($request->identifier, FILTER_VALIDATE_EMAIL)
                ? User::where('email', $request->identifier)->first()
                : User::where('login_id', $request->identifier)->first();

            if (!$user) {
                return $this->errorResponse('User not found', [], 404);
            }

            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $user->email)
                ->where('token', $request->token)
                ->where('created_at', '>', now()->subHours(1))
                ->first();

            if (!$resetRecord) {
                return $this->errorResponse('Invalid or expired token');
            }

            $user->password = Hash::make($request->password);
            $user->save();

            DB::table('password_reset_tokens')
                ->where('email', $user->email)
                ->delete();

            $token = $user->createToken('reset-password-token')->plainTextToken;
            $user->load('profile');

            return $this->successResponse([
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'role' => $user->role,
                    'first_name' => $user->profile->first_name ?? null,
                    'last_name' => $user->profile->last_name ?? null,
                ]
            ], 'Password reset successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to reset password');
        }
    }
}
