<?php

namespace bus\handler;

use Closure;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 * Calls event handler (class and method)
 */
class Handler implements HandlerInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * @param object $event
     * @param iterable $handlers
     * @throws ReflectionException
     */
    public function handle(object $event, iterable $handlers): void
    {
        foreach ($handlers as $item) {
            $this->call($event, $item[0], $item[1]);
        }
    }

    /**
     * @param object $event
     * @param mixed $closure string or closure
     * @param string $method
     * @throws ReflectionException
     */
    public function call(object $event, mixed $closure, string $method): void
    {
        if (is_string($closure)) {
            $rc = new ReflectionClass($closure);
            $handler = $rc->newInstance();
            $reflectionMethod = new ReflectionMethod($handler, $method);
            $reflectionMethod->invoke($handler, $event);

            return;
        }

        if ($closure instanceof Closure) {
            $handler = $closure();
            $reflectionMethod = new ReflectionMethod($handler, $method);
            $reflectionMethod->invoke($handler, $event);

            return;
        }
    }
}
