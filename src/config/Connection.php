<?php

namespace bus\config;

class Connection
{
    /**
     * Connection constructor.
     */
    public function __construct(private string $host, private int $port = 11300)
    {
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }
}
