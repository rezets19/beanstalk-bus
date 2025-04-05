<?php

namespace bus\broker\commands;

use Pheanstalk\Contract\JobIdInterface;

interface ICommand
{
    public function getJob(): JobIdInterface;
    public function getReason(): string;
}
