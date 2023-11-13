<?php

namespace bus\serializer;

use JsonSerializable;

interface IBodySerializer
{
    public function toJson(JsonSerializable $target) : string;
}
