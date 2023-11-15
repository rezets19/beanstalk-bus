<?php

namespace bus;

use bus\broker\Bury;
use bus\broker\BrokerFactory;
use bus\common\Restarter;
use bus\config\ConfigNotFoundException;
use bus\consumer\Consumer;
use bus\exception\BrokerNotFoundException;
use bus\exception\HandlerNotFoundException;
use Exception;
use InvalidArgumentException;
use Pheanstalk\Pheanstalk;
use Pheanstalk\Values\TubeName;
use Psr\Log\LoggerInterface;
use ReflectionException;
use Throwable;

/**
 * Main background process
 *
 * Class Listener
 * @package bus
 */
class Listener
{
    public function __construct(
        private string          $queueName,
        private LoggerInterface $logger,
        private BrokerFactory   $brokerFactory,
        private Restarter       $restarter,
        private Bury            $bury,
        private Consumer        $consumer
    )
    {
        $this->validate();
    }

    /**
     * @return void
     */
    public function listen(): void
    {
        $broker = $this->brokerFactory->get(BrokerFactory::DRIVER_BEANSTALK);

        $queueName = new TubeName($this->queueName);

        $broker->useTube($queueName);
        $broker->watch($queueName);

        while (true) {
            $this->consume($broker);
        }
    }

    /**
     * @param Pheanstalk $broker
     * @throws HandlerNotFoundException
     * @throws Throwable
     * @throws ReflectionException
     * @throws ConfigNotFoundException
     * @throws BrokerNotFoundException
     */
    public function consume(Pheanstalk $broker): void
    {
        if ($this->restarter->restart()) {
            $this->logger->notice('Restart attempt received, shutting down');

            throw new Exception('restart');
        }

        $this->bury->check($broker);

        $this->consumer->consume($this->queueName, $broker);
    }

    private function validate(): void
    {
        if (empty($this->queueName)) {
            throw new InvalidArgumentException('Queue is empty');
        }
    }
}
