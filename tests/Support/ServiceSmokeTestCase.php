<?php

namespace Tests\Support;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\Concerns\InstantiatesServices;
use Tests\TestCase;

abstract class ServiceSmokeTestCase extends TestCase
{
    use InstantiatesServices;
    use MockeryPHPUnitIntegration;

    abstract protected function serviceClass(): string;

    public function test_service_can_be_instantiated(): void
    {
        $serviceClass = $this->serviceClass();
        $service = $this->instantiateService($serviceClass);

        $this->assertInstanceOf($serviceClass, $service);
    }
}
