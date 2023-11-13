<?php

namespace bus\broker;

use bus\broker\commands\ICommand;
use bus\broker\exception\NothingToDoException;
use bus\config\ConfigDto;
use Pheanstalk\Contract\JobIdInterface;
use Pheanstalk\Values\JobStats;

interface IBuryStrategy
{
    /**
     * @param JobIdInterface $job
     * @param ConfigDto $config
     * @return ICommand
     * @throws NothingToDoException
     */
    public function check(JobIdInterface $job, JobStats $stats, ConfigDto $config): ICommand;
}
