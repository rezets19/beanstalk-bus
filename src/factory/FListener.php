<?php

namespace bus\factory;

use bus\broker\Bury;
use bus\broker\dto\FStatsDto;
use bus\broker\FBroker;
use bus\common\Restarter;
use bus\interfaces\APMSenderInterface;
use bus\Listener;
use bus\message\FQMessage;
use bus\message\Processor;
use bus\MessageBus;
use Psr\Log\LoggerInterface;

class FListener
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
            $queue,
            $logger,
            $messageBus->getConfigProvider(),
            new Processor($messageBus, $logger),
            new FBroker($messageBus->getConnection()),
            new FQMessage(),
            new Restarter($tmpPath),
            new Bury($messageBus->getConfigProvider(), $logger),
            $apm,
            new FTags()
        );
    }
}
