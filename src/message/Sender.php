<?php

namespace bus\message;

use bus\broker\BrokerFactory;
use bus\config\Config;
use bus\config\Connection;
use bus\exception\BrokerNotFoundException;
use Pheanstalk\Exception\NoImplementationException;
use Pheanstalk\Values\TubeName;

class Sender
{
    /**
     * @var BrokerFactory
     */
    private BrokerFactory $brokerFactory;

    public function __construct(private Connection $connection)
    {
        $this->brokerFactory = new BrokerFactory($this->connection);
    }

    /**
     * @param Config $config
     * @param QMessage $message
     * @return void
     * @throws BrokerNotFoundException
     * @throws NoImplementationException
     */
    public function sendMessage(Config $config, QMessage $message): void
    {
        $broker = $this->brokerFactory->get($config->getDriver());

        $broker->useTube(new TubeName($message->getQueue()));
        $broker->put(
            json_encode($message),
            $message->getPriority(),
            $message->getDelay(),
            $message->getTimeToRun()
        );
    }
}
