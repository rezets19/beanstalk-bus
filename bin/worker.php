<?php

/**
 * php bin/worker.php -h 127.0.0.1 -p 11300 -q test -t /var/log/worker.log -f src/impl/config.php
 */

require __DIR__ . '/../vendor/autoload.php';

use bus\config\Connection;
use bus\config\Provider;
use bus\factory\TagsFactory;
use bus\factory\WorkerFactory;
use bus\handler\Handler;
use bus\impl\NullAPMSender;
use bus\MessageBus;
use Programster\Log\FileLogger;

$options = getopt('h:p:q:t:f:');
$logger = new FileLogger($options['t']);

(new WorkerFactory())->create(
    $options['q'],
    $options['t'],
    new MessageBus(
        new Connection($options['h'] ?? '127.0.0.1', $options['p'] ?? 11300),
        new Provider(include $options['f']),
        $logger,
        new NullAPMSender(),
        new TagsFactory(),
        new Handler($logger)
    ),
    $logger,
    new NullAPMSender(),
    new Handler($logger)
)->listen();
