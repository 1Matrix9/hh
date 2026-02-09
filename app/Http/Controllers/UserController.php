<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Leaderboard;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
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

    /**
     * Admin: Recalculate and refresh the leaderboard from users' points.
     */
    public function refreshLeaderboard(Request $request)
    {
        $users = User::orderByDesc('points_balance')->get(['id', 'points_balance']);

        $prevPoints = null;
        $rank = 0;
        $updateat = date('Y-m-d H:i:s', time());
        foreach ($users as $u) {
            if ($prevPoints === null || $u->points_balance !== $prevPoints) {
                $rank++;
            }
            //dd($u->id, $u->points_balance, $rank, $updateat);

            Leaderboard::updateOrCreate(
                ['user_id' => $u->id],
                ['points' => $u->points_balance ?? 0, 'rank' => $rank, 'updated_at' => $updateat]
            );
            $prevPoints = $u->points_balance;
        }

        return $this->ok('Leaderboard refreshed successfully', [
            'users_processed' => $users->count(),
        ]);
    }

    /**
     * Return top 100 leaderboard entries (rank, points, and user info).
     */
    public function topLeaderboard(Request $request)
    {
        $rows = DB::table('leaderboards')
            ->join('users', 'leaderboards.user_id', '=', 'users.id')
            ->select(
                'users.id as user_id',
                'users.name',
                'users.email',
                'leaderboards.points',
                'leaderboards.rank',
                'leaderboards.updated_at'
            )
            ->orderBy('leaderboards.rank', 'asc')
            ->limit(100)
            ->get();

        return $this->ok('Top leaderboard retrieved successfully', [
            'leaderboard' => $rows,
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