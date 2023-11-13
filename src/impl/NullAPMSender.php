<?php

namespace bus\impl;

use bus\interfaces\APMSenderInterface;

class NullAPMSender implements APMSenderInterface
{

    public function metricIncrement(string $name, array $create)
    {
        // TODO: Implement metricIncrement() method.
    }
}
