<?php
/******************************
 *  Project:        IPP Project 2 - Interpret of IPPcode24
 *  File Name:      Operand.php
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


class Operand
{
    public mixed $frame;
    public ?string $type = null;
    public ?string $value = null;

    /**
     * Class Constructor
     */
    public function __construct(string $type, string $value, mixed $frame)
    {

        $this->type = $type;
        $this->value = $value;
        $this->frame = $frame;
    }
}
