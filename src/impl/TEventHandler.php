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
        throw new \Exception('Some error in handle');
    }

    public function handle2(TEvent $event)
    {
        throw new \InvalidArgumentException('Error #2');
    }
}
