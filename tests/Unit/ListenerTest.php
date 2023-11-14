<?php

namespace Tests\Unit;

/*use app\components\APMSenderInterface;
use base\interfaces\ConsoleCommandInterface;
use base\interfaces\MemoryLimitInterface;*/
use bus\broker\Bury;
use bus\broker\FBroker;
use bus\common\Restarter;
use bus\config\Connection;
use bus\config\Provider;
use bus\factory\FTags;
use bus\interfaces\APMSenderInterface;
use bus\Listener;
use bus\message\FQMessage;
use bus\message\Processor;
use bus\MessageBus;
use Exception;
use Pheanstalk\Pheanstalk;
use Pheanstalk\Values\Job;
use Pheanstalk\Values\JobId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Zfekete\BypassReadonly\BypassReadonly;

class ListenerTest extends TestCase
{
    use PrivateProperty;

    /**
     * @var Listener
     */
    private $listener;

    /**
     * @var MockObject|MessageBus
     */
    private $messageBus;

    /**
     * @var MockObject|Connection
     */
    private $connection;

    /**
     * @var MockObject|Provider
     */
    private $configProvider;

    /**
     * @var MockObject|Processor
     */
    private $processor;

    /**
     * @var MockObject|FBroker
     */
    private $fbroker;

    /**
     * @var MockObject|LoggerInterface
     */
    private $logger;

    /**
     * @var MockObject|FQMessage
     */
    private $messageFactory;

    /**
     * @var MockObject|Restarter
     */
    private $restarter;

    /**
     * @var MockObject|Bury
     */
    private $bury;

    /**
     * @var MockObject|Pheanstalk
     */
    private $broker;

    /**
     * @var MockObject|APMSenderInterface
     */
    private $apm;

    /**
     * @var MockObject|FTags
     */
    private $fTags;

    protected function setUp(): void
    {
        BypassReadonly::enable();

        $this->messageBus = $this->createMock(MessageBus::class);
        $this->connection = $this->createMock(Connection::class);
        $this->configProvider = $this->createMock(Provider::class);
        $this->processor = $this->createMock(Processor::class);
        $this->fbroker = $this->createMock(FBroker::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->messageFactory = $this->createMock(FQMessage::class);
        $this->restarter = $this->createMock(Restarter::class);
        $this->bury = $this->createMock(Bury::class);
        $this->broker = $this->createMock(Pheanstalk::class);
        $this->apm = $this->createMock(APMSenderInterface::class);
        $this->fTags = $this->createMock(FTags::class);

        $this->listener = new Listener(
            'unit',
            $this->logger,
            $this->configProvider,
            $this->processor,
            $this->fbroker,
            $this->messageFactory,
            $this->restarter,
            $this->bury,
            $this->apm,
            $this->fTags
        );
    }

    public function testListenAndPickOneJob(): void
    {
        $job = new Job(new JobId(1), 'data');

        $this->restarter->expects(self::once())->method('restart')->willReturn(false);
        $this->bury->expects(self::once())->method('check')->with($this->broker);
        $this->broker->expects(self::once())->method('reserveWithTimeout')->with(60)->willReturn($job);

        $this->listener->consume($this->broker);
    }

    public function testListenAndRestart(): void
    {
        $this->expectException(Exception::class);
        $this->restarter->expects(self::once())->method('restart')->willReturn(true);

        $this->listener->consume($this->broker);
    }

    public function testSomeDbException(): void
    {
        $this->expectException(\Exception::class);

        $job = new Job(new JobId(1), 'data');

        $this->restarter->expects(self::once())->method('restart')->willReturn(false);
        $this->bury->expects(self::once())->method('check')->with($this->broker);
        $this->broker->expects(self::once())->method('reserveWithTimeout')->with(60)->willReturn($job);
        $this->processor->expects(self::once())->method('process')->willThrowException(new \Exception('gone away'));

        $this->listener->consume($this->broker);
    }
}
