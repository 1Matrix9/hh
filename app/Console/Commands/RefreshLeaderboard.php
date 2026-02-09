<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Leaderboard;
use Illuminate\Support\Facades\DB;

class RefreshLeaderboard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leaderboard:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate and refresh the leaderboard rankings from user points';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Refreshing leaderboard...');

        $users = User::orderByDesc('points_balance')->get(['id', 'points_balance']);

        $prevPoints = null;
        $rank = 0;

        DB::beginTransaction();
        try {
            foreach ($users as $u) {
                if ($prevPoints === null || $u->points_balance !== $prevPoints) {
                    $rank++;
                }

                Leaderboard::updateOrCreate(
                    ['user_id' => $u->id],
                    ['points' => $u->points_balance ?? 0, 'rank' => $rank, 'updated_at' => now()]
                );

                $prevPoints = $u->points_balance;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Failed to refresh leaderboard: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info('Leaderboard refreshed (' . $users->count() . ' users).');
        return Command::SUCCESS;
    }
}
