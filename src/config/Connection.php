<?php

namespace bus\config;

class Connection
{
    /**
     * Connection constructor.
     * @param string $host
     * @param int $port
     */
    public function __construct(private string $host, private int $port = 11300)
    {
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }
}
