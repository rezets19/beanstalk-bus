<?php

declare(strict_types=1);

namespace bus\config;

class ConfigDto
{
    /**
     * There are sync events too
     * @var bool
     */
    private bool $async;

    /**
     * Name of the queue
     * @var string
     */
    private string $queue;

    /**
     * Only beanstalk supported
     * @var string
     */
    private string $driver;

    /** @var int */
    private int $priority = 0;

    /**
     * Max time in delayed state after that we repeat job
     * @var int
     */
    private int $delay = 0;

    /**
     * Time to run after that job kicked
     * @var int
     */
    private int $ttr = 60;

    /**
     * Array of items, each consists of array with class and method name,
     * method receives typed  event
     * @var array
     */
    private array $handlers;

    /**
     * Number of retries to bury
     * 0 - critical job with no retries
     * @var int
     */
    private int $maxRetry;

    /**
     * Max time or delay in buried state
     * @var int
     */
    private int $maxAge;

    /**
     * After number of kicks from buried - delete job completely
     * @var int
     */
    private int $maxKicks;

    /**
     * Class with IBuryStrategy interfaces
     * defines how to handle buried jobs
     * @var string
     */
    private string $buryStrategy;

    /**
     * @var string
     */
    private string $class;
    private array $fatal = [];

    public function __construct()
    {
    }

    public function setAsync(bool $async): void
    {
        $this->async = $async;
    }

    public function setQueue(string $queue): void
    {
        $this->queue = $queue;
    }

    public function setDriver(string $driver): void
    {
        $this->driver = $driver;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function setDelay(int $delay): void
    {
        $this->delay = $delay;
    }

    public function setTtr(int $ttr): void
    {
        $this->ttr = $ttr;
    }

    public function setHandlers(array $handlers): void
    {
        $this->handlers = $handlers;
    }

    public function isAsync(): bool
    {
        return $this->async;
    }

    public function getQueue(): string
    {
        return $this->queue;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getDelay(): ?int
    {
        return $this->delay;
    }

    public function getTtr(): ?int
    {
        return $this->ttr;
    }

    public function getHandlers(): array
    {
        return $this->handlers;
    }

    public function setMaxRetry(int $maxRetry): void
    {
        $this->maxRetry = $maxRetry;
    }

    public function getMaxRetry(): int
    {
        return (int)$this->maxRetry;
    }

    /**
     * Critical job, no retries
     * @return bool
     */
    public function isCritical(): bool
    {
        return $this->getMaxRetry() < 1;
    }

    public function getMaxAge(): int
    {
        return $this->maxAge;
    }

    public function setMaxAge(int $maxAge): void
    {
        $this->maxAge = $maxAge;
    }

    public function getMaxKicks(): int
    {
        return $this->maxKicks;
    }

    public function setMaxKicks(int $maxKicks): void
    {
        $this->maxKicks = $maxKicks;
    }

    public function getBuryStrategy(): string
    {
        return $this->buryStrategy;
    }

    public function setBuryStrategy(string $buryStrategy): void
    {
        $this->buryStrategy = $buryStrategy;
    }

    public function setClass(string $class): void
    {
        $this->class = $class;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function setFatal(array $fatal): void
    {
        $this->fatal = $fatal;
    }

    /**
     * @return array
     */
    public function getFatal(): array
    {
        return $this->fatal;
    }
}
