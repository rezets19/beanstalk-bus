<?php

namespace bus\handler;

interface IHandler
{
    public function handle(object $event, iterable $handlers): void;
}
