<?php

namespace bus\impl;

use JsonSerializable;

/**
 * Event example
 *
 * Class TEvent
 * @package bus\impl
 */
class TEvent implements JsonSerializable
{
    /** @var int */
    private $id;

    public function __construct(int $id)
    {
        $this->id = $id;
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
