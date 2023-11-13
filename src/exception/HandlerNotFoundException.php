<?php

namespace bus\exception;

use Exception;
use Throwable;

class HandlerNotFoundException extends Exception
{

    public function __construct($class, $method, $code = 0, Throwable $previous = null)
    {
        $message = $class . '::' . $method;
        parent::__construct($message, $code, $previous);
    }
}
