<?php

namespace bus\message;

use bus\serializer\BodySerializerInterface;
use Interop\Queue\Impl\MessageTrait;
use Interop\Queue\Message;
use JsonSerializable;

/**
 * Envelope for event or command
 */
class QMessage implements Message, JsonSerializable
{
    use MessageTrait;
    private ?int $timeToRun = null;
    private ?int $delay = null;
    private ?int $priority = null;
    private ?string $queue = null;

    public function __construct(
        private JsonSerializable $job,
        private BodySerializerInterface $serializer,
        array $properties = [],
        array $headers = []
    ) {
        $this->properties = $properties;
        $this->headers = $headers;
        $this->redelivered = false;
    }

    public function getBody(): string
    {
        return $this->serializer->toJson($this->job);
    }

    /** @inheritDoc */
    public function jsonSerialize(): array
    {
        return [
            'body' => $this->getBody(),
            'properties' => $this->getProperties(),
            'headers' => $this->getHeaders(),
        ];
    }

    public function setTimeToRun(int $time)
    {
        $this->timeToRun = $time;
    }

    public function getTimeToRun(): ?int
    {
        return $this->timeToRun;
    }

    public function setDelay(int $time)
    {
        $this->delay = $time;
    }

    public function getDelay(): ?int
    {
        return $this->delay;
    }

    public function getJob(): object
    {
        return $this->job;
    }

    public function setPriority(int $priority)
    {
        $this->priority = $priority;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setQueue(string $queue)
    {
        $this->queue = $queue;
    }

    public function getQueue(): ?string
    {
        return $this->queue;
    }

    public function addHandler(array $item)
    {
        $this->setHeader('handler', $item);
    }

    public function getHandler(): array
    {
        $handler = $this->getHeader('handler', null);

        if (null !== $handler) {
            return (array)$handler;
        }

        return [];
    }

    public function setUid(string $uid)
    {
        $this->setHeader('uid', $uid);
    }

    public function getUid()
    {
        return $this->getHeader('uid');
    }
}
