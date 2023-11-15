<?php

namespace Tests\Unit\config;

use bus\broker\BuryStrategy;
use bus\config\FConfigDto;
use bus\impl\TEventHandler;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class FConfigDtoTest extends TestCase
{
    private FConfigDto $factory;

    public function setUp(): void
    {
        $this->factory = new FConfigDto();
    }

    public function test_from_result(): void
    {
        $dto = $this->factory->fromResult($this->getConfig(), '\namespace\ClassName');

        $this->assertSame('\namespace\ClassName', $dto->getClass());
        $this->assertSame(true, $dto->isAsync());
        $this->assertSame(false, $dto->isCritical());
        $this->assertSame([[TEventHandler::class, 'handle2']], $dto->getHandlers());
        $this->assertSame(1, $dto->getMaxKicks());
        $this->assertSame(120, $dto->getMaxAge());
        $this->assertSame(5, $dto->getDelay());
        $this->assertSame(0, $dto->getPriority());
        $this->assertSame(1, $dto->getMaxRetry());
        $this->assertSame('test', $dto->getQueue());
        $this->assertSame(100, $dto->getTtr());
        $this->assertSame('beanstalk', $dto->getDriver());
        $this->assertSame(BuryStrategy::class, $dto->getBuryStrategy());

        $this->assertSame([Exception::class], $dto->getFatal());
    }

    private function getConfig(): array
    {
        $test_queue = [
            'name' => 'test',
            'driver' => 'beanstalk',
            'delay' => 5,
            'ttr' => 100,
            'maxRetry' => 1,
            'maxAge' => 120,
            'maxKicks' => 1,
            'buryStrategy' => BuryStrategy::class,
        ];

        return [
            'async' => true,
            'queue_config' => $test_queue,
            'handlers' => [
                [TEventHandler::class, 'handle2'],
            ],
            'exceptions' => [
                'fatal' => [
                    Exception::class,
                ],
                'repeatable' => [
                    InvalidArgumentException::class
                ],
            ],
        ];
    }
}
