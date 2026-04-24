<?php

namespace Ivy\Exception;

use Exception;

class AuthorizationException extends Exception
{
    public function __construct(string $message = 'This action is unauthorized.')
    {
        parent::__construct($message);
    }
}
