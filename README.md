# Beanstalk message bus

Frameworks independent message bus php 8.x library for beanstalkd.  
Beanstalkd is a zero management message broker, work queue.   

## Install
```sh 
composer require rezets19/beanstalk-bus
```

## Create event and handler

Examples:
- Event: src/impl/TEvent.php
- Handler: src/impl/TEventHandler.php

## Start listener
```sh
php bin/listen.php -h 127.0.0.1 -p 11300 -q test -t /tmp -f src/impl/config.php  
```

## Rise event
```sh 
php bin/rise_event.php -h 127.0.0.1 -p 11300 -f src/impl/config.php
```

## Laravel package
https://github.com/rezets19/laravel-beanstalk-bus

## Systemd worker
Systemd config, change paths before copy.
```sh
copy bin/worker.service.dist /etc/systemd/system/worker.service
```
Systemd documentation: https://jolicode.com/blog/symfony-messenger-systemd or 
```sh
man systemd 
``` 
