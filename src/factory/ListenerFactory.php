<?php

namespace bus\factory;

use bus\broker\Bury;
use bus\broker\BrokerFactory;
use bus\common\Arrays;
use bus\common\Restarter;
use bus\consumer\Consumer;
use bus\consumer\ExceptionsHandler;
use bus\interfaces\APMSenderInterface;
use bus\Listener;
use bus\message\QMessageFactory;
use bus\message\Processor;
use bus\MessageBus;
use Psr\Log\LoggerInterface;

class ListenerFactory
{
    public function create(
        string $queue,
        string $tmpPath,
        MessageBus $messageBus,
        LoggerInterface $logger,
        APMSenderInterface $apm
    ): Listener
    {
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
                fTags: new TagsFactory(),
                processor: new Processor($messageBus, $logger),
                arrays: new  Arrays(),
                exceptionsHandler:  new ExceptionsHandler(
                    $logger,
                    $apm,
                    new TagsFactory(),
                )
            )
        );
    }
}
