<?php

namespace bus\broker;

use bus\broker\exception\BuryStrategyNotFoundException;
use Psr\Log\LoggerInterface;

class BuryStrategyFactory
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * @param string $class
     * @return IBuryStrategy
     * @throws BuryStrategyNotFoundException
     */
    public function create(string $class): IBuryStrategy
    {
        if (BuryStrategy::class === $class) {

            return new BuryStrategy($this->logger);
        }

        throw new BuryStrategyNotFoundException($class);
    }
}
