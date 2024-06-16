<?php
/******************************
 *  Project:        IPP Project 2 - Interpret of IPPcode24
 *  File Name:      ProcessXML.php
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

use DOMDocument;
use DOMXPath;
use IPP\Core\Interface\InputReader;
use IPP\Core\Interface\OutputWriter;
use IPP\Student\Exceptions\UnexpectedStructureXml;


class Utils 
{
    public static function replaceEscapeSequences(string $s): string 
    {
        $pattern = '/\\\\[0-9]{3}/'; 

        return preg_replace_callback($pattern, function($matches) {
            $escapeSequence = $matches[0];
            $unicodeChar = chr(intval(substr($escapeSequence, 1), 10)); // Removes Back-Slash & Transfer to Int 
            return $unicodeChar;
        }, $s);
    }
}

class ProcessXML
{
    public mixed $inList;
    public mixed $frames;
    public mixed $dataStack;
    public mixed $labels;
    public DOMDocument $dom;
    public FlowControl $flow;
    protected InputReader $in;
    protected OutputWriter $out;

    public function __construct(DOMDocument $xml,InputReader $in,OutputWriter $out)
    {
        $this->inList = [];
        $this->frames = new Frame();
        $this->dataStack = [];
        $this->labels = [];
        $this->dom = $xml;
        $this->flow = new FlowControl();
        $this->in = $in;
        $this->out = $out;
    } 
    
    public function checkStructure() : void
    {
        $root = $this->dom->documentElement;
        if (!($root instanceof \DOMElement) || strtoupper($root->getAttribute('language')) !== "IPPCODE24")
        {   
            throw new UnexpectedStructureXml();
        }
    

        foreach($root->childNodes as $child)
        {
            if ($child instanceof \DOMElement && $child->nodeName == 'instruction')
            {

                $instrOpcode = $child->getAttribute('opcode');
                $instrOrder = $child->getAttribute('order');
            
                if (empty($instrOpcode) || empty($instrOrder) || !preg_match('/^[1-9]\d*$/', $instrOrder))
                {   
                    throw new UnexpectedStructureXml();
                }

                $argCounter = 0;
                foreach ($child->childNodes as $arg)
                {
                    if ($arg->nodeType == XML_ELEMENT_NODE)
                    {
                        $argCounter++;
                        if (!preg_match('/^arg[1-3]$/', $arg->nodeName) || $argCounter > 3)
                        {   
                            throw new UnexpectedStructureXml();
                        }
                    }
                }
            }
        }
    }
    public function extract() : void
    {
        $extractedData = [];
        $xmlPath = new DOMXPath($this->dom);
        $instructions = $xmlPath->query('/program/instruction');
        foreach ($instructions as $child)
        {
            if (!($child instanceof \DOMElement)) continue;
            $instrOpcode = $child->getAttribute('opcode');
            $instrOperands = [null, null, null];
            $instrOrder = intval($child->getAttribute('order'));
            // Extract Arguments of Instruction
            foreach ($child->childNodes as $argNodes)
            {
                if ($argNodes instanceof \DOMElement)  
                {
                    $argIdx = intval(substr($argNodes->nodeName, 3)) - 1;
                    $argIdx = (string)$argIdx;
                    $argIdx = trim($argIdx);
                    $argIdx = (int)$argIdx;
                    $type = $argNodes->getAttribute('type');
                    $type = trim($type);
                    $value = $argNodes->textContent;
                    $value = trim($value);
                    $frame = null;

                    if ($type === 'var' && strpos($value, '@') !== false) {
                        list($frame, $value) = explode('@', $value, 2);
                        $frame = trim($frame); // Trim frame to remove whitespace from beginning and end
                        $value = trim($value); // Trim value to remove whitespace from beginning and end
                    }
                    $instrOperands[$argIdx] = new Operand($type, $value, $frame);
                }
            }
            $extractedData[] = new Instruction($instrOpcode, $instrOperands, $instrOrder, $this->frames,$this->labels,$this->flow,$this->in,$this->out);
        }
        $orderArr = [];
        foreach ($extractedData as $instruction) {
            if (in_array($instruction->order, $orderArr)) {
                throw new UnexpectedStructureXml();
            }
            $orderArr[] = $instruction->order;
        }
        $sequentialOrder = 0;
        foreach ($extractedData as $instruction) {
            $instruction->order = $sequentialOrder++;
        }
        $this->inList = $extractedData;
    }

    public function runInterpret() : void
    {
        $this->checkStructure();
        $this->extract();
        $this->flow->extractLabels($this->inList);
        while ($this->flow->current < count($this->inList))
        {
            $instruction = $this->inList[$this->flow->current];
            if ($instruction === null)
            {
                break;
            }
            else 
                $instruction->processInstruction($this->dataStack);
        }
    }
}
