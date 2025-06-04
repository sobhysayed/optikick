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

class AuthController extends BaseController
{
    public function login(LoginRequest $request)
    {
        try {
            $user = User::where('login_id', $request->login_id)->first();
            
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
                    'login_id' => $user->login_id,
                    'email' => $user->email,
                    'role' => $user->role,
                    'profile' => $user->profile
                ]
            ], 'Successfully logged in');

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred', [], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->tokens()->delete();
            return $this->successResponse([], 'Successfully logged out');
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred', [], 500);
        }
    }

    public function forgotPassword(Request $request)
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

            return $this->successResponse([
                'message' => 'Password reset code has been sent to your email',
                'email' => $user->email
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to send reset code');
        }
    }

    public function verifyResetToken(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string'
        ]);

        try {
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->where('token', $request->token)
                ->where('created_at', '>', now()->subHours(1))
                ->first();

            if (!$resetRecord) {
                return $this->errorResponse('Invalid or expired code', [], 400);
            }

            return $this->successResponse([
                'message' => 'Code verified successfully',
                'email' => $request->email,
                'token' => $request->token
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to verify code');
        }
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed'
        ]);

        try {
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->where('token', $request->token)
                ->where('created_at', '>', now()->subHours(1))
                ->first();

            if (!$resetRecord) {
                return $this->errorResponse('Invalid or expired token');
            }

            $user = User::where('email', $request->email)->first();
            $user->password = Hash::make($request->password);
            $user->save();

            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            return $this->successResponse([], 'Password reset successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to reset password');
        }
    }
}