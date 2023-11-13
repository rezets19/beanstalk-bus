<?php

namespace bus\broker;

use bus\broker\exception\BuryStrategyNotFoundException;
use Psr\Log\LoggerInterface;

class FBuryStrategy
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function create(string $class): IBuryStrategy
    {
        if (BuryStrategy::class === $class) {

            return new BuryStrategy($this->logger);
        }

        throw new BuryStrategyNotFoundException($class);
    }
}
