<?php

/**
 * Description of Helper
 * 
 * PHP Version 7.2.9
 *
 * @category PHP
 * @package  JSON_Flatner
 * @author   Aditya Mittal <scientificchess@gmail.com>
 * @license  Aditya Mittal
 * @link     https://github.com/adimittal/JSON_Flatner
 */

namespace JSONFlatner;

/**
 * Helper functions for the JSON Flatner
 */
class Helper
{
    /**
     * Adds the id to the object if it doesn't already have an id
     * if id is not specified, generates and adds a uuid
     *
     * @param type $obj - object to check
     * @param type $id  - id to add if not exists
     *
     * @return $obj
     */
    public function addObjectId($obj, $id = '')
    {
        $id = $id ? $id : $this->genUUID();
        if (is_object($obj) && !isset($obj->id)) {
            $obj->id = $id;
        }
        return $obj;
    }

    /**
     * Get a v4 UUID
     *
     * @return uuid
     */
    public function genUUID()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Helper to remove endlines
     *
     * @param string $buffer - the buffer to remove the endlines from
     *
     * @return string with no endlines
     */
    public function removeEndlines($buffer)
    {
        return str_replace(array("\r", "\n"), '', $buffer);
    }

    /**
     * Helper to remove endlines and read in contents of json file
     *
     * @param string $fileName - the file to read
     *
     * @return string with no endlines
     */
    public function readJsonFileWithoutEndlines($fileName)
    {
        return $this->removeEndlines(json_encode(json_decode(file_get_contents($fileName))));
    }

    /**
     * Just to help during debugging test printouts
     * 
     * @param type $obj - object to print
     * 
     * @return null
     */
    private function quickPrint($obj)
    {
        fwrite(STDOUT, print_r($obj, true));
    }
}
