<?php

namespace bus;

use bus\config\Config;
use bus\config\ConfigNotFoundException;
use bus\config\Connection;
use bus\config\Provider;
use bus\exception\BrokerNotFoundException;
use bus\factory\TagsFactory;
use bus\handler\IHandler;
use bus\interfaces\APMSenderInterface;
use bus\message\QMessage;
use bus\message\QMessageFactory;
use bus\message\Sender;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Main class to handle events and commands
 *
 * Class MessageBus
 * @package bus
 */
class MessageBus implements EventDispatcherInterface
{
    public const METRIC_JOB_ADD_CNT = 'beanstalk_job_add_cnt';

    /**
     * @var QMessageFactory
     */
    private QMessageFactory $messageFactory;

    /**
     * @var Sender
     */
    private Sender $sender;

    public function __construct(
        private Connection         $connection,
        private Provider           $configProvider,
        private LoggerInterface    $logger,
        private APMSenderInterface $apm,
        private TagsFactory        $tagsFactory,
        private IHandler           $handler
    )
    {
        $this->messageFactory = new QMessageFactory();
        $this->sender = new Sender($this->connection);
    }

    /**
     * @return Provider
     */
    public function getConfigProvider(): Provider
    {
        return $this->configProvider;
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @param object $job
     * @return object
     * @throws BrokerNotFoundException
     * @throws ConfigNotFoundException
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

    /**
     * @param Config $config
     * @param QMessage $message
     * @return void
     * @throws BrokerNotFoundException
     */
    public function sendMessage(Config $config, QMessage $message): void
    {
        $this->sender->sendMessage($config, $message);
    }
}
