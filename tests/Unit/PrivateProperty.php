<?php

namespace Tests\Unit;

use ReflectionClass;

trait PrivateProperty
{
    public function setMockPropertyObj(object $instance, string $property, mixed $value): void
    {
        $reflectionClass = new ReflectionClass($instance);
        $reflectionProperty = $reflectionClass->getProperty($property);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($instance, $value);
    }
}
