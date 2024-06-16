<?php
/******************************
 *  Project:        IPP Project 2 - Interpret of IPPcode24
 *  File Name:      FlowControl.php
 *  Author:         Tomas Dolak
 *  Date:           25.02.2024
 *  Description:    Implements Interpret Of IPPcode24 With Use Of IPPcore.
 *
 * ****************************/

 /**
 *  @package        IPP Project 2 - Interpret of IPPcode24
 *  @author         Tomas Dolak
 * ****************************/


namespace IPP\Student;

use IPP\Student\Exceptions\SemanticControlException;        
/**
 * Controls the flow of the program execution based on instructions.
 */
class FlowControl
{
    /** @var int|string The current position in the instruction list */
    public int|string $current = 0;
    /** @var int index */
    public int $index = 0;
    /** @var array<string> Stack for program's "jump" operations */
    public array $germs = [];
    /** @var array<string> Stack for program's "call" operations */
    public array $labels = [];

    /**
     * Extracts labels from a list of instructions and populates the labels array.
     *
     * @param array<Instruction> $inList The list of instructions from which to extract labels.
     * @throws SemanticControlException If a duplicate label is found.
     */
    public function extractLabels(array $inList): void
    {
        foreach ($inList as $item) {
            if (strtoupper($item->operationCode) === "LABEL") {
                if (!array_key_exists($item->operands[0]->value, $this->labels)) {
                    $this->labels[$item->operands[0]->value] = $item->order;
                } else {
                    throw new SemanticControlException();
                }
            }
        }
    
    }
    /**
     * Increments the current instruction pointer.
     */
    public function incrementCurrent() : void
    {
        $this->current++;
    }
}
