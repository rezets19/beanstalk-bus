<?php

namespace bus\factory;

use bus\config\Config;

class TagsFactory
{
    public function create(Config $config): array
    {
        $class = substr($config->getClass(), strrpos($config->getClass(), '\\') + 1);

        return ['event_name' => $class];
    }
}
