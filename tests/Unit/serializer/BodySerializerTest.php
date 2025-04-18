<?php

namespace Tests\Unit\serializer;

use bus\serializer\BodySerializer;
use PHPUnit\Framework\TestCase;
use Tests\unit\TEvent;

class BodySerializerTest extends TestCase
{
    private BodySerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new BodySerializer();
    }

    public function testToJson(): void
    {
        $event = new TEvent();
        $event->setId(1);

        $json = $this->serializer->toJson($event);

        $this->assertSame('["Tests\\\Unit\\\TEvent",{"id":1}]', $json);
    }
}
