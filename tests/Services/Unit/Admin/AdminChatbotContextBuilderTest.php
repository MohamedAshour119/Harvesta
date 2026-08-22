<?php

namespace Tests\Services\Unit\Admin;

use App\Http\Services\AdminChatbotContextBuilder;
use App\Http\Services\AdminDataQueryService;
use App\Http\Services\AdminIntentRouter;
use Tests\Support\ServiceSmokeTestCase;

class AdminChatbotContextBuilderTest extends ServiceSmokeTestCase
{
    protected function serviceClass(): string
    {
        return AdminChatbotContextBuilder::class;
    }

    public function test_build_system_prompt_includes_primary_relevant_and_snapshot_sections(): void
    {
        $intentRouter = $this->mockDependency(AdminIntentRouter::class);
        $dataService = $this->mockDependency(AdminDataQueryService::class);

        $intentRouter->shouldReceive('route')
            ->once()
            ->with('Show top customers today')
            ->andReturn([
                'intent' => 'top_customers',
                'data' => [['customer_name' => 'Acme']],
                'data_label' => 'Top Customers',
            ]);

        $dataService->shouldReceive('getRelevantContext')
            ->once()
            ->with('Show top customers today', 'today')
            ->andReturn([
                'matched_customers' => [['name' => 'Acme']],
            ]);

        $dataService->shouldReceive('getPlatformSnapshot')
            ->once()
            ->with('today')
            ->andReturn([
                'sales_overview' => ['total_revenue' => 1500],
            ]);

        $builder = $this->instantiateService($this->serviceClass());
        $result = $builder->buildSystemPrompt('Show top customers today', 'Mona');

        $this->assertSame('top_customers', $result['intent']);
        $this->assertStringContainsString('You serve Mona', $result['system_prompt']);
        $this->assertStringContainsString('PRIMARY QUERY DATA:', $result['system_prompt']);
        $this->assertStringContainsString('Top Customers:', $result['system_prompt']);
        $this->assertStringContainsString('RELEVANT ENTITY DATA:', $result['system_prompt']);
        $this->assertStringContainsString('Matched customers:', $result['system_prompt']);
        $this->assertStringContainsString('BROADER PLATFORM SNAPSHOT:', $result['system_prompt']);
        $this->assertStringContainsString('Platform Snapshot:', $result['system_prompt']);
    }
}
