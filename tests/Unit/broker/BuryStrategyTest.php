<?php

namespace Tests\Unit\broker;

use bus\broker\BuryStrategy;
use bus\broker\commands\DeleteCommandInterface;
use bus\broker\commands\KickCommandInterface;
use bus\broker\exception\NothingToDoException;
use bus\config\Config;
use Pheanstalk\Values\Job;
use Pheanstalk\Values\JobId;
use Pheanstalk\Values\JobState;
use Pheanstalk\Values\JobStats;
use Pheanstalk\Values\TubeName;
use PHPUnit\Framework\TestCase;

class BuryStrategyTest extends TestCase
{
    private BuryStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new BuryStrategy();
    }

    public function testKick(): void
    {
        $job = new Job(new JobId(1), 'data');

        $stats = $this->getStats(1, 1);

        $config = new Config();
        $config->setMaxAge(1);
        $config->setMaxKicks(2);

        $command = $this->strategy->check($job, $stats, $config);

        $this->assertInstanceOf(KickCommandInterface::class, $command);
    }

    public function testDeleteWhenKicksMoreThanConfig(): void
    {
        $job = new Job(new JobId(1), 'data');

        $stats = $this->getStats(1, 3);

        $config = new Config();
        $config->setMaxAge(1);
        $config->setMaxKicks(2);

        $command = $this->strategy->check($job, $stats, $config);

        $this->assertInstanceOf(DeleteCommandInterface::class, $command);
    }

    public function testDeleteWhenKicksEqualsToConfig(): void
    {
        $job = new Job(new JobId(1), 'data');

        $stats = $this->getStats(1, 2);

        $config = new Config();
        $config->setMaxAge(1);
        $config->setMaxKicks(2);

        $command = $this->strategy->check($job, $stats, $config);

        $this->assertInstanceOf(DeleteCommandInterface::class, $command);
    }

    public function testNothingToDo(): void
    {
        $this->expectException(NothingToDoException::class);

        $job = new Job(new JobId(1), 'data');

        $stats = $this->getStats(0, 1);

        $config = new Config();
        $config->setMaxAge(1);
        $config->setMaxKicks(2);

        $this->strategy->check($job, $stats, $config);
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
