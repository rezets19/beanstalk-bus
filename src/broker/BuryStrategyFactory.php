<?php

namespace bus\broker;

use bus\broker\exception\BuryStrategyNotFoundException;
use Psr\Log\LoggerInterface;

class BuryStrategyFactory
{
    public function __construct()
    {
    }

    /**
     * @param string $class
     * @return BuryStrategyInterface
     * @throws BuryStrategyNotFoundException
     */
    public function create(string $class): BuryStrategyInterface
    {
        if (BuryStrategy::class === $class) {
            return new BuryStrategy();
        }

        throw new BuryStrategyNotFoundException($class);
    }
}
