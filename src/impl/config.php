<?php

use bus\broker\BuryStrategy;
use bus\impl\TEvent;
use bus\impl\TEventHandler;

return [
    TEvent::class => [
        'async' => true,
        'queue_config' => [
            'name' => 'test',
            'driver' => 'beanstalk',
            'delay' => 60,
            'ttr' => 60,
            'maxAge' => 60,
            'maxKicks' => 3,
            'maxRetry' => 0,
            'buryStrategy' => BuryStrategy::class,
        ],
        'handlers' => [
            [TEventHandler::class, 'handle'],
            [TEventHandler::class, 'handle2'],
        ],
        'exceptions' => [
            'fatal' => [
                \Exception::class,
            ],
        ],
    ],
];
