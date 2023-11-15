<?php

use bus\config\Connection;
use bus\config\Provider;
use bus\factory\TagsFactory;
use bus\handler\Handler;
use bus\impl\ConsoleLogger;
use bus\impl\NullAPMSender;
use bus\impl\TEvent;
use bus\MessageBus;

require __DIR__.'/../vendor/autoload.php';

$messageBus =  new MessageBus(
    connection: new Connection('127.0.0.1'),
    configProvider: new Provider(include __DIR__ . '/../src/impl/config.php'),
    logger: new ConsoleLogger(),
    apm: new NullAPMSender(),
    tagsFactory: new TagsFactory(),
    handler: new Handler(new ConsoleLogger())
);

$messageBus->dispatch(new TEvent(1));
