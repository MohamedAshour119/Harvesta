<?php

namespace App\Http\Services;

use App\Http\Repositories\ProfileRepository;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ProfileService
{
    public function __construct(
        protected ProfileRepository $profileRepository,
        protected InvoiceService $invoiceService
    ) {}

    public function getProfileData(): array
    {
        $user = Auth::user();

        $userData = [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'is_agent' => (bool) $user->is_agent,
            'code' => $user->code,
            'commission' => $user->commission,
            'monthly_target' => $user->monthly_target,
            'created_at' => $user->created_at->format('Y-m-d H:i:s'),
            'last_login_at' => $user->last_login_at?->format('Y-m-d H:i:s'),
        ];

        $customerSummary = $this->profileRepository->getCustomerSummary($user->id);
        $recentOrders = $this->profileRepository->getRecentOrders($user->id);

        $agentSummary = null;
        if ($user->is_agent && $user->code) {
            $agentSummary = $this->profileRepository->getAgentSummary(
                $user->code,
                (float) $user->commission,
                $user->id
            );
        }

        return [
            'user' => $userData,
            'customer_summary' => $customerSummary,
            'recent_orders' => $recentOrders,
            'agent_summary' => $agentSummary,
        ];
    }

    public function updateProfile(array $data): array
    {
        $user = Auth::user();
        $user->update(['full_name' => $data['full_name']]);

        return [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'is_agent' => (bool) $user->is_agent,
            'code' => $user->code,
            'commission' => $user->commission,
            'monthly_target' => $user->monthly_target,
            'created_at' => $user->created_at->format('Y-m-d H:i:s'),
            'last_login_at' => $user->last_login_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function deleteAccount(): void
    {
        $user = Auth::user();
        $user->tokens()->delete();
        $user->delete();
    }

    public function getPaginatedOrders(int $perPage = 10)
    {
        return $this->profileRepository->getPaginatedOrders(Auth::id(), $perPage);
    }

    public function getAgentActivity(int $perPage = 10)
    {
        $user = Auth::user();
        if (!$user->is_agent || !$user->code) {
            return null;
        }
        return $this->profileRepository->getAgentActivity($user->code, $perPage);
    }

    public function downloadInvoice(int $orderId)
    {
        $user = Auth::user();

        $order = $this->profileRepository->findOrderForUser($orderId, $user->id);

        if (!$order && $user->is_agent && $user->code) {
            $order = $this->profileRepository->findOrderForAgent($orderId, $user->code);
        }

        if (!$order) {
            return null;
        }

        return [
            'pdf' => $this->invoiceService->generatePdf($order),
            'filename' => $this->invoiceService->getFileName($order),
        ];
    }
}
