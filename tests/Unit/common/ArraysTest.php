<?php

namespace Tests\Unit\common;

use bus\common\Arrays;
use bus\exception\MemoryLimitException;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ArraysTest extends TestCase
{
    private Arrays $arrays;

    protected function setUp(): void
    {
        $this->arrays = new Arrays();
    }

    public function testPhpClassExists(): void
    {
        self::assertTrue($this->arrays->classExist(new Exception(), [Exception::class]));
    }

    public function testCustomClassExists(): void
    {
        self::assertTrue($this->arrays->classExist(new MemoryLimitException(), [MemoryLimitException::class]));
    }

    public function testClassDoesntExist(): void
    {
        self::assertFalse($this->arrays->classExist(new InvalidArgumentException(), [Exception::class]));
    }
}
