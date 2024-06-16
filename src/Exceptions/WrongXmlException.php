<?php

namespace IPP\Student\Exceptions;
use IPP\Core\Exception\IPPException;

use Throwable;

/**
 * Exception for output file errors
 */
class WrongXmlException extends IPPException
{
    public function __construct(string $message = "Wrong Format In Input File", ?Throwable $previous = null)
    {
        parent::__construct($message, 31, $previous, false);
    }
}
