<?php

namespace Tests\Unit\factory;

use bus\config\Config;
use bus\factory\TagsFactory;
use PHPUnit\Framework\TestCase;

class TagsFactoryTest extends TestCase
{
    private TagsFactory $factory;

    public function setUp(): void
    {
        $this->factory = new TagsFactory();
    }

    public function test_create(): void
    {
        $config = new Config();
        $config->setClass(get_class($this));

        $res = $this->factory->create($config);

        $this->assertSame('TagsFactoryTest', $res['event_name']);
    }
}
