<?php

namespace bus\serializer;

use JsonSerializable;

/**
 * Serialize event into special array
 */
class BodySerializer implements BodySerializerInterface
{
    public function toJson(JsonSerializable $target): string
    {
        $out = [
            get_class($target),
            $target,
        ];

        return json_encode($out);
    }
}
