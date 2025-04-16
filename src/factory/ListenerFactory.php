<?php

namespace bus\factory;

use bus\broker\BrokerFactory;
use bus\broker\Bury;
use bus\common\Arrays;
use bus\common\Restarter;
use bus\consumer\Consumer;
use bus\consumer\ExceptionsHandler;
use bus\handler\HandlerInterface;
use bus\interfaces\APMSenderInterface;
use bus\Listener;
use bus\message\Processor;
use bus\message\QMessageFactory;
use bus\message\Sender;
use bus\MessageBus;
use Psr\Log\LoggerInterface;

class ListenerFactory
{
    public function create(
        string             $queue,
        string             $tmpPath,
        MessageBus         $messageBus,
        LoggerInterface    $logger,
        APMSenderInterface $apm,
        HandlerInterface   $handler
    ): Listener {
        return new Listener(
            queueName: $queue,
            logger: $logger,
            brokerFactory: new BrokerFactory($messageBus->getConnection()),
            restarter: new Restarter($tmpPath),
            bury: new Bury($messageBus->getConfigProvider(), $logger),
            consumer: new Consumer(
                logger: $logger,
                messageFactory: new QMessageFactory(),
                configProvider: $messageBus->getConfigProvider(),
                apm: $apm,
                tagsFactory: new TagsFactory(),
                processor: new Processor($messageBus, $logger, $handler, new Sender($messageBus->getConnection())),
                arrays: new Arrays(),
                exceptionsHandler: new ExceptionsHandler(
                    $logger,
                    $apm,
                    new TagsFactory(),
                )
            )
        );
    }
}
