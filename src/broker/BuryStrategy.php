<?php

namespace bus\broker;

use bus\broker\commands\DeleteCommand;
use bus\broker\commands\ICommand;
use bus\broker\commands\KickCommand;
use bus\broker\exception\NothingToDoException;
use bus\config\Config;
use Pheanstalk\Contract\JobIdInterface;
use Pheanstalk\Values\JobStats;

/**
 * Default jobs bury strategy
 *
 * Class BuryStrategy
 * @package bus\broker
 */
class BuryStrategy implements IBuryStrategy
{
    public function __construct()
    {
    }

    /**
     * Handle buried jobs
     *
     * @param JobIdInterface $job
     * @param JobStats $jobStats
     * @param Config $config
     * @return ICommand
     * @throws NothingToDoException
     */
    public function check(JobIdInterface $job, JobStats $jobStats, Config $config): ICommand
    {
        if ($jobStats->age >= $config->getMaxAge() && $jobStats->kicks < $config->getMaxKicks()) {
            // Return job into ready state
            return new KickCommand($job, sprintf('Job pause timeout reached, to wait: %s, time: %s', $config->getMaxAge(), $jobStats->age));
        } else if ($jobStats->kicks >= $config->getMaxKicks()) {
            // We don't want to keep it anymore
            return new DeleteCommand($job, sprintf('Max kicks reached: max: %s, total: %s', $config->getMaxKicks(), $jobStats->kicks));
        } else {
            // Job is not ready to be kicked or deleted
            throw new NothingToDoException('Job id=' . $job->getId());
        }
    }
}
