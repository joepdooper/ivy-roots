<?php

namespace Ivy\Exceptions;

use Exception;

class AuthorizationException extends Exception
{
    public function __construct(string $message = 'This action is unauthorized.')
    {
        parent::__construct($message);
    }
}
