<?php

namespace App\Filament\Resources\AgentResource\Widgets;

use App\Http\Repositories\AgentDashboardRepository;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $agentDashboardRepository = new AgentDashboardRepository();
        $totalDeliveredCommissions = $agentDashboardRepository->getTotalDeliveredCommissions();
        $totalPendingCommissions = $agentDashboardRepository->getTotalPendingCommissions();
        $totalSales = $agentDashboardRepository->getTotalSales();
        $topSellingCategory = $agentDashboardRepository->getTopSellingCategory();
        $totalOrdersCount = $agentDashboardRepository->getTotalOrdersCount();

        return [
            Stat::make(__('translations.total_sales'), number_format($totalSales, 2) .' ' . __('translations.currency')),
            Stat::make(__('translations.total_delivered_commissions'), number_format($totalDeliveredCommissions, 2) .' ' . __('translations.currency')),
            Stat::make(__('translations.total_pending_commissions'), number_format($totalPendingCommissions, 2) .' ' . __('translations.currency')),
            Stat::make(__('translations.top_selling_category'), $topSellingCategory ? $topSellingCategory->category_name : __('translations.n_a'))
                ->description($topSellingCategory ? $topSellingCategory->order_count . ' ' . __('translations.orders') : null),
            Stat::make(__('translations.orders_count'), $totalOrdersCount),
        ];
    }
}
