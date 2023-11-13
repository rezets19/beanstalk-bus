<?php

namespace bus\message;

use bus\config\ConfigDto;
use bus\serializer\BodySerializer;
use Exception;
use JsonMapper\JsonMapperFactory;
use JsonMapper\JsonMapperInterface;
use ReflectionClass;
use ReflectionException;
use function random_int;

class FQMessage
{
    private JsonMapperInterface $mapper;

    private BodySerializer $serializer;

    public function __construct()
    {
        $this->mapper = (new JsonMapperFactory())->default();
        $this->serializer = new BodySerializer();
    }

    /**
     * @param string $job
     * @return QMessage
     * @throws ReflectionException
     */
    public function fromString(string $job): QMessage
    {
        $message = json_decode($job);

        $body = json_decode($message->body);

        if (is_object($body[1])) {
            return new QMessage(
                $this->mapper->mapObject(
                    $body[1],
                    (new ReflectionClass($body[0]))->newInstanceWithoutConstructor()
                ),
                $this->serializer,
                (array)$message->properties,
                (array)$message->headers
            );
        }

        return new QMessage(
            (new ReflectionClass($body[0]))->newInstanceWithoutConstructor(),
            $this->serializer,
            (array)$message->properties,
            (array)$message->headers
        );
    }

    /**
     * @param object $event
     * @param ConfigDto $config
     * @return QMessage
     * @throws Exception
     */
    public function create(object $event, ConfigDto $config): QMessage
    {
        $message = new QMessage($event, $this->serializer);
        $message->setUid((string)random_int(10000000, PHP_INT_MAX));

        return $this->fromConfig($config, $message);
    }

    /**
     * @param ConfigDto $config
     * @param QMessage $message
     * @return QMessage
     */
    public function fromConfig(ConfigDto $config, QMessage $message): QMessage
    {
        if (null === $message->getDelay()) {
            $message->setDelay($config->getDelay());
        }

        if (null === $message->getPriority()) {
            $message->setPriority($config->getPriority());
        }

        if (null === $message->getTimeToRun()) {
            $message->setTimeToRun($config->getTtr());
        }

        if (null === $message->getQueue()) {
            $message->setQueue($config->getQueue());
        }

        return $message;
    }
}
