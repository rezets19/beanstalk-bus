<?php

namespace bus;

use bus\broker\BrokerFactory;
use bus\broker\Bury;
use bus\config\ConfigNotFoundException;
use bus\consumer\Consumer;
use bus\exception\BrokerNotFoundException;
use bus\exception\HandlerNotFoundException;
use InvalidArgumentException;
use Pheanstalk\Contract\PheanstalkManagerInterface;
use Pheanstalk\Contract\PheanstalkPublisherInterface;
use Pheanstalk\Contract\PheanstalkSubscriberInterface;
use Pheanstalk\Values\TubeName;
use Psr\Log\LoggerInterface;
use ReflectionException;
use Throwable;

class Worker
{
    public function __construct(
        private string          $queueName,
        private LoggerInterface $logger,
        private BrokerFactory   $brokerFactory,
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
        $this->consume($broker);
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
