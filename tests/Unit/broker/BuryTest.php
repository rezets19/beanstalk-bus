<?php

namespace Tests\Unit\broker;

use bus\broker\Bury;
use bus\broker\commands\DeleteCommandInterface;
use bus\broker\commands\KickCommandInterface;
use bus\broker\exception\NothingToDoException;
use bus\broker\BuryStrategyFactory;
use bus\broker\BuryStrategyInterface;
use bus\config\Provider;
use bus\message\QMessageFactory;
use bus\message\QMessage;
use bus\serializer\BodySerializerInterface;
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

    private MockObject|QMessageFactory $factory;

    private MockObject|BodySerializerInterface $serializer;

    private MockObject|BuryStrategyFactory $buryStrategyFactory;

    private MockObject|BuryStrategyInterface $buryStrategy;

    private MockObject|ResponseInterface $response;

    private MockObject|PheanstalkManagerInterface $manager;

    private MockObject|PheanstalkSubscriberInterface $subscriber;

    protected function setUp(): void
    {
        BypassReadonly::enable();

        $this->configProvider = $this->createMock(Provider::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->manager = $this->createMock(PheanstalkManagerInterface::class);
        $this->subscriber = $this->createMock(PheanstalkSubscriberInterface::class);

        $this->factory = $this->createMock(QMessageFactory::class);
        $this->serializer = $this->createMock(BodySerializerInterface::class);
        $this->buryStrategyFactory = $this->createMock(BuryStrategyFactory::class);
        $this->buryStrategy = $this->createMock(BuryStrategyInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);

        $this->checker = new Bury($this->configProvider, $this->logger);
        $this->setMockPropertyObj($this->checker, 'factory', $this->factory);
        $this->setMockPropertyObj($this->checker, 'configProvider', $this->configProvider);
        $this->setMockPropertyObj($this->checker, 'fBuryStrategy', $this->buryStrategyFactory);
    }

    public function testKickJob(): void
    {
        $job = new Job(new JobId(1), 'data');

        $message = new QMessage($this->createMock(JsonSerializable::class), $this->serializer);
        $command = new KickCommandInterface($job, 'reason');

        $this->manager->expects(self::once())->method('statsJob');
        $this->manager->expects(self::once())->method('kickJob')->with($command->getJob());
        $this->factory->expects(self::once())->method('fromString')->willReturn($message);
        $this->buryStrategy->expects(self::once())->method('check')->willReturn($command);
        $this->buryStrategyFactory->expects(self::once())->method('create')->willReturn($this->buryStrategy);

        $this->checker->consume($job, $this->manager, $this->subscriber);
    }

    public function testDeleteJob(): void
    {
        $job = new Job(new JobId(1), 'data');
        $message = new QMessage($this->createMock(JsonSerializable::class), $this->serializer);
        $command = new DeleteCommandInterface($job, 'reason');

        $this->manager->expects(self::once())->method('statsJob')->with($job);
        $this->subscriber->expects(self::once())->method('delete')->with($command->getJob());
        $this->factory->expects(self::once())->method('fromString')->willReturn($message);
        $this->buryStrategy->expects(self::once())->method('check')->willReturn($command);
        $this->buryStrategyFactory->expects(self::once())->method('create')->willReturn($this->buryStrategy);

        $this->checker->consume($job, $this->manager, $this->subscriber);
    }

    public function testConsumeWithException(): void
    {
        $this->expectException(NothingToDoException::class);

        $job = new Job(new JobId(1), 'data');
        $message = new QMessage($this->createMock(JsonSerializable::class), $this->serializer);
        $command = new DeleteCommandInterface($job, 'reason');

        $this->manager->expects(self::once())->method('statsJob')->with($job);
        $this->manager->expects(self::never())->method('kickJob')->with($command->getJob());
        $this->subscriber->expects(self::never())->method('delete')->with($command->getJob());
        $this->factory->expects(self::once())->method('fromString')->willReturn($message);
        $this->buryStrategy->expects(self::once())->method('check')->willThrowException(new NothingToDoException());
        $this->buryStrategyFactory->expects(self::once())->method('create')->willReturn($this->buryStrategy);

        $this->checker->consume($job, $this->manager, $this->subscriber);
    }
}
