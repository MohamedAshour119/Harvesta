<?php

namespace App\Console\Commands;

use App\Models\AgentMonthlyTarget;
use App\Models\Bonus;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckAgentMonthlyTargets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agents:check-monthly-targets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if agents achieved monthly targets and reset achieved_amount for the next cycle';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = Carbon::now();

        AgentMonthlyTarget::query()->chunkById(200, function ($targets) use ($now) {
            foreach ($targets as $target) {
                // Initialize reset_at if missing
                if (empty($target->reset_at)) {
                    $target->reset_at = $now->copy()->startOfMonth()->toDateString();
                    $target->save();
                    continue;
                }

                $resetDate = Carbon::parse($target->reset_at);

                info('resetDate: ' . $resetDate->toDateString());
                info('resetDate->endOfDay: ' . $resetDate->endOfDay()->toDateString());
                info('now: ' . $now->greaterThanOrEqualTo($resetDate->endOfDay()));

                // Run only on or after reset date
                if ($now->toDateString() >= $resetDate->toDateString()) {
                    $achieved = (float) ($target->achieved_amount ?? 0);
                    $goal = (float) ($target->target_amount ?? 0);

                    $achievedStatus = $goal > 0 && $achieved >= $goal ? 'achieved' : 'not_achieved';

                    Log::info('Agent monthly target check', [
                        'agent_id' => $target->agent_id,
                        'achieved_amount' => $achieved,
                        'target_amount' => $goal,
                        'status' => $achievedStatus,
                        'checked_at' => $now->toDateTimeString(),
                    ]);

                    if ($achievedStatus === 'achieved' && (float)$target->bonus_amount > 0) {
                        Bonus::create([
                            'user_id' => $target->agent_id,
                            'amount' => $target->bonus_amount,
                            'paid_at' => $now->toDateTimeString(),
                        ]);
                        
                        $target->bonus = $target->bonus_amount;
                    } else {
                        $target->bonus = 0.00;
                    }
                    $target->save();

                    // Reset achieved and set next reset date to end of current month
                    $target->achieved_amount = 0;
                    $target->reset_at = $now->copy()->endOfMonth()->toDateString();
                    $target->save();
                }
            }
        });

        $this->info('Monthly targets checked.');
        return self::SUCCESS;
    }
}
