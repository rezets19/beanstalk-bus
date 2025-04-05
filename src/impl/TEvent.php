<?php

namespace bus\impl;

use JsonSerializable;

/**
 * Event example
 */
class TEvent implements JsonSerializable
{
    public function __construct(private int $id)
    {
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
