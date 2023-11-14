<?php

namespace Tests\Unit\message;

use bus\message\FQMessage;
use PHPUnit\Framework\TestCase;

class FQMessageTest extends TestCase
{
    private FQMessage $factory;

    protected function setUp(): void
    {
        $this->factory = new FQMessage();
    }

    public function testFromString(): void
    {
        $message = $this->factory->fromString(file_get_contents(__DIR__ . '/message1.txt'));

        $this->assertIsArray($message->getHeaders());
    }

    public function testFromStringAndHeader(): void
    {
        $message = $this->factory->fromString(file_get_contents(__DIR__ . '/message_w_header.txt'));

        $this->assertIsArray($message->getHeaders());
    }

    public function testFromStringEmptyBody(): void
    {
        $message = $this->factory->fromString(file_get_contents(__DIR__ . '/message_empty_body.txt'));

        $this->assertIsArray($message->getHeaders());
    }
}
