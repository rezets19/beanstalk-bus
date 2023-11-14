<?php

namespace Tests\Unit\factory;

use bus\config\ConfigDto;
use bus\factory\FTags;
use PHPUnit\Framework\TestCase;

class FTagsTest extends TestCase
{
    private FTags $factory;

    public function setUp(): void
    {
        $this->factory = new FTags();
    }

    public function test_create(): void
    {
        $config = new ConfigDto();
        $config->setClass(get_class($this));

        $res = $this->factory->create($config);

        $this->assertSame('FTagsTest', $res['event_name']);
    }
}
