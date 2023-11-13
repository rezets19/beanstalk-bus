<?php

namespace bus\serializer;

use JsonSerializable;

/**
 * Serialize event into special array
 *
 * Class BodySerializer
 * @package bus\serializer
 */
class BodySerializer implements IBodySerializer
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
