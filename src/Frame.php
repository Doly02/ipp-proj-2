<?php
/******************************
 *  Project:        IPP Project 2 - Interpret of IPPcode24
 *  File Name:      Frame.php
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
use IPP\Core\Exception\ExecuteReturnCodeException;    
use IPP\Student\Exceptions\AccessNonExistingVarException;
use IPP\Student\Exceptions\AccessNonExistingLabelException;
use IPP\Student\Exceptions\MissingValueException;
use IPP\Student\Exceptions\UnexpectedStructureXml;

class Frame
{
    /**
     * The type of the frame.
     * @var string
     */
    public string $frameType;
    /**
     * Counter for the local frames.
     * @var int
     */
    public int $localFrameCounter;
    /**
     * Storage for global frame variables.
     * @var array<Variable>
     */
    public mixed $globalFrame;
    /**
     * Storage for local frame variables.
     * @var array<array<Variable>>
     */   
    public mixed $localFrame;
     /**
     * Storage for the temporary frame variables, if any.
     * @var array<string,Variable>|null
     */
    public mixed $temporaryFrame;

     /**
     * Initializes the frame with default values.
     *
     * This constructor sets up the frame with initial default values,
     * preparing it for use.
     */
    public function __construct()
    {
        $this->localFrameCounter = 0;
        $this->localFrame = [];
        $this->temporaryFrame = null;
    }

    /**
     * Creates New Temporary Frame.
     * 
     */
    public function createTemporaryFrame(): void
    {
        $this->temporaryFrame = [];
    }

    /**
     * Pushes Temporary Frame To Local Frame.
     * 
     */
    public function pushTempFrame(): void
    {
        /* Check If Temporary Frame Exits */
        if (empty($this->temporaryFrame)) {
            throw new ExecuteReturnCodeException; // TODO: Not Sure If This Is The Right Exception
        }
        $this->localFrameCounter += 1;
        array_unshift($this->localFrame, $this->temporaryFrame); // Adds Temporary Frame In The Beginning Of Array
        $this->temporaryFrame = null;
    }

    /**
     * Pops Local Frame.
     * 
     */
    public function popLocalFrame(): void
    {
        if ($this->localFrameCounter === 0) {
            throw new AccessNonExistingLabelException; // TODO: Not Sure If This Is The Right Exception
        }
        else {
            $this->localFrameCounter -= 1;
            $this->temporaryFrame = $this->localFrame[0];
            array_shift($this->localFrame);
        }
    }

    /**
     * Adds Variable From Frame.
     * 
     * @param Variable $variable Variable Name
     * @return void
     */
    public function addToTempFrame(Variable $variable): void
    {
        if ($this->temporaryFrame === null) {
            fwrite(STDERR, "empty temporary\n");
            throw new AccessNonExistingLabelException; // TODO: Not Sure If This Is The Right Exception
        }

        $this->temporaryFrame[$variable->name] = $variable;
    }

    /**
     * Adds Variable To Global Frame.
     * 
     * @param Variable $variable Variable Name
     * @return void
     */
    public function addToGlobalFrame(Variable $variable): void
    {
        $this->globalFrame[$variable->name] = $variable;
    }

    /**
     * Adds Variable To Local Frame.
     * 
     * @param Variable $variable Variable Name
     * @return void
     */
    public function addToLocalFrame(Variable $variable): void
    {
        $this->localFrame[0][$variable->name] = $variable;
    }

    /**
     * Push Frame Temporary Frame To Local Frame Stack.
     * 
     * @return void
     */
    public function pushFrame(): void
    {
        if ($this->temporaryFrame === null) {
            // Empty Temporary Frame
            throw new AccessNonExistingLabelException; 
        }

        $this->localFrameCounter += 1;
        array_unshift($this->localFrame, $this->temporaryFrame); // Adds Temporary Frame In The Beginning Of Array
        $this->temporaryFrame = null;
    }

    /**
     * Pops Frame From Local Frame Stack.
     * 
     * @return void
     */
    public function popFrame(): void
    {
        if (empty($this->localFrame)) {
            // Empty Temporary Frame
            throw new AccessNonExistingLabelException; 
        }
    
        $this->localFrameCounter -= 1;
        $this->temporaryFrame = array_shift($this->localFrame); // Removes First Frame From Temporary Frame And Stores It To Temp. Frame
    }

    /**
     * Checks If Variable Exists.
     * 
     * @param string $name Variable Name
     * @param string $frame Frame Type
     * @return Bool True If Variable Exists, False If Not
     */
    public function variableExists($name, $frame): bool
    {
        switch ($frame) {
            case 'GF':
                if (isset($this->globalFrame[$name])) {
                    fwrite(STDERR, "Variable Is In Global Frame\n");
                    // TODO:
                    return true;
                }
                break;
            case 'TF':
                if ($this->temporaryFrame !== null && isset($this->temporaryFrame[$name])) {
                    fwrite(STDERR, "Variable Is In Temporary Frame\n");
                    // TODO:
                    return true;
                }
                break;
            case 'LF':
                if (!empty($this->localFrame) && isset($this->localFrame[0][$name])) {
                    return true;
                }
                break;
        }
        return false;
    }

    /**
     * Gets Variable Value.
     * 
     * @param string $name Variable Name
     * @param string $frame Frame Type
     * @return string|int|bool|null Variable Value
     */
    public function getVariableValue(string $name, string $frame) : string|int|bool|null
    {
        switch ($frame) {
            case 'GF':
                if (!isset($this->globalFrame[$name])) {
                    throw new AccessNonExistingVarException();
                }
                return $this->globalFrame[$name]->getValue();
    
            case 'LF':
                if (!empty($this->localFrame)) {
                    if (!isset($this->localFrame[0][$name])) 
                    {
                        // Pristup k neexistující proměnné
                        throw new AccessNonExistingVarException();
                    } else 
                    {
                        return $this->localFrame[0][$name]->getValue();
                    }
                } else 
                {   // Spatny ramec
                    throw new AccessNonExistingVarException();
                }
    
            case 'TF':
                if ($this->temporaryFrame !== null) {
                    if (!isset($this->temporaryFrame[$name])) 
                    {
                        // Spatny ramec nebo promenna neexistuje
                        throw new AccessNonExistingVarException();
                    } else 
                    {
                        return $this->temporaryFrame[$name]->getValue();
                    }
                } 
                else 
                {
                    // Spatny ramec nebo promenna neexistuje
                    throw new AccessNonExistingVarException();
                }
    
            default:
                throw new UnexpectedStructureXml(); 
        }
    }

    public function canAccess(string $operationCode,string $name, string $frame) : bool
    {
        if ($operationCode === "TYPE" || $operationCode == "DEFVAR")
        {
            return true;
        }
        else 
        {
            switch($frame)
            {
                /* Global Frame */
                case "GF":
                    if (isset($this->globalFrame[$name]))   // Variable Is In Global Frame 
                    {
                        return true;
                    }
                    else 
                    {
                        return false;
                    }
                /* Temporary Frame */
                case "TF":
                    if (!isset($this->temporaryFrame)) 
                    {
                        // Temporary Frame Does Not Exists
                        throw new AccessNonExistingLabelException();
                    }

                    if(isset($this->temporaryFrame[$name]))   // Variable Is In Temporary Frame 
                    {
                        return true;
                    }
                    else 
                    {
                        return false;
                    }
                /* Local Frame */
                case "LF": 
                    if (empty($this->localFrame)) 
                    {
                        // Local Frame Does Not Exists
                        throw new AccessNonExistingLabelException();
                    }

                    if(isset($this->localFrame[0][$name]))   // Variable Is In Local Frame 
                    {
                        return true;
                    }
                    else 
                    {
                        return false;
                    }
                default:
                    throw new AccessNonExistingLabelException();
            }
        }
    }
    /**
     * Gets Variable Type.
     * 
     * @param string $variable Variable Name
     * @param string $frame Frame Type
     * @return string|null Variable Type
     */
    public function getVariableType(string|null $variable,string $frame) : string|null
    {
        if ($frame == "GF")
        {
            try{
                return $this->globalFrame[$variable]->getType();
            }
            catch (Exception $e)
            {
                throw new MissingValueException();
            }
        }
        else if ($frame == "TF")
        {
            try{
                return $this->temporaryFrame[$variable]->getType();
            }
            catch (Exception $e)
            {
                throw new MissingValueException();
            }
        }
        else if ($frame == "LF")
        {
            try{
                return $this->localFrame[0][$variable]->getType();
            }
            catch (Exception $e)
            {
                throw new MissingValueException();
            }
        }
        else 
        {
            throw new AccessNonExistingVarException();
        }
    }

    /**
     * Sets Variable Value.
     * 
     * @param string $varName Variable Name
     * @param string $frame Frame Type
     * @param string $type Variable Type
     * @param mixed $value Variable Value
     */
    public function setValue(string $frame,string $varName,string|null $type,mixed $value)  :   void
    {
        if ($frame == 'TF') {
            if ($this->temporaryFrame !== null) {
                if (array_key_exists($varName, $this->temporaryFrame)) 
                {
                    $this->temporaryFrame[$varName]->setValue($value, $type);
                } 
                else 
                {
                    throw new AccessNonExistingLabelException();
                }
            } 
            else 
            {
                throw new AccessNonExistingLabelException();
            }
        }
        else if ($frame == 'LF') 
        {
            if (count($this->localFrame) > 0) {
                if (array_key_exists($varName, $this->localFrame[0])) {
                    $this->localFrame[0][$varName]->setValue($value, $type);
                } else 
                {
                    throw new AccessNonExistingVarException();   
                }
            }
        }
        else if ($frame == 'GF') 
        {
            if (count($this->globalFrame) != 0) {
                if (array_key_exists($varName, $this->globalFrame)) {
                    $this->globalFrame[$varName]->setValue($value, $type);
                } else {
                    throw new AccessNonExistingVarException();
                }
            }
        }
        else 
        {
            throw new AccessNonExistingVarException();
        }
    }
}
