<?php

namespace Tests\Unit\consumer;

use bus\common\Arrays;
use bus\config\Provider;
use bus\consumer\Consumer;
use bus\consumer\ExceptionsHandler;
use bus\factory\TagsFactory;
use bus\interfaces\APMSenderInterface;
use bus\message\QMessage;
use bus\message\QMessageFactory;
use bus\message\Processor;
use Pheanstalk\Contract\PheanstalkManagerInterface;
use Pheanstalk\Contract\PheanstalkPublisherInterface;
use Pheanstalk\Contract\PheanstalkSubscriberInterface;
use Pheanstalk\Pheanstalk;
use Pheanstalk\Values\Job;
use Pheanstalk\Values\JobId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Zfekete\BypassReadonly\BypassReadonly;

class ConsumerTest extends TestCase
{
    private LoggerInterface|MockObject $logger;
    private QMessageFactory|MockObject $messageFactory;
    private MockObject|Provider $configProvider;
    private MockObject|APMSenderInterface $apm;
    private MockObject|TagsFactory $tagsFactory;
    private Processor|MockObject $processor;
    private Arrays|MockObject $arrays;
    private ExceptionsHandler|MockObject $exceptionsHandler;
    private PheanstalkManagerInterface|PheanstalkPublisherInterface|PheanstalkSubscriberInterface|MockObject $broker;

    protected function setUp(): void
    {
        BypassReadonly::enable();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->messageFactory = $this->createMock(QMessageFactory::class);
        $this->configProvider = $this->createMock(Provider::class);
        $this->apm = $this->createMock(APMSenderInterface::class);
        $this->tagsFactory = $this->createMock(TagsFactory::class);
        $this->processor = $this->createMock(Processor::class);
        $this->arrays = $this->createMock(Arrays::class);
        $this->exceptionsHandler = $this->createMock(ExceptionsHandler::class);

        $this->broker = $this->createMockForIntersectionOfInterfaces([
            PheanstalkManagerInterface::class,
            PheanstalkPublisherInterface::class,
            PheanstalkSubscriberInterface::class,
        ]);

        $this->consumer = new Consumer(
            $this->logger,
            $this->messageFactory,
            $this->configProvider,
            $this->apm,
            $this->tagsFactory,
            $this->processor,
            $this->arrays,
            $this->exceptionsHandler
        );
    }

    /**
     * TODO: finish test
     */
    public function testPickJob(): void
    {
        $json = file_get_contents(__DIR__ . '/../message/message.json');
        $message = $this->createMock(QMessage::class);

        $job = $this->createMock(Job::class);
        $job->method('getId')->willReturn('1');
        $job->method('getData')->willReturn($json);

        $this->broker->expects(self::once())->method('reserveWithTimeout')->with(60)->willReturn($job);
        $this->broker->expects(self::once())->method('statsJob')->with($job);
        $this->messageFactory->expects(self::once())->method('fromString')->with($json)->willReturn($message);

        $this->consumer->consume('unit', $this->broker);
    }
}
