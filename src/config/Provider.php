<?php

namespace bus\config;

use Psr\EventDispatcher\ListenerProviderInterface;

class Provider implements ListenerProviderInterface
{
    private array $config;

    private array $cached = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $class
     * @return ConfigDto
     * @throws ConfigNotFoundException
     */
    public function getByClass(string $class): ConfigDto
    {
        if (isset($this->config[$class])) {
            if (!isset($this->cached[$class])) {
                $this->cached[$class] = (new FConfigDto())->fromResult($this->config[$class], $class);
            }

            return $this->cached[$class];
        }

        throw new ConfigNotFoundException($class);
    }

    /**
     * @param object $job
     * @return ConfigDto
     * @throws ConfigNotFoundException
     */
    public function getByJob(object $job): ConfigDto
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
            return (new FConfigDto())->fromResult($this->config[$class], $class)->getHandlers();
        }

        throw new ConfigNotFoundException($class);
    }
}
