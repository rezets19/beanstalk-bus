<?php

namespace bus\message;

use bus\broker\FBroker;
use bus\config\ConfigDto;
use bus\config\Connection;
use bus\exception\BrokerNotFoundException;
use Pheanstalk\Pheanstalk;
use Pheanstalk\Values\TubeName;

class Sender
{
    /**
     * @var FBroker
     */
    private FBroker $brokerFactory;

    /**
     * @var Connection
     */
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param ConfigDto $config
     * @param $message
     * @return void
     * @throws BrokerNotFoundException
     */
    public function sendMessage(ConfigDto $config, QMessage $message): void
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

    private function getBrokerFactory(): FBroker
    {
        $this->brokerFactory = new FBroker($this->connection);

        return $this->brokerFactory;
    }
}
