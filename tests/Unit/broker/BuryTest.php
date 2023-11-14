<?php

namespace Tests\Unit\broker;

use bus\broker\Bury;
use bus\broker\commands\DeleteCommand;
use bus\broker\commands\KickCommand;
use bus\broker\exception\NothingToDoException;
use bus\broker\FBuryStrategy;
use bus\broker\IBuryStrategy;
use bus\config\Provider;
use bus\message\FQMessage;
use bus\message\QMessage;
use bus\serializer\IBodySerializer;
use JsonSerializable;
use Pheanstalk\Contract\PheanstalkManagerInterface;
use Pheanstalk\Contract\PheanstalkSubscriberInterface;
use Pheanstalk\Contract\ResponseInterface;
use Pheanstalk\Values\Job;
use Pheanstalk\Values\JobId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tests\Unit\PrivateProperty;
use Zfekete\BypassReadonly\BypassReadonly;

class BuryTest extends TestCase
{
    use PrivateProperty;

    private Bury $checker;

    private MockObject|Provider $configProvider;

    private MockObject|LoggerInterface $logger;


    /**
     * @var MockObject|FQMessage
     */
    private $factory;

    /**
     * @var MockObject|IBodySerializer
     */
    private $serializer;

    /**
     * @var MockObject|FBuryStrategy
     */
    private $fBuryStrategy;

    /**
     * @var MockObject|IBuryStrategy
     */
    private $iBuryStrategy;

    /**
     * @var MockObject|ResponseInterface
     */
    private $responseInterface;
    /**
     * @var PheanstalkManagerInterface|(PheanstalkManagerInterface&MockObject)|MockObject
     */
    private MockObject|PheanstalkManagerInterface $manager;
    /**
     * @var PheanstalkSubscriberInterface|(PheanstalkSubscriberInterface&MockObject)|MockObject
     */
    private MockObject|PheanstalkSubscriberInterface $subscriber;

    protected function setUp(): void
    {
        BypassReadonly::enable();

        $this->configProvider = $this->createMock(Provider::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->manager = $this->createMock(PheanstalkManagerInterface::class);
        $this->subscriber = $this->createMock(PheanstalkSubscriberInterface::class);

        $this->factory = $this->createMock(FQMessage::class);
        $this->serializer = $this->createMock(IBodySerializer::class);
        $this->fBuryStrategy = $this->createMock(FBuryStrategy::class);
        $this->iBuryStrategy = $this->createMock(IBuryStrategy::class);
        $this->responseInterface = $this->createMock(ResponseInterface::class);

        $this->checker = new Bury($this->configProvider, $this->logger);
        $this->setMockPropertyObj($this->checker, 'factory', $this->factory);
        $this->setMockPropertyObj($this->checker, 'configProvider', $this->configProvider);
        $this->setMockPropertyObj($this->checker, 'fBuryStrategy', $this->fBuryStrategy);
    }

    public function testKickJob(): void
    {
        $job = new Job(new JobId(1), 'data');

        $message = new QMessage($this->createMock(JsonSerializable::class), $this->serializer);
        $command = new KickCommand($job);

        $this->manager->expects(self::once())->method('statsJob');
        $this->manager->expects(self::once())->method('kickJob')->with($command->getJob());
        $this->factory->expects(self::once())->method('fromString')->willReturn($message);
        $this->iBuryStrategy->expects(self::once())->method('check')->willReturn($command);
        $this->fBuryStrategy->expects(self::once())->method('create')->willReturn($this->iBuryStrategy);

        $this->checker->consume($job, $this->manager, $this->subscriber);
    }

    public function testDeleteJob(): void
    {
        $job = new Job(new JobId(1), 'data');
        $message = new QMessage($this->createMock(JsonSerializable::class), $this->serializer);
        $command = new DeleteCommand($job);

        $this->manager->expects(self::once())->method('statsJob')->with($job);
        $this->subscriber->expects(self::once())->method('delete')->with($command->getJob());
        $this->factory->expects(self::once())->method('fromString')->willReturn($message);
        $this->iBuryStrategy->expects(self::once())->method('check')->willReturn($command);
        $this->fBuryStrategy->expects(self::once())->method('create')->willReturn($this->iBuryStrategy);

        $this->checker->consume($job, $this->manager, $this->subscriber);
    }

    public function testConsumeWithException(): void
    {
        $this->expectException(NothingToDoException::class);

        $job = new Job(new JobId(1), 'data');
        $message = new QMessage($this->createMock(JsonSerializable::class), $this->serializer);
        $command = new DeleteCommand($job);

        $this->manager->expects(self::once())->method('statsJob')->with($job);
        $this->manager->expects(self::never())->method('kickJob')->with($command->getJob());
        $this->subscriber->expects(self::never())->method('delete')->with($command->getJob());
        $this->factory->expects(self::once())->method('fromString')->willReturn($message);
        $this->iBuryStrategy->expects(self::once())->method('check')->willThrowException(new NothingToDoException());
        $this->fBuryStrategy->expects(self::once())->method('create')->willReturn($this->iBuryStrategy);

        $this->checker->consume($job, $this->manager, $this->subscriber);
    }
}
