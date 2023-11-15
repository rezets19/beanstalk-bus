<?php

namespace Tests\Unit\consumer;

use bus\config\Config;
use bus\consumer\ExceptionsHandler;
use bus\factory\TagsFactory;
use bus\interfaces\APMSenderInterface;
use Exception;
use Pheanstalk\Pheanstalk;
use Pheanstalk\Values\Job;
use Pheanstalk\Values\JobId;
use Pheanstalk\Values\JobState;
use Pheanstalk\Values\JobStats;
use Pheanstalk\Values\TubeName;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Zfekete\BypassReadonly\BypassReadonly;

class ExceptionsHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        BypassReadonly::enable();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->apm = $this->createMock(APMSenderInterface::class);
        $this->fTags = $this->createMock(TagsFactory::class);
        $this->broker = $this->createMock(Pheanstalk::class);

        $this->exceptionsHandler = new ExceptionsHandler(
            $this->logger,
            $this->apm,
            $this->fTags
        );
    }

    public function test_gone_away(): void
    {
        $this->expectException(Exception::class);

        $stats = $this->getStats(1, 0);
        $job = new Job(new JobId(1), 'data');

        $config = new Config();
        $config->setMaxAge(1);
        $config->setMaxKicks(2);

        $this->exceptionsHandler->handle(
            new \Exception('gone away'),
            $stats,
            $this->broker,
            $job,
            $config
        );
    }

    public function getStats(int $age, int $kicks): JobStats
    {
        $stats = new JobStats(
            new JobId(1),
            new TubeName('test'),
            JobState::READY,
            1,
            $age,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            $kicks
        );
        return $stats;
    }
}
