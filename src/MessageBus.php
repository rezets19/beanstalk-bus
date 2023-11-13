<?php

namespace bus;

use bus\common\Handler;
use bus\config\ConfigDto;
use bus\config\Connection;
use bus\config\Provider;
use bus\exception\BrokerNotFoundException;
use bus\factory\FTags;
use bus\interfaces\APMSenderInterface;
use bus\message\FQMessage;
use bus\message\QMessage;
use bus\message\Sender;
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
     * @var FQMessage
     */
    private FQMessage $messageFactory;
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
    private ?Sender $sender = null;

    /**
     * @var APMSenderInterface
     */
    private APMSenderInterface $apm;
    /**
     * @var FTags
     */
    private FTags $fTags;

    public function __construct(Connection $connection, Provider $configProvider, LoggerInterface $logger, APMSenderInterface $apm, FTags $fTags)
    {
        $this->connection = $connection;
        $this->configProvider = $configProvider;
        $this->logger = $logger;
        $this->apm = $apm;
        $this->fTags = $fTags;
        $this->messageFactory = new FQMessage();
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
     * @throws \Exception
     */
    public function dispatch(object $job): object
    {
        $config = $this->configProvider->getByJob($job);

        if ($config->isAsync()) {
            $message = $this->getMessageFactory()->create($job, $config);
            // First delay = zero, then from config
            $message->setDelay(0);
            $this->sendMessage($config, $message);
            $this->apm->metricIncrement(self::METRIC_JOB_ADD_CNT, $this->fTags->create($config));
        } else {
            $this->getHandler()->handle($job, $config->getHandlers());
        }

        return $job;
    }

    /**
     * @param ConfigDto $config
     * @param $message
     * @return void
     * @throws BrokerNotFoundException
     */
    public function sendMessage(ConfigDto $config, QMessage $message): void
    {
        $this->getSender()->sendMessage($config, $message);
    }

    /**
     * @return Sender
     */
    private function getSender(): Sender
    {
        if (null === $this->sender) {
            $this->sender = new Sender($this->connection);
        }

        return $this->sender;
    }

    /**
     * @return Handler
     */
    private function getHandler(): Handler
    {
        if (null === $this->handler) {
            $this->handler = new Handler($this->logger);
        }

        return $this->handler;
    }

    /**
     * @return FQMessage
     */
    private function getMessageFactory(): FQMessage
    {
        return $this->messageFactory;
    }
}
