<?php

namespace bus;

use bus\config\ConfigNotFoundException;
use bus\config\Connection;
use bus\config\Provider;
use bus\exception\BrokerNotFoundException;
use bus\factory\TagsFactory;
use bus\handler\HandlerInterface;
use bus\interfaces\APMSenderInterface;
use bus\message\QMessageFactory;
use bus\message\Sender;
use Pheanstalk\Exception\NoImplementationException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Main class to handle events and commands
 */
class MessageBus implements EventDispatcherInterface
{
    public const METRIC_JOB_ADD_CNT = 'beanstalk_job_add_cnt';
    private QMessageFactory $messageFactory;
    private Sender $sender;

    public function __construct(
        private Connection         $connection,
        private Provider           $configProvider,
        private LoggerInterface    $logger,
        private APMSenderInterface $apm,
        private TagsFactory        $tagsFactory,
        private HandlerInterface $handler
    ) {
        $this->messageFactory = new QMessageFactory();
        $this->sender = new Sender($this->connection);
    }

    public function getConfigProvider(): Provider
    {
        return $this->configProvider;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @throws BrokerNotFoundException
     * @throws ConfigNotFoundException
     * @throws NoImplementationException
     */
    public function dispatch(object $job): object
    {
        $config = $this->configProvider->getByJob($job);

        if ($config->isAsync()) {
            $message = $this->messageFactory->create($job, $config);
            // First delay = zero, then from config
            $message->setDelay(0);
            $this->sender->sendMessage($config, $message);
            $this->apm->metricIncrement(self::METRIC_JOB_ADD_CNT, $this->tagsFactory->create($config));
        } else {
            $this->handler->handle($job, $config->getHandlers());
        }

        return $job;
    }
}
