<?php

namespace IPP\Student\Exceptions;
use IPP\Core\Exception\IPPException;

use Throwable;

/**
 * Exception for output file errors
 */
class WrongOperandValueException extends IPPException
{
    public function __construct(string $message = "Wrong Operand Value", ?Throwable $previous = null)
    {
        parent::__construct($message, 57, $previous, false);
    }
}
