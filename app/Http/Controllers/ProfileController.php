<?php

namespace App\Http\Controllers;

use App\Http\Services\ProfileService;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use HttpResponses;

    public function __construct(protected ProfileService $profileService) {}

    public function show()
    {
        try {
            $data = $this->profileService->getProfileData();
            return $this->response_success($data, 'Profile data retrieved successfully');
        } catch (\Exception $e) {
            return $this->response_error($e->getMessage(), [], 500);
        }
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
        ]);

        try {
            $user = $this->profileService->updateProfile($validated);
            return $this->response_success($user, 'Profile updated successfully');
        } catch (\Exception $e) {
            return $this->response_error($e->getMessage(), [], 500);
        }
    }

    public function destroy()
    {
        try {
            $this->profileService->deleteAccount();
            return $this->response_success(null, 'Account deleted successfully');
        } catch (\Exception $e) {
            return $this->response_error($e->getMessage(), [], 500);
        }
    }

    public function orders(Request $request)
    {
        try {
            $perPage = (int) $request->query('per_page', 10);
            $data = $this->profileService->getPaginatedOrders($perPage);
            return $this->response_success($data, 'Orders retrieved successfully');
        } catch (\Exception $e) {
            return $this->response_error($e->getMessage(), [], 500);
        }
    }

    public function invoice(int $order)
    {
        try {
            $result = $this->profileService->downloadInvoice($order);

            if (!$result) {
                return $this->response_error('Order not found or unauthorized', [], 404);
            }

            return $result['pdf']->download($result['filename']);
        } catch (\Exception $e) {
            return $this->response_error($e->getMessage(), [], 500);
        }
    }

    public function agentActivity(Request $request)
    {
        try {
            $perPage = (int) $request->query('per_page', 10);
            $data = $this->profileService->getAgentActivity($perPage);

            if ($data === null) {
                return $this->response_error('Agent access required', [], 403);
            }

            return $this->response_success($data, 'Agent activity retrieved successfully');
        } catch (\Exception $e) {
            return $this->response_error($e->getMessage(), [], 500);
        }
    }
}
