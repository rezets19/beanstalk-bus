<?php

/**
 * php bin/listen.php -h 127.0.0.1 -p 11300 -q test -t /tmp/bus -f src/impl/config.php
 */

require __DIR__ . '/../vendor/autoload.php';

use bus\config\Connection;
use bus\config\Provider;
use bus\factory\ListenerFactory;
use bus\factory\TagsFactory;
use bus\handler\Handler;
use bus\impl\ConsoleLogger;
use bus\impl\NullAPMSender;
use bus\MessageBus;

$options = getopt('h:p:q:t:f:');

(new ListenerFactory())->create(
    $options['q'],
    $options['t'],
    new MessageBus(
        new Connection($options['h'] ?? '127.0.0.1', $options['p'] ?? 11300),
        new Provider(include $options['f']),
        new ConsoleLogger(),
        new NullAPMSender(),
        new TagsFactory(),
        new Handler(new ConsoleLogger())
    ),
    new ConsoleLogger(),
    new NullAPMSender(),
    new Handler(new ConsoleLogger())
)->listen();
