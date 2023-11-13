<?php

namespace bus\config;

class Connection
{
    private string $host;
    private int $port;

    /**
     * Connection constructor.
     * @param string $host
     * @param int $port
     */
    public function __construct(string $host, int $port = 11300)
    {
        $this->host = $host;
        $this->port = $port;
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
