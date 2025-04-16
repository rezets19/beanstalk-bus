<?php

namespace bus\impl;

use Psr\Log\LoggerInterface;
use Stringable;

class ConsoleLogger implements LoggerInterface
{
    public function emergency(Stringable|string $message, array $context = []): void
    {
        $this->print($message);
    }

    public function alert(Stringable|string $message, array $context = []): void
    {
        $this->print($message);
    }

    public function critical(Stringable|string $message, array $context = []): void
    {
        $this->print($message);
    }

    public function error(Stringable|string $message, array $context = []): void
    {
        $this->print($message);
    }

    public function warning(Stringable|string $message, array $context = []): void
    {
        $this->print($message);
    }

    public function notice(Stringable|string $message, array $context = []): void
    {
        $this->print($message);
    }

    public function info(Stringable|string $message, array $context = []): void
    {
        $this->print($message);
    }

    public function debug(Stringable|string $message, array $context = []): void
    {
        $this->print($message);
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->print($message);
    }

    public function print(string $message)
    {
        echo $message . "\n";
    }
}
