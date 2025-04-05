<?php

namespace Tests\Unit;

use bus\broker\BrokerFactory;
use bus\broker\Bury;
use bus\common\Restarter;
use bus\config\ConfigNotFoundException;
use bus\consumer\Consumer;
use bus\exception\BrokerNotFoundException;
use bus\exception\HandlerNotFoundException;
use bus\Listener;
use Exception;
use Pheanstalk\Contract\PheanstalkManagerInterface;
use Pheanstalk\Contract\PheanstalkPublisherInterface;
use Pheanstalk\Contract\PheanstalkSubscriberInterface;
use Pheanstalk\Pheanstalk;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionException;
use Throwable;
use Zfekete\BypassReadonly\BypassReadonly;

class ListenerTest extends TestCase
{
    use PrivateProperty;

    private LoggerInterface|MockObject $logger;
    private BrokerFactory|MockObject $brokerFactory;
    private Restarter|MockObject $restarter;
    private Bury|MockObject $bury;
    private Consumer|MockObject $consumer;
    private Pheanstalk|MockObject $broker;

    protected function setUp(): void
    {
        BypassReadonly::enable();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->brokerFactory = $this->createMock(BrokerFactory::class);
        $this->restarter = $this->createMock(Restarter::class);
        $this->bury = $this->createMock(Bury::class);
        $this->consumer = $this->createMock(Consumer::class);
        $this->broker = $this->createMockForIntersectionOfInterfaces([
            PheanstalkManagerInterface::class,
            PheanstalkPublisherInterface::class,
            PheanstalkSubscriberInterface::class
        ]);

        $this->listener = new Listener(
            'unit',
            $this->logger,
            $this->brokerFactory,
            $this->restarter,
            $this->bury,
            $this->consumer
        );
    }

    /**
     * @return void
     * @throws ReflectionException
     * @throws Throwable
     * @throws ConfigNotFoundException
     * @throws BrokerNotFoundException
     * @throws HandlerNotFoundException
     */
    public function testListenAndRestart(): void
    {
        $this->expectException(Exception::class);
        $this->restarter->expects(self::once())->method('restart')->willReturn(true);

        $this->listener->consume($this->broker);
    }
}
