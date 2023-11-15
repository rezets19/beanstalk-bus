<?php

namespace bus\common;

/**
 * Graceful restart
 */
class Restarter
{
    private ?int $lastUpdate = null;

    private int $lastCheck;

    private int $timeout;

    public function __construct(private string $tmpPath)
    {
        $this->lastCheck = time();
        $this->timeout = 10;
    }

    public function restart(): bool
    {
        if ((time() - $this->lastCheck) < $this->timeout) {
            return false;
        }

        $this->lastCheck = time();

        clearstatcache();

        $file = $this->tmpPath . '/restart.txt';

        if (!is_file($file)) {
            file_put_contents($file, 1);
        }

        $lastUpdate = filemtime($file);

        if (null === $this->lastUpdate) {
            $this->lastUpdate = $lastUpdate;
        }

        if ($lastUpdate !== $this->lastUpdate) {
            return true;
        }

        return false;
    }
}
