<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    use ApiResponses;
   
    public function index()
    {
        $users =User::all(['id', 'name', 'email', 'isAdmin', 'points_balance', 'wallet_balance', 'email_verified_at', 'created_at']);

        return $this->ok('Users retrieved successfully', [
            'users' => $users
        ]);
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->error('User not found', 404);
        }

        $user->delete();

        return $this->ok('User deleted successfully');
    }

    public function show(Request $request)
    {
        return $this->ok('User retrieved successfully', [
            'user' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'isAdmin' => $request->user()->isAdmin,
                'points_balance' => $request->user()->points_balance,
                'wallet_balance' => $request->user()->wallet_balance,
                'email_verified_at' => $request->user()->email_verified_at,
                'created_at' => $request->user()->created_at,
            ]
        ]);
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $request->user()->id,
        ]);

        $user = $request->user();
        $user->update($validated);

        return $this->ok('Profile updated successfully', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();


        if (!Hash::check($validated['current_password'], $user->password)) {
            return $this->error('Current password is incorrect', 422);
        }


        $user->update([
            'password' => Hash::make($validated['password'])
        ]);

        return $this->ok('Password changed successfully');
    }


    public function getWallet(Request $request)
    {
        return $this->ok('Wallet retrieved successfully', [
            'wallet_balance' => $request->user()->wallet_balance,
            'points_balance' => $request->user()->points_balance,
        ]);
    }

 
    public function getLeaderboard(Request $request)
    {
        $user = $request->user();
        
        $leaderboard = $user->leaderboard;
        
        if (!$leaderboard) {
            return $this->ok('No leaderboard entry found', [
                'rank' => null,
                'points' => $user->points_balance
            ]);
        }

        return $this->ok('Leaderboard retrieved successfully', [
            'rank' => $leaderboard->rank,
            'points' => $leaderboard->points
        ]);
    }

    public function adjustWallet(Request $request, $id)
    {
        $validated = $request->validate([
            'wallet_balance' => 'required|numeric',
        ]);

        $user = User::find($id);

        if (!$user) {
            return $this->error('User not found', 404);
        }

        $user->update([
            'wallet_balance' => $validated['wallet_balance'],
        ]);

        return $this->ok('User wallet adjusted successfully', [
            'user' => [
                'id' => $user->id,
                'wallet_balance' => $user->wallet_balance,
            ]
        ]);
    }

    public function depositWallet(Request $request, $id)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $user = User::find($id);

        if (!$user) {
            return $this->error('User not found', 404);
        }

        $user->increment('wallet_balance', $validated['amount']);

        return $this->ok('Amount deposited successfully', [
            'user' => [
                'id' => $user->id,
                'wallet_balance' => $user->wallet_balance,
            ]
        ]);
    }

    public function adjustPoints(Request $request, $id)
    {
        $validated = $request->validate([
            'points_balance' => 'required|numeric',
        ]);

        $user = User::find($id);

        if (!$user) {
            return $this->error('User not found', 404);
        }

        $user->update([
            'points_balance' => $validated['points_balance'],
        ]);

        return $this->ok('User points adjusted successfully', [
            'user' => [
                'id' => $user->id,
                'points_balance' => $user->points_balance,
            ]
        ]);
    }
}