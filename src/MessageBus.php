<?php

namespace bus;

use bus\common\Handler;
use bus\config\Config;
use bus\config\Connection;
use bus\config\Provider;
use bus\exception\BrokerNotFoundException;
use bus\factory\TagsFactory;
use bus\interfaces\APMSenderInterface;
use bus\message\QMessageFactory;
use bus\message\QMessage;
use bus\message\Sender;
use Exception;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use ReflectionException;

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
     * @var Provider
     */
    private Provider $configProvider;

    /**
     * @var Handler
     */
    private Handler $handler;

    /**
     * @var QMessageFactory
     */
    private QMessageFactory $messageFactory;
    /**
     * @var Connection
     */
    private Connection $connection;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var Sender
     */
    private Sender $sender;

    /**
     * @var APMSenderInterface
     */
    private APMSenderInterface $apm;
    /**
     * @var TagsFactory
     */
    private TagsFactory $fTags;

    public function __construct(Connection $connection, Provider $configProvider, LoggerInterface $logger, APMSenderInterface $apm, TagsFactory $fTags)
    {
        $this->connection = $connection;
        $this->configProvider = $configProvider;
        $this->logger = $logger;
        $this->apm = $apm;
        $this->fTags = $fTags;
        $this->messageFactory = new QMessageFactory();

        $this->sender = new Sender($this->connection);
        $this->handler = new Handler($this->logger);
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
     * @throws ReflectionException
     * @throws Exception
     */
    public function dispatch(object $job): object
    {
        $config = $this->configProvider->getByJob($job);

        if ($config->isAsync()) {
            $message = $this->messageFactory->create($job, $config);
            // First delay = zero, then from config
            $message->setDelay(0);
            $this->sender->sendMessage($config, $message);
            $this->apm->metricIncrement(self::METRIC_JOB_ADD_CNT, $this->fTags->create($config));
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
