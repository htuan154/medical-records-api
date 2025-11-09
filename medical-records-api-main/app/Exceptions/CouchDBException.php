<?php

namespace App\Exceptions;

use Exception;

class CouchDBException extends Exception
{
    public function __construct(string $message = 'CouchDB error', int $code = 500)
    {
        parent::__construct($message, $code);
    }
}
