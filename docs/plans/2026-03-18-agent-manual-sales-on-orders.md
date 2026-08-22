# Agent Manual Sales on Existing Orders Implementation Plan

> **For Antigravity:** REQUIRED WORKFLOW: Use `.agent/workflows/execute-plan.md` to execute this plan in single-flow mode.

**Goal:** Let sales representatives record field sales using the existing `orders` and `order_items` tables, require one exterior product photo as visit proof, and issue a printable invoice link after admin approval while making approved sales affect stock and existing agent metrics.

**Architecture:** Reuse `orders` as the single sales table. Agent-submitted field sales are created as manual-source draft orders with their items stored in `order_items`; admin approval transitions them into finalized delivered/completed orders, triggers inventory allocation, and sends the invoice link. This keeps all reporting, commission, and target logic centered on `orders` instead of introducing a second sales model.

**Tech Stack:** Laravel 12, Sanctum, Filament 3.3, React 19 + TypeScript, Spatie Media Library, MySQL, i18next.

---

### Task 1: Extend The Existing Order Model For Manual Sales

**Files:**
- Create: `database/migrations/*_add_manual_sale_fields_to_orders_table.php`
- Modify: `app/Models/Order.php`

**Step 1: Write the failing test**
- Add coverage for order manual-sale scopes, casts, and media collection registration.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=Order`

**Step 3: Write minimal implementation**
- Add manual-sale lifecycle columns to `orders`.
- Update `Order` to implement Spatie media support and manual-sale helpers/scopes.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=Order`

**Step 5: Commit**
- Commit after verification.

### Task 2: Shared Inventory Allocation Service

**Files:**
- Create: `app/Http/Services/InventoryAllocationService.php`
- Modify: `app/Http/Services/StripePaymentService.php`
- Modify: `app/Http/Services/TapPaymentService.php`

**Step 1: Write the failing test**
- Add coverage for shared inventory allocation and insufficient stock failure.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=InventoryAllocation`

**Step 3: Write minimal implementation**
- Extract stock deduction into `InventoryAllocationService::allocate()`.
- Update Stripe and Tap services to delegate to it.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=InventoryAllocation`

**Step 5: Commit**
- Commit after verification.

### Task 3: Agent Manual-Order Submission API

**Files:**
- Create: `app/Http/Requests/AgentManualOrderStoreRequest.php`
- Create: `app/Http/Resources/AgentManualOrderResource.php`
- Create: `app/Http/Resources/AgentManualOrderListResource.php`
- Create: `app/Http/Controllers/AgentManualOrderController.php`
- Create: `app/Http/Services/AgentManualOrderService.php`
- Modify: `routes/api.php`

**Step 1: Write the failing test**
- Add coverage for submit/list/show/lookups endpoints.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=AgentManualOrderSubmissionTest`

**Step 3: Write minimal implementation**
- Add agent-only routes and multipart submission.
- Persist manual orders inside `orders` and `order_items`.
- Support proof photo upload and existing/guest customer flows.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=AgentManualOrderSubmissionTest`

**Step 5: Commit**
- Commit after verification.

### Task 4: Admin Review And Approval On OrderResource

**Files:**
- Create: `app/Http/Services/ManualOrderApprovalService.php`
- Modify: `app/Filament/Resources/OrderResource.php`
- Modify: `app/Filament/Resources/OrderResource/Pages/EditOrder.php`
- Modify: `app/Models/Order.php`

### Task 5: Agent Metrics And Job Integration

**Files:**
- Modify: `app/Models/Order.php`
- Modify: `app/Jobs/UpdateAgentMonthlyAchievedAmount.php`
- Modify: `app/Http/Repositories/AgentDashboardRepository.php`

### Task 6: Invoice Rendering And Email Delivery From Order

**Files:**
- Create: `app/Http/Controllers/ManualOrderInvoiceController.php`
- Create: `app/Mail/ManualOrderApprovedInvoiceMail.php`
- Create: `resources/views/invoices/manual-order.blade.php`
- Create: `resources/views/emails/manual_order_invoice.blade.php`
- Modify: `routes/web.php`

### Task 7: Agent Dashboard UI

**Files:**
- Modify: `resources/frontend/src/App.tsx`
- Modify: `resources/frontend/src/pages/Dashboard/components/Sidebar.tsx`
- Modify: `resources/frontend/src/ApiClient.ts`
- Modify: `resources/frontend/src/types.ts`
- Modify: `resources/frontend/src/i18n/locales/en/dashboard.json`
- Modify: `resources/frontend/src/i18n/locales/ar/dashboard.json`
- Create: `resources/frontend/src/pages/Dashboard/pages/FieldSales/FieldSales.tsx`
- Create: `resources/frontend/src/pages/Dashboard/pages/FieldSales/components/ManualOrderForm.tsx`
- Create: `resources/frontend/src/pages/Dashboard/pages/FieldSales/components/ManualOrderItemsTable.tsx`
- Create: `resources/frontend/src/pages/Dashboard/pages/FieldSales/components/ManualOrdersHistory.tsx`

### Task 8: Checkout Isolation And Regression Safety

**Files:**
- Modify: `app/Http/Controllers/PaymentController.php`
- Modify: `app/Http/Requests/CreateOrderRequest.php`
- Modify: `app/Filament/Resources/OrderResource.php`
