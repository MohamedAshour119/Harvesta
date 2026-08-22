<?php

namespace Tests\Repositories\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentCodeGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_code_is_auto_generated_when_left_blank(): void
    {
        $agent = User::factory()->create([
            'is_agent' => true,
            'code' => null,
        ]);

        $this->assertNotNull($agent->code);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $agent->code,
        );
    }

    public function test_existing_agent_gets_a_generated_code_when_saved_with_blank_code(): void
    {
        $agent = User::factory()->create([
            'is_agent' => true,
            'code' => '867df5fb-253d-3369-a782-313f9e87e3d0',
        ]);

        $agent->code = null;
        $agent->save();

        $this->assertNotNull($agent->fresh()->code);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $agent->fresh()->code,
        );
    }

    public function test_non_agents_do_not_keep_an_agent_code(): void
    {
        $user = User::factory()->create([
            'is_agent' => false,
            'code' => '867df5fb-253d-3369-a782-313f9e87e3d0',
        ]);

        $this->assertNull($user->fresh()->code);
    }
}
