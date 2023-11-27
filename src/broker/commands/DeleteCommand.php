<?php

namespace bus\broker\commands;

use Pheanstalk\Contract\JobIdInterface;

class DeleteCommand implements ICommand
{
    public function __construct(private JobIdInterface $job, private string $reason)
    {
    }

    /**
     * @return JobIdInterface
     */
    public function getJob(): JobIdInterface
    {
        return $this->job;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
