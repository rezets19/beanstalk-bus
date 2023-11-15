<?php

require __DIR__ . '/../vendor/autoload.php';

use bus\config\Connection;
use bus\config\Provider;
use bus\factory\ListenerFactory;
use bus\factory\TagsFactory;
use bus\impl\ConsoleLogger;
use bus\impl\NullAPMSender;
use bus\MessageBus;

(new ListenerFactory())->create(
    'test',
    '/tmp/bus',
    new MessageBus(
        new Connection('127.0.0.1'),
        new Provider(include __DIR__ . '/../src/impl/config.php'),
        new ConsoleLogger(),
        new NullAPMSender(),
        new TagsFactory()
    ),
    new ConsoleLogger(),
    new NullAPMSender()
)->listen();
