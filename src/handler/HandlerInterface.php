<?php

namespace bus\handler;

interface HandlerInterface
{
    public function handle(object $event, iterable $handlers): void;
}
