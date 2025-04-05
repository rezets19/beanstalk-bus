<?php

namespace bus\broker;

use bus\broker\commands\DeleteCommandInterface;
use bus\broker\commands\CommandInterface;
use bus\broker\commands\KickCommandInterface;
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
class BuryStrategy implements BuryStrategyInterface
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
     * @return CommandInterface
     * @throws NothingToDoException
     */
    public function check(JobIdInterface $job, JobStats $jobStats, Config $config): CommandInterface
    {
        if ($jobStats->age >= $config->getMaxAge() && $jobStats->kicks < $config->getMaxKicks()) {
            // Return job into ready state
            return new KickCommandInterface($job, sprintf('Job pause timeout reached, to wait: %s, time: %s', $config->getMaxAge(), $jobStats->age));
        } else if ($jobStats->kicks >= $config->getMaxKicks()) {
            // We don't want to keep it anymore
            return new DeleteCommandInterface($job, sprintf('Max kicks reached: max: %s, total: %s', $config->getMaxKicks(), $jobStats->kicks));
        } else {
            // Job is not ready to be kicked or deleted
            throw new NothingToDoException('Job id=' . $job->getId());
        }
    }
}
