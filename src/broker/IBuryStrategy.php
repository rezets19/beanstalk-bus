<?php

namespace bus\broker;

use bus\broker\commands\ICommand;
use bus\broker\exception\NothingToDoException;
use bus\config\Config;
use Pheanstalk\Contract\JobIdInterface;
use Pheanstalk\Values\JobStats;

interface IBuryStrategy
{
    /**
     * @param JobIdInterface $job
     * @param Config $config
     * @return ICommand
     * @throws NothingToDoException
     */
    public function check(JobIdInterface $job, JobStats $stats, Config $config): ICommand;
}
