<?php

namespace Tests\unit;

use JsonSerializable;

class TEvent implements JsonSerializable
{
    private int $id;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
