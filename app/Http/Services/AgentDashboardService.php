<?php

namespace App\Http\Services;

use App\Http\Repositories\AgentDashboardRepository;
use App\Http\Resources\AgentDashboardResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class AgentDashboardService
{
    protected $agentDashboardRepository;

    public function __construct(AgentDashboardRepository $agentDashboardRepository)
    {
        $this->agentDashboardRepository = $agentDashboardRepository;
    }

    public function dashboardInfo()
    {
        $data = $this->agentDashboardRepository->dashboardInfo();
        $data = AgentDashboardResource::make($data);
        return $data;
    }

    public function salesPerformance($year)
    {
        $data = $this->agentDashboardRepository->salesPerformance($year);
        return $data;
    }

    public function recentOrders($request)
    {
        $perPage = $request->query('per_page', 10);
        $data = $this->agentDashboardRepository->recentOrders((int) $perPage);
        return $data;
    }

    public function commissionAndBonus()
    {
        $data = $this->agentDashboardRepository->commissionAndBonus();
        return $data;
    }

    public function commissionsTimeline()
    {
        $data = $this->agentDashboardRepository->commissionsTimeline();
        return $data;
    }
}
