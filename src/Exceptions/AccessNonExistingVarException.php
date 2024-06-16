<?php

namespace IPP\Student\Exceptions;
use IPP\Core\Exception\IPPException;
use Throwable;

/**
 * Exception for output file errors
 */
class AccessNonExistingVarException extends IPPException
{
    public function __construct(string $message = "Access To Non-Existing Variable", ?Throwable $previous = null)
    {
        parent::__construct($message, 54, $previous, false);
    }
}

