# Beanstalk message bus

Frameworks independent message bus php 8.x library for beanstalkd.  
Beanstalkd is a zero management simple amqp server.   

## Install
``composer require rezets19/beanstalk-bus``

## Create event and handler

Examples:
- Event: src/impl/TEvent.php
- Handler: src/impl/TEventHandler.php

## Start listener
``php bin/listen.php -h 127.0.0.1 -p 11300 -q test -t /tmp/bus -f src/impl/config.php``

## Rise event
``php bin/rise_event.php -h 127.0.0.1 -p 11300 -f src/impl/config.php``
