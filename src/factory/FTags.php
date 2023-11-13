<?php

namespace bus\factory;

use bus\config\ConfigDto;

class FTags
{
    public function create(ConfigDto $config): array
    {
        $class = substr($config->getClass(), strrpos($config->getClass(), '\\') + 1);

        return ['event_name' => $class];
    }
}
