<?php
/******************************
 *  Project:        IPP Project 2 - Interpret of IPPcode24
 *  File Name:      Interpreter.php
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

use IPP\Core\AbstractInterpreter;

class Interpreter extends AbstractInterpreter
{

    public function execute(): int
    {
        $dom = $this->source->getDOMDocument();
        $processXML = new ProcessXML($dom,$this->input,$this->stdout);
        $processXML->runInterpret();
        exit(0);
    }
}
