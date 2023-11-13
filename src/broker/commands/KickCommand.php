<?php

namespace bus\broker\commands;

use Pheanstalk\Contract\JobIdInterface;

class KickCommand implements ICommand
{
    private JobIdInterface $job;

    public function __construct(JobIdInterface $job)
    {
        $this->job = $job;
    }

    /**
     * @return JobIdInterface
     */
    public function getJob(): JobIdInterface
    {
        return $this->job;
    }
}
