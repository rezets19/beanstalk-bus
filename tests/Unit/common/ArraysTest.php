<?php

namespace Tests\Unit\common;

use bus\common\Arrays;
use bus\exception\BrokerNotFoundException;
use PHPUnit\Framework\TestCase;

class ArraysTest extends TestCase
{
    private Arrays $arrays;

    protected function setUp(): void
    {
        $this->arrays = new Arrays();
    }

    public function test_class_exist_true(): void
    {
        self::assertTrue($this->arrays->classExist(new \Exception(), [\Exception::class]));
    }

    public function test_class_exist_true_2(): void
    {
        self::assertTrue($this->arrays->classExist(new \bus\exception\MemoryLimitException(), [\bus\exception\MemoryLimitException::class]));
    }

    public function test_class_exist_false(): void
    {
        self::assertFalse($this->arrays->classExist(new \InvalidArgumentException(), [\Exception::class]));
    }
}
