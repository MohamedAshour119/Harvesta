<?php

namespace Tests\Concerns;

use Mockery;
use ReflectionClass;
use ReflectionNamedType;

trait InstantiatesServices
{
    /**
     * @var array<string, object>
     */
    private array $serviceDependencies = [];

    protected function instantiateService(string $serviceClass): object
    {
        $reflection = new ReflectionClass($serviceClass);
        $constructor = $reflection->getConstructor();

        if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
            return $reflection->newInstance();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                $arguments[] = $this->mockDependency($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            $this->fail("Unable to auto-instantiate [{$serviceClass}] because parameter [{$parameter->getName()}] is unsupported.");
        }

        return $reflection->newInstanceArgs($arguments);
    }

    protected function mockDependency(string $dependencyClass): object
    {
        if (! array_key_exists($dependencyClass, $this->serviceDependencies)) {
            $this->serviceDependencies[$dependencyClass] = Mockery::mock($dependencyClass);
        }

        return $this->serviceDependencies[$dependencyClass];
    }
}
