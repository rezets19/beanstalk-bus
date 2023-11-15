<?php

namespace bus\broker\commands;

use Pheanstalk\Contract\JobIdInterface;

class KickCommand implements ICommand
{
    public function __construct(private JobIdInterface $job)
    {
    }

    /**
     * @return JobIdInterface
     */
    public function getJob(): JobIdInterface
    {
        return $this->job;
    }
}
