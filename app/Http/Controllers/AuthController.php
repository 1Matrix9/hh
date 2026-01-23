<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use App\Traits\ApiResponses;
use Dotenv\Util\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    use ApiResponses;
    public function login(LoginUserRequest $request) 
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->error('Invalid credentials', 401);
        }

        $user = Auth::user();
        if (is_null($user->email_verified_at)) {
            Auth::logout();
            return $this->error('Please verify your email before logging in', 403);
        }

        return $this->ok(
            'Authenticated',
            [
                'token' => $user->createToken(
                    'API token for ' . $user->email,
                    ['*'],
                    now()->addDay()
                )->plainTextToken,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'isAdmin' => $user->isAdmin,
                ]
            ]
        );
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->ok('Logged out successfully');
    }

    public function register(RegisterUserRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'isAdmin' => false,
            'points_balance' => 10,
            'wallet_balance' => 10,
            'email_verified_at' => now(), // Auto-verify for now
        ]);

        // $user->sendEmailVerificationNotification();

        return $this->ok(
            'Registration successful!',
            [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'isAdmin' => $user->isAdmin,
                    'points_balance' => $user->points_balance,
                    'wallet_balance' => $user->wallet_balance,
                    'email_verified_at' => $user->email_verified_at,
                ]
            ],
            201
        );
    }
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);
        $status = Password::sendResetLink(
            $request->only('email')
        );
        if ($status === Password::RESET_LINK_SENT) {
            return $this->ok(
                'Password reset link sent to your email. Please check your inbox.',
                null,
                200
            );
        }
        return $this->error(
            'Unable to send password reset link. Please try again.',
            statusCode: 500
        );
    }

    /**
     * Reset user's password with token
     */
   public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password has been reset successfully.'
            ], 200);
        }

        return response()->json([
            'message' => 'Invalid token or email.'
        ], 400);
    }


}
