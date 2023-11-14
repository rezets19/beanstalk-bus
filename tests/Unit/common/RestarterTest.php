<?php

namespace Tests\Unit\common;

use bus\common\Restarter;
use PHPUnit\Framework\TestCase;
use Tests\Unit\PrivateProperty;

class RestarterTest extends TestCase
{
    use PrivateProperty;

    private Restarter $restartChecker;

    private string $tmpPath;

    private string $file;

    protected function setUp(): void
    {
        $this->tmpPath = sys_get_temp_dir();
        $this->file = $this->tmpPath . '/restart.txt';

        $this->restartChecker = new Restarter($this->tmpPath);

        if (is_file($this->file)) {
            unlink($this->file);
        }

        $this->setMockPropertyObj($this->restartChecker, 'tmpPath', sys_get_temp_dir());
        $this->setMockPropertyObj($this->restartChecker, 'lastCheck', time());
    }

    public function testTimeOutNotExceeded(): void
    {
        $this->setMockPropertyObj($this->restartChecker, 'timeout', 10);

        $res = $this->restartChecker->restart();

        $this->assertFalse($res);
    }

    public function testInitLastUpdate(): void
    {
        $this->setMockPropertyObj($this->restartChecker, 'timeout', 0);

        $res = $this->restartChecker->restart();
        $this->assertFalse($res);
    }

    public function testRestartTrue(): void
    {
        $this->setMockPropertyObj($this->restartChecker, 'timeout', 0);
        $this->restartChecker->restart();

        sleep(1);
        touch($this->file);

        $this->setMockPropertyObj($this->restartChecker, 'timeout', -1);
        $res = $this->restartChecker->restart();

        $this->assertTrue($res);
    }
}
