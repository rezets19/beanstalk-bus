<?php

namespace Tests\Unit;

use Exception;

class TEventHandler
{
    /**
     * @param TEvent $event
     * @return void
     * @throws Exception
     */
    public function handle(TEvent $event): void
    {
        if ($event->getId() > 0) {
            throw new Exception();
        }
    }
}
