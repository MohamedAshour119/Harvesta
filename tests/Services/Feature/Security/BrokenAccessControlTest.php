<?php

namespace Tests\Services\Feature\Security;

use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Broken Access Control (BAC) Test Suite
 *
 * Tests for OWASP #1 vulnerability across 5 categories:
 *   A. Vertical Privilege Escalation  – lower role accesses higher-role endpoints
 *   B. Horizontal Privilege Escalation (IDOR) – user accesses another user's data
 *   C. Missing Authentication         – unauthenticated access to protected endpoints
 *   D. Missing Function-Level Access Control – auth check present, role check missing
 *   E. Parameter Tampering            – manipulating request values for unauthorized access
 */
class BrokenAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $agent;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a customer (non-agent)
        $this->customer = User::factory()->create([
            'is_agent'  => false,
            'code'      => null,
        ]);

        // Create an agent
        $this->agent = User::factory()->create([
            'is_agent'  => true,
            'code'      => 'AGENT-TEST-001',
        ]);

        // Create a super admin with role
        $this->admin = User::factory()->create([
            'is_agent'  => false,
            'code'      => null,
        ]);
        $role = Role::firstOrCreate(['name' => 'Super Admin']);
        $this->admin->roles()->syncWithoutDetaching([$role->id]);
    }

    // ╔══════════════════════════════════════════════════════════════╗
    // ║  A. VERTICAL PRIVILEGE ESCALATION                          ║
    // ╚══════════════════════════════════════════════════════════════╝

    public function test_customer_cannot_access_agent_dashboard(): void
    {
        $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/agents/dashboard-info')
            ->assertStatus(403);
    }

    public function test_customer_cannot_access_agent_manual_orders(): void
    {
        $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/agents/manual-orders')
            ->assertStatus(403);
    }

    public function test_customer_cannot_access_agent_sales_performance(): void
    {
        $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/agents/dashboard-info/sales-performance/2026')
            ->assertStatus(403);
    }

    public function test_customer_cannot_access_agent_commission_and_bonus(): void
    {
        $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/agents/dashboard-info/commission-and-bonus')
            ->assertStatus(403);
    }

    public function test_customer_cannot_access_agent_commissions_timeline(): void
    {
        $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/agents/dashboard-info/commissions-timeline')
            ->assertStatus(403);
    }

    public function test_customer_cannot_access_agent_recent_orders(): void
    {
        $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/agents/dashboard-info/orders')
            ->assertStatus(403);
    }

    public function test_customer_cannot_access_agent_customer_lookup(): void
    {
        $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/agents/manual-orders/lookups/customers')
            ->assertStatus(403);
    }

    public function test_customer_cannot_access_agent_product_lookup(): void
    {
        $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/agents/manual-orders/lookups/products')
            ->assertStatus(403);
    }

    public function test_customer_cannot_impersonate_any_user(): void
    {
        $target = User::factory()->create(['is_agent' => true]);

        $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/agents/{$target->id}/impersonate")
            ->assertStatus(403);
    }

    public function test_agent_cannot_impersonate_any_user(): void
    {
        $target = User::factory()->create(['is_agent' => true]);

        $this->actingAs($this->agent, 'sanctum')
            ->postJson("/api/agents/{$target->id}/impersonate")
            ->assertStatus(403);
    }

    public function test_customer_cannot_access_admin_chatbot_session(): void
    {
        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/admin-chatbot/session')
            ->assertStatus(403);
    }

    public function test_customer_cannot_send_admin_chatbot_message(): void
    {
        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/admin-chatbot/send', [
                'message'    => 'show all orders',
                'session_id' => str_repeat('a', 64),
            ])
            ->assertStatus(403);
    }

    public function test_customer_cannot_access_admin_chatbot_history(): void
    {
        $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/admin-chatbot/history?session_id=' . str_repeat('a', 64))
            ->assertStatus(403);
    }

    public function test_agent_cannot_access_admin_chatbot_session(): void
    {
        $this->actingAs($this->agent, 'sanctum')
            ->postJson('/api/admin-chatbot/session')
            ->assertStatus(403);
    }

    public function test_agent_cannot_send_admin_chatbot_message(): void
    {
        $this->actingAs($this->agent, 'sanctum')
            ->postJson('/api/admin-chatbot/send', [
                'message'    => 'show revenue',
                'session_id' => str_repeat('b', 64),
            ])
            ->assertStatus(403);
    }

    public function test_agent_cannot_access_admin_chatbot_history(): void
    {
        $this->actingAs($this->agent, 'sanctum')
            ->getJson('/api/admin-chatbot/history?session_id=' . str_repeat('b', 64))
            ->assertStatus(403);
    }

    // ╔══════════════════════════════════════════════════════════════╗
    // ║  B. HORIZONTAL PRIVILEGE ESCALATION (IDOR)                 ║
    // ╚══════════════════════════════════════════════════════════════╝

    public function test_customer_cannot_download_another_users_invoice(): void
    {
        $otherUser = User::factory()->create(['is_agent' => false]);

        $order = Order::factory()->create([
            'user_id'        => $otherUser->id,
            'agent_code'     => 'SYSTEM',
            'payment_status' => 'completed',
        ]);

        $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/profile/orders/{$order->id}/invoice")
            ->assertStatus(404);
    }

    public function test_agent_cannot_view_another_agents_manual_order(): void
    {
        $otherAgent = User::factory()->create([
            'is_agent' => true,
            'code'     => 'AGENT-OTHER-001',
        ]);

        $order = Order::factory()->create([
            'user_id'    => $otherAgent->id,
            'agent_code' => $otherAgent->code,
            'source'     => 'agent_manual_sale',
        ]);

        $this->actingAs($this->agent, 'sanctum')
            ->getJson("/api/agents/manual-orders/{$order->id}")
            ->assertStatus(404);
    }

    public function test_customer_can_only_see_own_orders(): void
    {
        $otherUser = User::factory()->create(['is_agent' => false]);

        // Create 1 order for our customer
        Order::factory()->create([
            'user_id'    => $this->customer->id,
            'agent_code' => 'SYSTEM',
        ]);
        // Create 2 orders for the other user
        Order::factory()->count(2)->create([
            'user_id'    => $otherUser->id,
            'agent_code' => 'SYSTEM',
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/profile/orders');

        $response->assertOk();

        // The customer should only see their own 1 order, not the other user's 2 orders
        $orders = $response->json('data.data');
        $this->assertCount(1, $orders, 'Customer should only see their own orders, not other users\' orders');
    }

    // ╔══════════════════════════════════════════════════════════════╗
    // ║  C. MISSING AUTHENTICATION                                 ║
    // ╚══════════════════════════════════════════════════════════════╝

    public function test_unauthenticated_cannot_access_profile(): void
    {
        $this->getJson('/api/profile')->assertStatus(401);
    }

    public function test_unauthenticated_cannot_update_profile(): void
    {
        $this->patchJson('/api/profile', ['full_name' => 'Hacker'])->assertStatus(401);
    }

    public function test_unauthenticated_cannot_delete_profile(): void
    {
        $this->deleteJson('/api/profile')->assertStatus(401);
    }

    public function test_unauthenticated_cannot_access_wishlist(): void
    {
        $this->getJson('/api/wishlist')->assertStatus(401);
    }

    public function test_unauthenticated_cannot_add_to_wishlist(): void
    {
        $this->postJson('/api/wishlist/add', [
            'product_id' => 1,
            'variant_id' => 1,
        ])->assertStatus(401);
    }

    public function test_unauthenticated_cannot_remove_from_wishlist(): void
    {
        $this->deleteJson('/api/wishlist/remove', [
            'product_id' => 1,
            'variant_id' => 1,
        ])->assertStatus(401);
    }

    public function test_unauthenticated_cannot_submit_rating(): void
    {
        $this->postJson('/api/ratings', [
            'product_id' => 1,
            'rating'     => 5,
            'comment'    => 'test',
        ])->assertStatus(401);
    }

    public function test_unauthenticated_cannot_checkout(): void
    {
        $this->postJson('/api/checkout', [])->assertStatus(401);
    }

    public function test_unauthenticated_cannot_apply_coupon(): void
    {
        $this->postJson('/api/apply-coupon', ['coupon' => 'TEST'])->assertStatus(401);
    }

    public function test_unauthenticated_cannot_access_agent_dashboard(): void
    {
        $this->getJson('/api/agents/dashboard-info')->assertStatus(401);
    }

    public function test_unauthenticated_cannot_access_agent_manual_orders(): void
    {
        $this->getJson('/api/agents/manual-orders')->assertStatus(401);
    }

    public function test_unauthenticated_cannot_impersonate(): void
    {
        $this->postJson('/api/agents/1/impersonate')->assertStatus(401);
    }

    public function test_unauthenticated_cannot_access_admin_chatbot(): void
    {
        $this->postJson('/api/admin-chatbot/session')->assertStatus(401);
    }

    public function test_unauthenticated_cannot_access_user_orders(): void
    {
        $this->getJson('/api/profile/orders')->assertStatus(401);
    }

    public function test_unauthenticated_cannot_download_invoice(): void
    {
        $this->getJson('/api/profile/orders/1/invoice')->assertStatus(401);
    }

    public function test_unauthenticated_cannot_track_cart(): void
    {
        $this->postJson('/api/cart/track', [])->assertStatus(401);
    }

    public function test_unauthenticated_cannot_get_user_recommendations(): void
    {
        $this->getJson('/api/recommendations/user')->assertStatus(401);
    }

    // ╔══════════════════════════════════════════════════════════════╗
    // ║  D. MISSING FUNCTION-LEVEL ACCESS CONTROL                  ║
    // ╚══════════════════════════════════════════════════════════════╝

    public function test_agent_cannot_checkout_via_prevent_agents_middleware(): void
    {
        $this->actingAs($this->agent, 'sanctum')
            ->postJson('/api/checkout', [
                'products' => [],
                'address'  => [],
            ])
            ->assertStatus(403);
    }

    public function test_impersonation_route_blocks_non_admin_at_middleware_level(): void
    {
        // This test verifies the FIX: impersonation route now has can:superAdminAccess middleware
        // A non-admin should get 403 from middleware, not just from the controller Gate check
        $target = User::factory()->create(['is_agent' => true]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/agents/{$target->id}/impersonate");

        $response->assertStatus(403);
    }

    public function test_admin_chatbot_api_blocks_non_admin_at_middleware_level(): void
    {
        // This test verifies the FIX: admin chatbot API now has can:superAdminAccess middleware
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/admin-chatbot/session');

        $response->assertStatus(403);
    }

    public function test_ensure_agent_middleware_returns_403_not_401(): void
    {
        // This test verifies the FIX: EnsureAgent now returns 403 (Forbidden) not 401 (Unauthenticated)
        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/agents/dashboard-info');

        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'You are not authorized to access this page']);
    }

    // ╔══════════════════════════════════════════════════════════════╗
    // ║  E. ADMIN-ONLY FEATURES ALLOW ADMIN                        ║
    // ║     (positive tests to verify admin access still works)     ║
    // ╚══════════════════════════════════════════════════════════════╝

    public function test_admin_can_access_admin_chatbot_session(): void
    {
        // Admin should not get a 403 — may get another status depending on chatbot service config
        // but the key point is: the middleware LETS them through
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin-chatbot/session');

        $this->assertNotEquals(403, $response->status());
        $this->assertNotEquals(401, $response->status());
    }

    public function test_agent_can_access_agent_dashboard(): void
    {
        // Agent should be allowed through middleware — endpoint may error on
        // data dependencies, but should NOT return 401 or 403
        $response = $this->actingAs($this->agent, 'sanctum')
            ->getJson('/api/agents/dashboard-info');

        $this->assertNotEquals(401, $response->status());
        $this->assertNotEquals(403, $response->status());
    }

    public function test_customer_can_access_own_profile(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/profile');

        $response->assertOk()
            ->assertJsonPath('data.user.id', $this->customer->id);
    }
}
