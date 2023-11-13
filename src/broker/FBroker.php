<?php

namespace bus\broker;

use bus\config;
use bus\exception\BrokerNotFoundException;
use Pheanstalk\Connection;
use Pheanstalk\Exception\NoImplementationException;
use Pheanstalk\Pheanstalk;
use Pheanstalk\SocketFactory;

/**
 * Broker factory
 *
 * Class FBroker
 * @package bus
 */
class FBroker
{
    const DRIVER_BEANSTALK = 'beanstalk';

    private config\Connection $connection;

    private static array $brokers = [];

    public function __construct(config\Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string $driver
     * @return Pheanstalk
     * @throws BrokerNotFoundException
     * @throws NoImplementationException
     */
    public function get(string $driver): Pheanstalk
    {
        $key = $driver . $this->connection->getHost() . $this->connection->getPort();

        if (self::DRIVER_BEANSTALK === $driver) {
            if (!isset(self::$brokers[$key])) {
                $connection = new Connection((new SocketFactory($this->connection->getHost(), $this->connection->getPort())));
                self::$brokers[$key] = new Pheanstalk($connection);
            }

            return self::$brokers[$key];
        }

        throw new BrokerNotFoundException($driver);
    }
}
