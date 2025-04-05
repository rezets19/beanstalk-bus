<?php

namespace bus\config;

use Psr\EventDispatcher\ListenerProviderInterface;

class Provider implements ListenerProviderInterface
{
    private array $cached = [];

    public function __construct(private array $config)
    {
    }

    /**
     * @param string $class
     * @return Config
     * @throws ConfigNotFoundException
     */
    public function getByClass(string $class): Config
    {
        if (isset($this->config[$class])) {
            if (!isset($this->cached[$class])) {
                $this->cached[$class] = (new ConfigFactory())->create($this->config[$class], $class);
            }

            return $this->cached[$class];
        }

        throw new ConfigNotFoundException($class);
    }

    /**
     * @param object $job
     * @return Config
     * @throws ConfigNotFoundException
     */
    public function getByJob(object $job): Config
    {
        return $this->getByClass(get_class($job));
    }

    /**
     * @param object $event
     * @return iterable
     * @throws ConfigNotFoundException
     */
    public function getListenersForEvent(object $event): iterable
    {
        $class = get_class($event);

        if (isset($this->config[$class])) {
            return (new ConfigFactory())->create($this->config[$class], $class)->getHandlers();
        }

        throw new ConfigNotFoundException($class);
    }
}
