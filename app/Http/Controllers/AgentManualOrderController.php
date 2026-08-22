<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgentManualOrderStoreRequest;
use App\Http\Resources\AgentManualOrderListResource;
use App\Http\Resources\AgentManualOrderResource;
use App\Http\Services\AgentManualOrderService;
use App\Models\Order;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;

class AgentManualOrderController extends Controller
{
    use HttpResponses;

    public function __construct(protected AgentManualOrderService $manualOrderService)
    {
    }

    public function index(Request $request)
    {
        $orders = $this->manualOrderService->listForAgent($request->user(), 3);

        return $this->response_success([
            'data' => AgentManualOrderListResource::collection($orders),
        ], 'Manual orders retrieved successfully.');
    }

    public function store(AgentManualOrderStoreRequest $request)
    {
        $order = $this->manualOrderService->store($request);

        return $this->response_success(new AgentManualOrderResource($order), 'Manual order submitted successfully.', 201);
    }

    public function show(Request $request, Order $order)
    {
        $order = $this->manualOrderService->findForAgent($request->user(), $order);

        return $this->response_success(new AgentManualOrderResource($order), 'Manual order retrieved successfully.');
    }

    public function customerLookup(Request $request)
    {
        $results = $this->manualOrderService->customerLookup((string) $request->query('search', ''));

        return $this->response_success($results, 'Customers retrieved successfully.');
    }

    public function productLookup(Request $request)
    {
        $results = $this->manualOrderService->productLookup((string) $request->query('search', ''));

        return $this->response_success($results, 'Products retrieved successfully.');
    }
}
