<?php

namespace IPP\Student\Exceptions;
use IPP\Core\Exception\IPPException;

use Throwable;

/**
 * Exception for output file errors
 */
class WrongOperationWstring extends IPPException
{
    public function __construct(string $message = "Wrong Operation With String", ?Throwable $previous = null)
    {
        parent::__construct($message, 58, $previous, false);
    }
}
