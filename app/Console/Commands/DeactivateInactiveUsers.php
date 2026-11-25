<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DeactivateInactiveUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:deactivate-inactive {--months=6 : Number of inactive months before deactivation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically mark users as Inactive if they have not logged into the website for the last 6 months';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $months = (int) $this->option('months') ?: 6;
        $this->info("Scanning users with no login activity for {$months}+ months...");

        if (method_exists(User::class, 'deactivateInactiveUsers')) {
            $count = User::deactivateInactiveUsers($months);
        } else {
            $cutoff = now()->subMonths($months);
            $count = User::where('status', 1)
                ->where(function ($q) {
                    $q->whereNull('role_id')->orWhere('role_id', '!=', 1);
                })
                ->where('created_at', '<', $cutoff)
                ->update(['status' => 0]);
        }

        $this->info("Complete! {$count} user(s) were automatically marked as Inactive.");

        return Command::SUCCESS;
    }
}

