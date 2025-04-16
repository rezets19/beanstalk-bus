<?php

namespace Tests\Unit\common;

use bus\handler\Handler;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionException;
use Tests\Unit\TEvent;
use Tests\Unit\TEventHandler;

class HandlerTest extends TestCase
{
    private Handler $handler;

    private MockObject|LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->handler = new Handler($this->logger);

        restore_error_handler();
    }

    public function testClassNotFound(): void
    {
        $this->expectException(ReflectionException::class);

        $event = new TEvent();
        $event->setId(1);

        $this->handler->handle($event, [['a', 'handle']]);
    }

    public function testExecutedOk(): void
    {
        $this->expectException(Exception::class);

        $event = new TEvent();
        $event->setId(1);

        $this->handler->call($event, TEventHandler::class, 'handle');
    }

    public function testExecutedOkFunction(): void
    {
        $this->expectException(Exception::class);

        $event = new TEvent();
        $event->setId(1);

        $this->handler->call($event, function () { return new TEventHandler(); }, 'handle');
    }
}
