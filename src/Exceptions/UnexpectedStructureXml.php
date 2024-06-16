<?php

namespace IPP\Student\Exceptions;
use IPP\Core\Exception\IPPException;

use Throwable;

/**
 * Exception for output file errors
 */
class UnexpectedStructureXml extends IPPException
{
    public function __construct(string $message = "Wrong Structure of In Input XML File", ?Throwable $previous = null)
    {
        parent::__construct($message, 32, $previous, false);
    }
}
