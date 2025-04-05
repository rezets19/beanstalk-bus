<?php

namespace bus\impl;

/**
 * Event handler example
 *
 * Class TEventHandler
 * @package bus\impl
 */
class TEventHandler
{
    public function handle(TEvent $event)
    {
        throw new \Exception('Fatal error in handler, do not repeat the job.');
    }

    public function handle2(TEvent $event)
    {
        throw new \InvalidArgumentException('Non fatal error in handler #2, repeat.');
    }
}
