<?php
/******************************
 *  Project:        IPP Project 2 - Interpret of IPPcode24
 *  File Name:      Instruction.php
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

use Exception;
use IPP\Core\Interface\InputReader;
use IPP\Core\Interface\OutputWriter;
use IPP\Student\Exceptions\SemanticControlException;        
use IPP\Student\Exceptions\AccessNonExistingVarException;
use IPP\Student\Exceptions\WrongXmlException;
use IPP\Student\Exceptions\AccessNonExistingLabelException;
use IPP\Student\Exceptions\MissingValueException;
use IPP\Student\Exceptions\UnexpectedStructureXml;
use IPP\Student\Exceptions\WrongOperandTypeException;
use IPP\Student\Exceptions\WrongOperandValueException;
use IPP\Student\Exceptions\WrongOperationWstring;
use Throwable;
use TypeError;

class Instruction 
{
    public string $operationCode;
    public mixed $operands;
    public mixed $order;

    protected InputReader $stdin;
    protected OutputWriter $stdout;
    protected Frame $frames;
    protected mixed $label;
    protected mixed $expect = null;
    protected FlowControl $flow;

    public function __construct(string $opcode, mixed $operands, mixed $order, Frame $frame, mixed $label,FlowControl $flowControl,InputReader $input, OutputWriter $output)
    {
        $this->operationCode = $opcode;
        $this->operands = $operands;
        $this->order = $order;
        $this->frames = $frame;
        $this->label = $label;
        $this->flow = $flowControl;
        $this->stdin = $input;
        $this->stdout = $output;
    }   

    /**
     * Returns Value Of Operand.
     * 
     * @param int $index Index Of Operand
     * @return string|int|bool|null Value Of Operand
     */
    public function getValue(int $index) : string|int|bool|null
    {

        if (isset($this->operands[$index]))
        {
            if ($this->operands[$index]->type === "var") 
            {
                try{
                    $value = $this->frames->getVariableValue($this->operands[$index]->value, $this->operands[$index]->frame);
                }
                catch (Exception $e)
                {
                    throw new MissingValueException();
                }
            }
            else 
            {
                $value = $this->operands[$index]->value;
            }
            if (null !== $value)
                return $value;
            else    
                throw new MissingValueException();
        }
        else 
        {
            throw new AccessNonExistingVarException();
        }
    }

    /**
     * Returns Type Of Operand.
     * 
     * @param int $index Index Of Operand
     * 
     */
    public function getType(int $index) : ?string
    {
        if (isset($this->operands[$index]))
        {
            try {
                if ($this->operands[$index]->type === "var") {
                    $type = $this->frames->getVariableType($this->operands[$index]->value, $this->operands[$index]->frame);
                } else {
                    $type = $this->operands[$index]->type;
                }
                return $type;
            } catch (TypeError $e) 
            {
                throw new MissingValueException();
            }
        } 
        else {
            throw new AccessNonExistingVarException();
        }
    }

    public function operandChecker() : void
    {
        if ($this->operands === null)
        {
            throw new MissingValueException();
        }
        $opNumber = 0;
        // Count Number Of Operands
        for ($idx = 0; $idx < count($this->operands); $idx++)
        {
            if (isset($this->operands[$idx]))
            {
                $opNumber++;
            }
        }

        if (!isset($this->operands[0]) && (isset($this->operands[1]) || isset($this->operands[2])))
        {
            // TODO: Check The Exception Code 
            throw new MissingValueException();
        }
        foreach ($this->operands as $operand) {
            if ($operand !== null) {
                if ($operand->type === "var" && !$this->frames->canAccess($this->operationCode, $operand->value, $operand->frame)) 
                {
                    throw new AccessNonExistingVarException();
                }
    
                if ($operand->type === 'string') {
                    if ($operand->value === null) {
                        $operand->value = '';
                    }

                    $operand->value = Utils::replaceEscapeSequences($operand->value);
    
                }
            }
        }
    }

    public function processInstruction(mixed &$dataStack)   : void
    {
        $instruction = strtoupper($this->operationCode);
        switch ($instruction) 
        {
            /*      DATA-STACK INSTRUCTIONS     */
            case "MOVE":
                $this->expect = ["var", "symb"];
                $this->operandChecker();
                
                $val = $this->getValue(1);
                $type = $this->getType(1);
                if ($type !== 'nil' && $type !== 'bool' && $type !== 'int' && $type !== 'string' && $type !== 'var') {
                    throw new WrongOperandTypeException();
                }
                $this->frames->setValue($this->operands[0]->frame,$this->operands[0]->value,$type,$val);
                $this->flow->incrementCurrent();
                break;

            case "PUSHS":
                $this->expect = ["symb"];
                $this->operandChecker();
                $op = $this->operands[0];
                if ($op->type === "var")
                {
                    $val = $this->frames->getVariableValue($op->value,$op->frame);
                    array_unshift($dataStack, $val);
                }
                else
                {
                    // Push Directly Value of Constant
                    array_unshift($dataStack, $op->value);
                }
                $this->flow->incrementCurrent();
                break;

            case "POPS":
                $this->expect = ["var"];
                $this->operandChecker();
                if (count($dataStack) === 0)
                {
                    // Stack Is Empty TODO:
                    throw new AccessNonExistingLabelException();
                }
                else
                {
                    $this->frames->setValue($this->operands[0]->frame, $this->operands[0]->value, null, $dataStack[0]);
                    array_shift($dataStack);
                }
                $this->flow->incrementCurrent();
                break;

            /*      FRAME-STACK INSTRUCTIONS & FUNCTION CALLS       */
            case "CREATEFRAME":
                $this->expect = [];
                $this->operandChecker();
                $this->frames->createTemporaryFrame();
                $this->flow->incrementCurrent();
                break;

            case "PUSHFRAME":
                $this->expect = [];
                $this->operandChecker();
                $this->frames->pushFrame();
                $this->flow->incrementCurrent();
                break;

            case "POPFRAME":
                $this->expect = [];
                $this->operandChecker();
                $this->frames->popFrame();
                $this->flow->incrementCurrent();
                break;

            case "CALL":
                $this->expect = ["label"];
                $this->operandChecker();
                if ($this->operands[0]->type !== "label")
                {
                    throw new WrongOperandTypeException();
                }
                $val = $this->operands[0]->value;
                if (!array_key_exists($val, $this->flow->labels)) {
                    throw new SemanticControlException();
                } 
                break;
            case "RETURN":
                $this->expect = [];
                $this->operandChecker();
                if (count($this->flow->germs) == 0) 
                {
                    throw new MissingValueException();
                }
                // TODO:
                $this->flow->current = $this->flow->germs[0];
                array_shift($this->flow->germs);
                break;

            case "DEFVAR":
                $this->expect = ["var"];
                $this->operandChecker();
                $var = new Variable($this->operands[0]->value, $this->operands[0]->frame);
                if (!$this->frames->variableExists($this->operands[0]->value, $this->operands[0]->frame))
                {
                    if($var->frame === "GF")
                    {
                        $this->frames->addToGlobalFrame($var);
                    }
                    else if ($var->frame === "TF" && $this->frames->temporaryFrame !== null)
                    {
                        if (!in_array($var, $this->frames->temporaryFrame, true))
                        {
                            $this->frames->addToTempFrame($var);
                        }
                        else
                        {
                            throw new SemanticControlException();
                        }
                    }
                    else 
                    {
                        if (($this->frames->localFrameCounter) >= 0)
                        {
                            $this->frames->addToLocalFrame($var);
                        }
                        else
                        {
                            throw new AccessNonExistingLabelException();
                        }
                    }
                }
                else
                {
                    // Variable Already Exists
                    throw new SemanticControlException();
                }
                $this->flow->incrementCurrent();
                break;

            /*      ARITHMETIC INSTRUCTIONS       */
            case "ADD":
                $this->expect = ["var", "symb", "symb"];
                $this->operandChecker();
                $opVal1 = $this->getValue(1);
                $opType1 = $this->getType(1);
                $opVal2 = $this->getValue(2);
                $opType2 = $this->getType(2);

                if ($opType1 !== $opType2 || $opType1 === null || $opType1 !== "int")
                {
                    throw new WrongOperandTypeException();
                }

                $res = (int)$opVal1 + (int)$opVal2;
                $this->frames->setValue($this->operands[0]->frame, $this->operands[0]->value, "int", $res);
                $this->flow->incrementCurrent();
                break;

            case "SUB":
                $this->expect = ["var", "symb", "symb"];
                $this->operandChecker();

                $opVal1 = $this->getValue(1);
                $opType1 = $this->getType(1);
                $opVal2 = $this->getValue(2);
                $opType2 = $this->getType(2);

                if ($opType1 !== $opType2 || $opType1 === null || $opType1 !== "int")
                {
                    throw new WrongOperandTypeException();
                }
                $res = (int)$opVal1 - (int)$opVal2;
                $this->frames->setValue($this->operands[0]->frame, $this->operands[0]->value, "int", $res);
                $this->flow->incrementCurrent();
                break;

            case "MUL":
                $this->expect = ["var", "symb", "symb"];
                $this->operandChecker();

                $opVal1 = $this->getValue(1);
                $opType1 = $this->getType(1);
                $opVal2 = $this->getValue(2);
                $opType2 = $this->getType(2);

                if ($opType1 !== $opType2 || $opType1 === null || $opType1 !== "int")
                {
                    throw new WrongOperandTypeException();
                }
                $res = (int)$opVal1 * (int)$opVal2;
                $this->frames->setValue($this->operands[0]->frame, $this->operands[0]->value, "int", $res);
                $this->flow->incrementCurrent();
                break;

            case "IDIV":
                $this->expect = ["var", "symb", "symb"];
                $this->operandChecker();
                $opVal1 = $this->getValue(1);
                $opType1 = $this->getType(1);
                $opVal2 = $this->getValue(2);
                $opType2 = $this->getType(2);

                if ($opType1 !== $opType2 || $opType1 === null || $opType1 !== "int")
                {
                    throw new WrongOperandTypeException();
                }
                if ((int)$opVal2 === 0)
                {
                    throw new WrongOperandValueException();
                }
                $res = intdiv((int)$opVal1,(int)$opVal2);
                $this->frames->setValue($this->operands[0]->frame, $this->operands[0]->value, "int", $res);
                $this->flow->incrementCurrent();
                break;

            case "LT":
                $this->expect = ["var", "symb", "symb"];
                $this->operandChecker();

                $res = "false";
                $opVal1 = $this->getValue(1);
                $opType1 = $this->getType(1);
                $opVal2 = $this->getValue(2);
                $opType2 = $this->getType(2);

                if ($opType1 === null || $opType2 === null)
                {
                    throw new WrongOperandTypeException();
                }
                else if ($opType1 !== $opType2)
                {
                    throw new WrongOperandTypeException();
                }
                else if ($opType1 === "int")
                {
                    $res = (int)$opVal1 < (int)$opVal2 ? "true" : "false";
                }
                $this->frames->setValue($this->operands[0]->frame, $this->operands[0]->value, "bool", $res);
                $this->flow->incrementCurrent();
                break;

            case "GT":
                $this->expect = ["var", "symb", "symb"];
                $this->operandChecker();

                $res = "false";
                $opVal1 = $this->getValue(1);
                $opType1 = $this->getType(1);
                $opVal2 = $this->getValue(2);
                $opType2 = $this->getType(2);

                if ($opType1 === null || $opType2 === null)
                {
                    throw new WrongOperandTypeException();
                }
                else if ($opType1 !== $opType2)
                {
                    throw new WrongOperandTypeException();
                }
                else if ($opType1 === "int")
                {
                    $res = (int)$opVal1 > (int)$opVal2 ? "true" : "false";
                }
                $this->frames->setValue($this->operands[0]->frame, $this->operands[0]->value, "bool", $res);
                $this->flow->incrementCurrent();
                break;

            case "EQ":
                $this->expect = ["var", "symb", "symb"];
                $this->operandChecker();

                $res = "false";
                $opVal1 = $this->getValue(1);
                $opType1 = $this->getType(1);
                $opVal2 = $this->getValue(2);
                $opType2 = $this->getType(2);

                if ($opType1 === null || $opType2 === null)
                {
                    throw new WrongOperandTypeException();
                }
                else if ($opType1 !== $opType2)
                {
                    throw new WrongOperandTypeException();
                }
                else if ($opType1 === "int")
                {
                    $res = (int)$opVal1 === (int)$opVal2 ? "true" : "false";
                }
                $this->frames->setValue($this->operands[0]->frame, $this->operands[0]->value, "bool", $res);
                $this->flow->incrementCurrent();
                break;

            case "AND":
                $this->expect = ["var", "symb", "symb"];
                $this->operandChecker();

                $res = "false";
                $opVal1 = $this->getValue(1);
                $opType1 = $this->getType(1);
                $opVal2 = $this->getValue(2);
                $opType2 = $this->getType(2);
                
                if ($opType1 !== "bool" || $opType1 !== $opType2)
                {
                    throw new WrongOperandTypeException();
                }

                /* Both Has to Be True To Result Be True  */
                if ($opVal1 === "true" && $opVal2 === "true")
                {
                    $res = "true";
                }
                else{
                    $res = "false";
                }
                $this->frames->setValue($this->operands[0]->frame, $this->operands[0]->value, "bool", $res);
                $this->flow->incrementCurrent();
                break;

            case "OR":
                $this->expect = ["var", "symb", "symb"];
                $this->operandChecker();

                $res = "false";
                $opVal1 = $this->getValue(1);
                $opType1 = $this->getType(1);
                $opVal2 = $this->getValue(2);
                $opType2 = $this->getType(2);
                
                if ($opType1 !== "bool" || $opType1 !== $opType2)
                {
                    throw new WrongOperandTypeException();
                }

                if ($opVal1 === "true" || $opVal2 === "true")
                {
                    $res = "true";
                }
                else{
                    $res = "false";
                }
                $this->frames->setValue($this->operands[0]->frame, $this->operands[0]->value, "bool", $res);
                $this->flow->incrementCurrent();
                break;

            case "NOT":
                $this->expect = ["var", "symb"];
                $this->operandChecker();
                $opVal1 = $this->getValue(1);
                $opType1 = $this->getType(1);
                
                $val = "true";
                if ($opType1 !== "bool")
                {
                    throw new WrongOperandTypeException();
                }
                if ($opVal1 === "true")
                {
                    $val = "false";
                }
                $this->frames->setValue($this->operands[0]->frame, $this->operands[0]->value, "bool", $val);
                $this->flow->incrementCurrent();
                break;

            case "INT2CHAR":
                $this->expect = ["var", "symb"];
                $this->operandChecker();
                $opVal1 = $this->getValue(1);
                $opType1 = $this->getType(1);

                if ($opType1 !== "int" || $opVal1 < 0 || $opVal1 > 255)
                {
                    throw new WrongOperandTypeException();
                }
                try {
                    $val = chr((int)$opVal1);
                } catch (Throwable $e) {
                    throw new WrongOperandValueException();
                }
                $this->frames->setValue($this->operands[0]->frame, $this->operands[0]->value, "string", $val);
                $this->flow->incrementCurrent();
                break;

            case "STRI2INT":
                $this->expect = ["var", "symb","symb"];
                $this->operandChecker();
                $opVal1 = $this->getValue(1);
                $opType1 = $this->getType(1);
                $opVal2 = $this->getValue(2);
                $opType2 = $this->getType(2);

                if ($opType1 !== "int" || $opType2 !== "string")
                {
                    throw new WrongOperandTypeException();
                }
                if ((int)$opVal2 < 0 || (int)$opVal2 >= strlen($opVal1))
                {
                    throw new WrongOperandValueException();
                }
                // Convert To Char
                $char = $opVal1[(int)$opVal2];
                try {
                    $val = ord($char);
                } catch (Throwable $e) {
                    throw new WrongOperandValueException();
                }
                $this->frames->setValue($this->operands[0]->frame, $this->operands[0]->value, "int", $val);
                $this->flow->incrementCurrent();
                break;

            /* INPUT-OUTPUT INSTRUCTIONS */
            case "READ":
                $this->expect = ["var", "type"];
                $this->operandChecker();
                $opType2 = $this->operands[1]->value;


                try {
                    if ($opType2 === 'int') {
                        $line = $this->stdin->readInt();
                        $line = (string)$line;
                        if (preg_match('/^(-)?(0b[01]+|0x[\da-fA-F]+|\d+)$/', $line)) {
                            $res = intval($line);
                        } else {
                            $res = 'nil';
                            $opType2 = 'nil';
                        }
                    } elseif ($opType2 === 'bool') {
                        $line = $this->stdin->readBool();
                        $line = (string)$line;
                        if (preg_match('/^(true)$/i', $line)) {
                            $res = 'true';
                        } 
                        else if (preg_match('/^(false)$/i', $line)) {
                            $res = 'false';
                        }
                        else {
                            $res = 'nil';
                            $opType2 = 'nil';
                        }
                    } else {
                        $line = $this->stdin->readString();
                        $res = $line;
                    }
                    $this->frames->setValue($this->operands[0]->frame, $this->operands[0]->value, $opType2, $res);                
                }
                catch (Throwable $e) {
                    $this->frames->setValue($this->operands[0]->frame, $this->operands[0]->value, 'nil', 'nil');
                }

                $this->flow->incrementCurrent();
                break;

            case "WRITE":
                $this->expect = ["symb"];
                $this->operandChecker();
                $opVal0 = $this->getValue(0);
                $opType0 = $this->getType(0);
                if ($opType0 === "nil") {
                    echo "";
                }
                else{
                    if ($opType0 === "bool")
                    {
                        $this->stdout->writeBool($opVal0);
                    }
                    else if ($opType0 === "int")
                        $this->stdout->writeInt($opVal0);
                    else if ($opType0 === "string")
                        $this->stdout->writeString($opVal0);
                }
                $this->flow->incrementCurrent();
                break;

            /*      STRING PROCESSING INSTRUCTIONS          */
            case "CONCAT":
                $this->expect = ['var', 'symb', 'symb'];
                $this->operandChecker();
                //echo "CONCAT\n";
                $opVal1 = $this->getValue(1);
                $opType1 = $this->getType(1);
                $opVal2 = $this->getValue(2);
                $opType2 = $this->getType(2);

                if ($opType1 !== "string" || $opType2 !== "string")
                {
                    //echo "Exeption 2\n";
                    throw new WrongOperandTypeException();
                }
                if ($opVal1 === null)
                {
                    $opVal1 = "";
                }
                if ($opVal2 === null)
                {
                    $opVal2 = "";
                }
                $res = $opVal1 . $opVal2;
                $this->frames->setValue($this->operands[0]->frame, $this->operands[0]->value, "string", $res);
                $this->flow->incrementCurrent();
                break;

            case "STRLEN":
                $this->expect = ['var', 'symb'];
                $this->operandChecker();

                $opVal1 = $this->getValue(1);
                $opType1 = $this->getType(1);
                if ($opType1 !== "string")
                {
                    //echo "Exeption 13\n";
                    throw new WrongOperandTypeException();
                }
                if ($opVal1 === null || $opVal1 === "")
                {
                    $res = 0;
                }
                else
                {
                    //echo "STRLEN opVal1: " . $opVal1 . "\n";
                    $res = strlen($opVal1);
                }
                $this->frames->setValue($this->operands[0]->frame, $this->operands[0]->value, "int", $res);
                $this->flow->incrementCurrent();
                break;

            case "GETCHAR":
                $this->expect = ['var', 'symb', 'symb'];
                $this->operandChecker();
                $opVal1 = $this->getValue(1);
                $opType1 = $this->getType(1);

                $opVal2 = $this->getValue(2);
                $opType2 = $this->getType(2);
    
                if ($opType1 !== "string" || $opType2 !== "int")
                {
                    throw new WrongOperandTypeException();
                }

                if ((int)$opVal2 >= strlen($opVal1) || (int)$opVal2 < 0)
                {
                    throw new WrongOperationWstring();
                }

                $res = $opVal1[(int)$opVal2];
                $this->frames->setValue($this->operands[0]->frame, $this->operands[0]->value, "string", $res);
                $this->flow->incrementCurrent();
                break;
            case "SETCHAR":
                $this->expect = ['var', 'symb', 'symb'];
                $this->operandChecker();
                $opVal0 = $this->getValue(0);
                $opType0 = $this->getType(0);
                $opVal1 = $this->getValue(1);
                $opType1 = $this->getType(1);
                $opVal2 = $this->getValue(2);
                $opType2 = $this->getType(2);

                if ($opType0 !== "string" || $opType1 !== "int" || $opType2 !== "string")
                {
                    throw new WrongOperationWstring();
                }
                $position = (int)$opType1;
                $nc = $opType2[0];
                // Replace Character on Defined Position
                $opVal0 = substr_replace($opVal0, $nc, $position, 1); 
                $this->frames->setValue($this->operands[0]->frame, $this->operands[0]->value, "string", $opVal0);
                $this->flow->incrementCurrent();
                break;

            /*      TYPE PROCESSING INSTRUCTIONS            */
            case "TYPE":
                $this->expect = ['var', 'symb'];
                $this->operandChecker();
                $opVal1 = $this->getValue(1);
                $opType1 = $this->getType(1);
                $this->frames->setValue($this->operands[0]->frame, $this->operands[0]->value, "string", $opType1);
                break;

            /*     FLOW CONTROL INSTRUCTIONS                */
            case "LABEL":
                $this->expect = ['label'];
                $this->operandChecker();
                $this->flow->incrementCurrent();
                break;

            case "JUMP":
                $this->expect = ['label'];
                $this->operandChecker();

                $labelName = $this->getValue(0);
                if (!array_key_exists($labelName, $this->flow->labels)) {
                    throw new SemanticControlException();
                } 
                $this->flow->current = $this->flow->labels[$labelName];
                break;

            case "JUMPIFEQ":
                $this->expect = ['label', 'symb', 'symb'];
                $this->operandChecker();
                $labelName = $this->operands[0]->value;
                $opVal1 = $this->getValue(1);
                $opType1 = $this->getType(1);
                $opVal2 = $this->getValue(2);
                $opType2 = $this->getType(2);
                $val = "false";
                if ($opType1 !== $opType2 && ($opType1 !== "nil") && ($opType1 !== "nil"))
                {
                    //echo "Exeption 3\n";
                    throw new WrongOperandTypeException();
                }
                if (!array_key_exists($labelName, $this->flow->labels)) {
                    //echo "Exeption 4\n";
                    throw new WrongOperandTypeException();
                }
                if ($opType1 == "int" || $opType2 == "int")
                {
                    if ($opType1 == "nil" || $opType2 == "nil")
                        $val = "false";
                    else
                        $val = (int)$opVal1 === (int)$opVal2;
                }
                else
                {
                    $val = ($opVal1 === $opVal2);   
                }
                if ($val === true)
                {
                    $this->flow->current = $this->flow->labels[$labelName];
                }
                else
                {
                    $this->flow->incrementCurrent();
                }
                break;
            
            case "JUMPIFNEQ":
                $this->expect = ['label', 'symb', 'symb'];
                $this->operandChecker();
                $labelName = $this->operands[0]->value;
                $opVal1 = $this->getValue(1);
                $opType1 = $this->getType(1);

                $opVal2 = $this->getValue(2);
                $opType2 = $this->getType(2);

                $val = "false";
                if ($opType1 !== $opType2 && ($opType1 !== "nil") && ($opType2 !== "nil"))
                {
                    throw new WrongOperandTypeException();
                }
                if (!array_key_exists($labelName, $this->flow->labels)) {
                    throw new WrongOperandTypeException();
                }
                if ($opType1 == "int" || $opType2 == "int")
                {
                    if ($opType1 == "nil" || $opType2 == "nil")
                        $val = "false";
                    else
                        $val = (int)$opVal1 === (int)$opVal2;
                }
                else
                {
                    $val = ($opVal1 === $opVal2);   
                }
                if ($val === true)
                {
                    $this->flow->incrementCurrent();
                }
                else
                {
                    $this->flow->current = $this->flow->labels[$labelName];
                }
                break;
            
            case "EXIT":
                $this->expect = ['symb'];
                $this->operandChecker();
                $opVal0 = $this->getValue(0);
                $opType0 = $this->getType(0);

                if ($opType0 !== "int")
                {
                    throw new WrongOperandTypeException();
                }
                if ((int)$opVal0 < 0 || (int)$opVal0 > 9)
                {
                    throw new WrongOperandTypeException();
                }
                exit((int)$opVal0);
            /*      DEBUG INSTRUCTIONS                      */
            case "DPRINT":
                $this->expect = ['symb'];
                $this->operandChecker();

                $opVal0 = $this->getValue(0);
                fwrite(STDERR, $opVal0 . "\n");
                $this->flow->incrementCurrent();
                break;
            
            case "BREAK":
                $this->expect = [];
                $this->operandChecker();
                $this->flow->incrementCurrent();
                break;

            default:
                throw new UnexpectedStructureXml();
        }
    }

}