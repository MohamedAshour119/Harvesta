<?php

namespace App\Http\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class ManualOrderApprovalService
{
    public function __construct(protected InventoryAllocationService $inventoryAllocationService)
    {
    }

    public function approve(Order $order, User $approver, ?string $reviewNotes = null): Order
    {
        return DB::transaction(function () use ($order, $approver, $reviewNotes) {
            $lockedOrder = Order::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($order->id);

            $this->ensurePendingManualSale($lockedOrder);

            $this->inventoryAllocationService->allocate(
                $lockedOrder->items->map(fn ($item) => [
                    'productVariantId' => $item->product_variant_id,
                    'quantity' => $item->quantity,
                ])->all()
            );

            $lockedOrder->forceFill([
                'manual_sale_status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $approver->id,
                'review_notes' => $reviewNotes,
                'status' => 'delivered',
                'payment_status' => 'completed',
                'invoice_uuid' => $lockedOrder->invoice_uuid ?: (string) Str::uuid(),
            ])->save();

            return $lockedOrder->refresh();
        });
    }

    public function reject(Order $order, User $approver, ?string $reviewNotes = null): Order
    {
        return DB::transaction(function () use ($order, $approver, $reviewNotes) {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            $this->ensurePendingManualSale($lockedOrder);

            $lockedOrder->forceFill([
                'manual_sale_status' => 'rejected',
                'approved_at' => now(),
                'approved_by' => $approver->id,
                'review_notes' => $reviewNotes,
                'status' => 'cancelled',
            ])->save();

            return $lockedOrder->refresh();
        });
    }

    private function ensurePendingManualSale(Order $order): void
    {
        if (!$order->isManualSale() || $order->manual_sale_status !== 'submitted') {
            throw ValidationException::withMessages([
                'order' => 'This order is not a submitted manual sale.',
            ]);
        }
    }
}
