<?php

namespace bus\broker;

use bus\broker\commands\CommandInterface;
use bus\broker\exception\NothingToDoException;
use bus\config\Config;
use Pheanstalk\Contract\JobIdInterface;
use Pheanstalk\Values\JobStats;

interface BuryStrategyInterface
{
    /**
     * @param JobIdInterface $job
     * @param JobStats $jobStats
     * @param Config $config
     * @return CommandInterface
     * @throws NothingToDoException
     */
    public function check(JobIdInterface $job, JobStats $jobStats, Config $config): CommandInterface;
}
