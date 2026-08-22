<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AgentDashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $monthlyTarget = (float) ($this->resource['monthly_target'] ?? 0);
        $monthlyProgress = (float) ($this->resource['monthly_target_progress'] ?? 0);
        $monthlyProgressPercent = $monthlyTarget > 0
            ? round(($monthlyProgress / $monthlyTarget) * 100, 2)
            : 0;


        return [
            'total_commission_this_month' => $this->resource['total_commission_this_month'] ?? 0,
            'pending_commissions' => $this->resource['pending_commissions'] ?? 0,
            'total_commission_last_month' => $this->resource['total_commission_last_month'] ?? 0,
            'percent_change' => $this->resource['percent_change'],
            'top_commission_source' => [
                'name' => $this->resource['top_commission_source']['name'] ?? null,
                'commission' => $this->resource['top_commission_source']['commission'] ?? 0,
            ],
            'monthly_target' => $monthlyTarget,
            'monthly_target_progress' => $monthlyProgress,
            'monthly_target_progress_percent' => $monthlyProgressPercent,
            'sales_performance' => $this->resource['sales_performance'] ?? [],
            'new_clients' => $this->resource['new_clients'] ?? 0,
            'repeat_buyers' => $this->resource['repeat_buyers'] ?? 0,
            'conversion_rate' => $this->resource['conversion_rate'] ?? 0,
            'top_selling_categories' => $this->resource['top_selling_categories'] ?? [],
        ];
    }
}
