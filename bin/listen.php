<?php

require __DIR__ . '/../vendor/autoload.php';

use bus\config\Connection;
use bus\config\Provider;
use bus\factory\FListener;
use bus\factory\FTags;
use bus\impl\ConsoleLogger;
use bus\impl\NullAPMSender;
use bus\MessageBus;

(new FListener())->create(
    'test',
    '/tmp/bus',
    new MessageBus(
        new Connection('127.0.0.1'),
        new Provider(include __DIR__ . '/../src/impl/config.php'),
        new ConsoleLogger(),
        new NullAPMSender(),
        new FTags()
    ),
    new ConsoleLogger(),
    new NullAPMSender()
)->listen();
