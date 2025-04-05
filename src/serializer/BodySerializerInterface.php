<?php

namespace bus\serializer;

use JsonSerializable;

interface BodySerializerInterface
{
    public function toJson(JsonSerializable $target) : string;
}
