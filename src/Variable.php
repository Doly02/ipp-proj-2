<?php
/******************************
 *  Project:        IPP Project 2 - Interpret of IPPcode24
 *  File Name:      Variable.php
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
class Variable 
{
    public string $name;
    public mixed $frame;
    public ?string $type = null;
    public ?string $value = null;

    /**
     * Class Constructor.
     *
     * Initialize Its Variable Name And Its Frame.
     * 
     *
     * @param string $name Variable Name
     * @param string $frame Variable Frame
     * @return void
     */
    public function __construct(string $name, mixed $frame)
    {
        $this->name = $name;
        $this->frame = $frame;
    }
    
    /**
    * Return Variable Type.
    *
    * @return ?string Default Returns Variable Type, Of Variable Does Not Have a Type Returns null.
    */
    public function getType(): ?string
    {
        return $this->type;
    }
    
    /**
     * Sets Variable Type.
     * 
     * @param string $type Inserted Type
     * @return void
     */
    public function setType(?string $type, string $value) : void
    {
        if ($type === null) {
            if (preg_match('/^[0-9]+$/', $value)) {
                $this->type = 'int';
            } elseif (preg_match('/^(true|false)$/', $value)) {
                $this->type = 'bool';
            } elseif (preg_match('/^(nil)$/', $value)) {
                $this->type = 'nil';
            } else {
                $this->type = 'string';
            }
        } else {
            $this->type = $type;
        }
    }
    /**
    * Return Variable Value.
    *
    * @return string|null Default Returns Variable Value, Of Variable Does Not Have a Value Returns null.
    */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Sets Variable Value.
     * 
     * @param mixed $value Inserted Value
     * @return void
     */
    public function setValue(mixed $value,mixed $type)
    {
        $this->value = $value;
        
        if ($type === null) {
            if (preg_match('/^[0-9]+$/', strval($value))) {
                $this->type = 'int';
            } elseif (preg_match('/^(true|false)$/', strval($value))) {
                $this->type = 'bool';
            } elseif (preg_match('/^(nil)$/', strval($value))) {
                $this->type = 'nil';
            } else {
                $this->type = 'string';
            }
        } else {
            $this->type = $type;
        }
    }
}
