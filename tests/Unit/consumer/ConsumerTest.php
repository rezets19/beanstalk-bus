<?php

namespace Tests\Unit\consumer;

use bus\common\Arrays;
use bus\config\Provider;
use bus\consumer\Consumer;
use bus\consumer\ExceptionsHandler;
use bus\factory\TagsFactory;
use bus\interfaces\APMSenderInterface;
use bus\message\QMessageFactory;
use bus\message\Processor;
use Exception;
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
    private MockObject|TagsFactory $fTags;
    private Processor|MockObject $processor;
    private Arrays|MockObject $arrays;
    private ExceptionsHandler|MockObject $exceptionsHandler;

    protected function setUp(): void
    {
        BypassReadonly::enable();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->messageFactory = $this->createMock(QMessageFactory::class);
        $this->configProvider = $this->createMock(Provider::class);
        $this->apm = $this->createMock(APMSenderInterface::class);
        $this->fTags = $this->createMock(TagsFactory::class);
        $this->processor = $this->createMock(Processor::class);
        $this->arrays = $this->createMock(Arrays::class);
        $this->exceptionsHandler = $this->createMock(ExceptionsHandler::class);

        $this->broker = $this->createMock(Pheanstalk::class);

        $this->consumer = new Consumer(
            $this->logger,
            $this->messageFactory,
            $this->configProvider,
            $this->apm,
            $this->fTags,
            $this->processor,
            $this->arrays,
            $this->exceptionsHandler
        );
    }

    public function test_pick_job(): void
    {
        $job = new Job(new JobId(1), 'data');

        $this->broker->expects(self::once())->method('reserveWithTimeout')->with(60)->willReturn($job);

        $this->consumer->consume('unit', $this->broker);
    }
}
