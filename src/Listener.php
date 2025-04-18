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
use Pheanstalk\Contract\PheanstalkManagerInterface;
use Pheanstalk\Contract\PheanstalkPublisherInterface;
use Pheanstalk\Contract\PheanstalkSubscriberInterface;
use Pheanstalk\Values\TubeName;
use Psr\Log\LoggerInterface;
use ReflectionException;
use Throwable;

/**
 * Main background process
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
    ) {
        $this->validate();
    }

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
     * @throws HandlerNotFoundException
     * @throws Throwable
     * @throws ReflectionException
     * @throws ConfigNotFoundException
     * @throws BrokerNotFoundException
     */
    public function consume(PheanstalkManagerInterface|PheanstalkPublisherInterface|PheanstalkSubscriberInterface $broker): void
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
