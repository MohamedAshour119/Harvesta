<?php

namespace App\Jobs;

use App\Models\AgentMonthlyTarget;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateAgentMonthlyAchievedAmount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $orderId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $order = Order::find($this->orderId);
        if (!$order || empty($order->agent_code)) {
            return; // Nothing to update
        }

        $agent = User::where('is_agent', true)
            ->where('code', $order->agent_code)
            ->first();
        
        $agentCommission = $agent->commission;

        if (!$agent) {
            return;
        }

        // Calculate current month's achieved sales for this agent (paid orders)
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $achievedThisMonth = Order::where('agent_code', $order->agent_code)
            ->where('payment_status', 'completed')
            ->where('status', 'delivered')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('total');

        // Idempotent update/create of monthly target progress
        $target = AgentMonthlyTarget::firstOrNew([
            'agent_id' => $agent->id,
        ]);

        $target->achieved_amount = $achievedThisMonth * $agentCommission / 100;
        $target->reset_at = $endOfMonth->toDateString();
        $target->save();
    }
}
