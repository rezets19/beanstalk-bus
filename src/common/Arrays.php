<?php

namespace bus\common;

use Throwable;

class Arrays
{
    public function classExist(Throwable $t, array $data): bool
    {
        return in_array(get_class($t), $data, true);
    }
}
