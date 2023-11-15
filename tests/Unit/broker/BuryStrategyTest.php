<?php

namespace Tests\Unit\broker;

use bus\broker\BuryStrategy;
use bus\broker\commands\DeleteCommand;
use bus\broker\commands\KickCommand;
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

        $this->assertInstanceOf(KickCommand::class, $command);
    }

    public function testDelete(): void
    {
        $job = new Job(new JobId(1), 'data');

        $stats = $this->getStats(1, 3);

        $config = new Config();
        $config->setMaxAge(1);
        $config->setMaxKicks(2);

        $command = $this->strategy->check($job, $stats, $config);

        $this->assertInstanceOf(DeleteCommand::class, $command);
    }

    public function testDelete2()
    {
        $job = new Job(new JobId(1), 'data');

        $stats = $this->getStats(1, 2);

        $config = new Config();
        $config->setMaxAge(1);
        $config->setMaxKicks(2);

        $command = $this->strategy->check($job, $stats, $config);

        $this->assertInstanceOf(DeleteCommand::class, $command);
    }

    public function testNothingToDO()
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
