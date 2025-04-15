<?php

namespace Tests\Unit\message;

use bus\message\QMessageFactory;
use PHPUnit\Framework\TestCase;

class QMessageFactoryTest extends TestCase
{
    private QMessageFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new QMessageFactory();
    }

    public function testFromString(): void
    {
        $message = $this->factory->fromString(file_get_contents(__DIR__ . '/message.json'));

        $this->assertIsArray($message->getHeaders());
    }

    public function testFromStringAndHeader(): void
    {
        $message = $this->factory->fromString(file_get_contents(__DIR__ . '/message_w_header.json'));

        $this->assertIsArray($message->getHeaders());
    }

    public function testFromStringEmptyBody(): void
    {
        $message = $this->factory->fromString(file_get_contents(__DIR__ . '/message_empty_body.json'));

        $this->assertIsArray($message->getHeaders());
    }
}
