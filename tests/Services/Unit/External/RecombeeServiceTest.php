<?php

namespace Tests\Services\Unit\External;

use App\Http\Services\RecombeeService;
use Recombee\RecommApi\Client;
use Recombee\RecommApi\Requests\RecommendItemsToUser;
use ReflectionProperty;
use Tests\Support\ServiceSmokeTestCase;

class RecombeeServiceTest extends ServiceSmokeTestCase
{
    protected function serviceClass(): string
    {
        return RecombeeService::class;
    }

    public function test_get_client_returns_null_when_configuration_is_missing(): void
    {
        config([
            'services.recombee.database_id' => null,
            'services.recombee.private_token' => null,
        ]);

        $service = new RecombeeService();

        $this->assertNull($service->getClient());
        $this->assertSame([], $service->recommendItemsToUser(5));
    }

    public function test_recommend_items_to_user_returns_client_response_when_client_is_available(): void
    {
        $client = \Mockery::mock(Client::class);
        $client->shouldReceive('send')
            ->once()
            ->with(\Mockery::type(RecommendItemsToUser::class))
            ->andReturn(['recomms' => [['id' => '11']]]);

        $service = new RecombeeService();
        $property = new ReflectionProperty($service, 'client');
        $property->setAccessible(true);
        $property->setValue($service, $client);

        $this->assertSame(['recomms' => [['id' => '11']]], $service->recommendItemsToUser(9, 3));
    }
}
