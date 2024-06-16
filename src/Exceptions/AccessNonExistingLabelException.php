<?php

namespace IPP\Student\Exceptions;
use IPP\Core\Exception\IPPException;
use Throwable;

/**
 * Exception for output file errors
 */
class AccessNonExistingLabelException extends IPPException
{
    public function __construct(string $message = "Access To Non-Existing Label", ?Throwable $previous = null)
    {
        parent::__construct($message, 55, $previous, false);
    }
}
