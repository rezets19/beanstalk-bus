<?php

namespace bus\message;

use bus\broker\BrokerFactory;
use bus\config\Config;
use bus\config\Connection;
use bus\exception\BrokerNotFoundException;
use Pheanstalk\Pheanstalk;
use Pheanstalk\Values\TubeName;

class Sender
{
    /**
     * @var BrokerFactory
     */
    private BrokerFactory $brokerFactory;

    public function __construct(private Connection $connection)
    {
    }

    /**
     * @param Config $config
     * @param $message
     * @return void
     * @throws BrokerNotFoundException
     */
    public function sendMessage(Config $config, QMessage $message): void
    {
        $broker = $this->getBrokerFactory()->get($config->getDriver());

        $broker->useTube(new TubeName($message->getQueue()));
        $broker->put(
            json_encode($message),
            $message->getPriority(),
            $message->getDelay(),
            $message->getTimeToRun()
        );
    }

    private function getBrokerFactory(): BrokerFactory
    {
        $this->brokerFactory = new BrokerFactory($this->connection);

        return $this->brokerFactory;
    }
}
