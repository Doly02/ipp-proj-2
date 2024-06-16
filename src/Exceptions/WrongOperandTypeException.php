<?php

namespace IPP\Student\Exceptions;
use IPP\Core\Exception\IPPException;

use Throwable;

/**
 * Exception for output file errors
 */
class WrongOperandTypeException extends IPPException
{
    public function __construct(string $message = "Wrong Type Of Operand", ?Throwable $previous = null)
    {
        parent::__construct($message, 53, $previous, false);
    }
}
