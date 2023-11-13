<?php

use bus\config\Provider;
use bus\impl\ConsoleLogger;
use bus\impl\NullAPMSender;
use bus\MessageBus;

require __DIR__.'/../vendor/autoload.php';

$messageBus =  new MessageBus(
    new \bus\config\Connection('127.0.0.1'),
    new Provider(include __DIR__ . '/../src/impl/config.php'),
    new ConsoleLogger(),
    new NullAPMSender(),
    new \bus\factory\FTags()
);

$messageBus->dispatch(new \bus\impl\TEvent(1));
