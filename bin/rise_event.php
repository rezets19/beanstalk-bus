<?php

/**
 * php bin/rise_event.php -h 127.0.0.1 -p 11300 -f src/impl/config.php
 */

require __DIR__.'/../vendor/autoload.php';

use bus\config\Connection;
use bus\config\Provider;
use bus\factory\TagsFactory;
use bus\handler\Handler;
use bus\impl\ConsoleLogger;
use bus\impl\NullAPMSender;
use bus\impl\TEvent;
use bus\MessageBus;

$options = getopt('h:p:f:');

$messageBus =  new MessageBus(
    connection: new Connection($options['h'] ?? '127.0.0.1', $options['p'] ?? 11300),
    configProvider: new Provider(include $options['f']),
    logger: new ConsoleLogger(),
    apm: new NullAPMSender(),
    tagsFactory: new TagsFactory(),
    handler: new Handler(new ConsoleLogger())
);

$messageBus->dispatch(new TEvent(1));
