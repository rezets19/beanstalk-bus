<?php

namespace Tests\Unit\consumer;

use bus\config\Config;
use bus\consumer\ExceptionsHandler;
use bus\factory\TagsFactory;
use bus\interfaces\APMSenderInterface;
use Exception;
use Pheanstalk\Contract\PheanstalkSubscriberInterface;
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
        $this->broker = $this->createMock(PheanstalkSubscriberInterface::class);

        $this->exceptionsHandler = new ExceptionsHandler(
            $this->logger,
            $this->apm,
            $this->fTags
        );
    }

    public function test_gone_away(): void
    {
        $this->expectException(Exception::class);

        $jobStats = $this->getStats(1, 0);
        $job = new Job(new JobId(1), 'data');

        $config = new Config();
        $config->setMaxAge(1);
        $config->setMaxKicks(2);

        $this->exceptionsHandler->handle(
            new Exception('gone away'),
            $jobStats,
            $this->broker,
            $job,
            $config
        );
    }

    public function test_release()
    {
        $jobStats = $this->getStats(1, 0, 0);
        $job = new Job(new JobId(1), 'data');

        $config = new Config();
        $config->setMaxAge(1);
        $config->setMaxKicks(2);
        $config->setMaxRetry(1);
        $config->setPriority(1);

        $this->broker->expects(self::once())->method('release')->with($job, 1, 0);

        $this->exceptionsHandler->handle(
            new Exception('any'),
            $jobStats,
            $this->broker,
            $job,
            $config
        );
    }

    public function test_ignored()
    {
        $jobStats = $this->getStats(1, 0, 0, JobState::BURIED);
        $job = new Job(new JobId(1), 'data');

        $config = new Config();
        $config->setMaxAge(1);
        $config->setMaxKicks(2);
        $config->setMaxRetry(1);
        $config->setPriority(1);

        $this->broker->expects(self::never())->method('release');
        $this->broker->expects(self::never())->method('bury');

        $this->exceptionsHandler->handle(
            new Exception('any'),
            $jobStats,
            $this->broker,
            $job,
            $config
        );
    }

    public function getStats(int $age, int $kicks, int $reserves = 0, $state = JobState::READY): JobStats
    {
        return new JobStats(
            id: new JobId(1),
            tube: new TubeName('test'),
            state: $state,
            priority: 1,
            age: $age,
            delay: 0,
            timeToRelease: 0,
            timeLeft: 0,
            file: 0,
            reserves: $reserves,
            timeouts: 0,
            releases: 0,
            buries: 0,
            kicks: $kicks
        );
    }
}
