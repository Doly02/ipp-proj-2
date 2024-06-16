<?php
/******************************
 *  Project:        IPP Project 2 - Interpret of IPPcode24
 *  File Name:      Utils.php
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