<?php

namespace IPP\Student\Exceptions;
use IPP\Core\Exception\IPPException;

use Throwable;

/**
 * Exception for output file errors
 */
class SemanticControlException extends IPPException
{
    public function __construct(string $message = "Semantic Error (Not Defined Label/Variable Redefinition", ?Throwable $previous = null)
    {
        parent::__construct($message, 52, $previous, false);
    }
}
