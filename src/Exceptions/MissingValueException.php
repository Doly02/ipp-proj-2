<?php

namespace IPP\Student\Exceptions;
use IPP\Core\Exception\IPPException;

use Throwable;

/**
 * Exception for output file errors
 */
class MissingValueException extends IPPException
{
    public function __construct(string $message = "Missing Value Exception", ?Throwable $previous = null)
    {
        parent::__construct($message, 56, $previous, false);
    }
}
