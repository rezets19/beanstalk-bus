<?php

namespace bus\broker\commands;

use Pheanstalk\Contract\JobIdInterface;

interface CommandInterface
{
    public function getJob(): JobIdInterface;
    public function getReason(): string;
}
