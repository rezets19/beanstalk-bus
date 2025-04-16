<?php

namespace bus\interfaces;

interface APMSenderInterface
{
    public function metricIncrement(string $name, array $create);
}
