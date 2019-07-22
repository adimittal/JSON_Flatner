<?php

/**
 * Description of Flatner
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

use JSONFlatner\Helper;
use TheSeer\Tokenizer\Exception;

/**
 * Flatten a JSON object and reconstruct it
 *
 * @author Aditya Mittal
 */
class Flatner
{
    /**
     * The contents of the JSON File without the endlines
     */
    private $json;
    /**
     * Object decoded from the JSON
     */
    private $decoded;
    /**
     * Stack for keeping track of the nested json, we pop the stack to ensure each level is flattened
     */
    private $stack = [];
    /**
     * The depth of the stack so we know when to stop popping
     */
    private $stackCount = 0;
    /**
     * A top level key is expected in the json, this is that key
     */
    private $topLevelObjectKey = '';
    /**
     * Directory for output - there are 4 directories inside: csv, dataload, json, and map
     * csv is the flattened csv files
     * dataload is the metadata for loading the csv in db (ex. delimiter, column mapping)
     * json is the flattened json without the nesting
     * map is the column mapping to types used by the framework for creating the table structures
     */
    private $outputDir = 'output';
    /**
     * Inflector is used to make things plural, singular etc. - instance of the inflector class.
     */
    private $inflector;
    /**
     * Helper methods such as getting uuid, adding object id, fixing endlines, printing etc.
     */
    private $_helper;

    /**
     * CONSTRUCTOR:
     * Construct a flatner for the json to flatten and ensure its valid json
     * Input: jsonFile to flatten
     * 
     * @param type $jsonFile - the json file to flatten
     */
    public function __construct($jsonFile)
    {
        $this->_helper = new Helper;
        //Get the jsonFile contents, throw any errors like not finding the file
        try {
            $json = $this->_helper->readJsonFileWithoutEndlines($jsonFile);
        } catch (Exception $e) {
            echo 'Caught exception getting file contents: ', $e->getMessage(), "\r\n";
        }

        //ensure the file contents are valid json
        try {
            $this->ensureValidJson($json);
        } catch (\InvalidArgumentException $e) {
            echo 'Caught exception validating json: ', $jsonFile, "\r\n", $e->getMessage(), "\r\n";
        }

        $this->init($json);
    }

    /**
     * Setup the Flatner
     * @param type $json
     */
    private function init($json)
    {
        //set the valid json into this flatner object
        $this->json = $json;

        //set the decoded json into this
        $this->decoded = json_decode($json); //defaults to depth 512 - depth is an optional 3rd parameter

        //set the top level
        $this->setTopLevel();

        //Setup an inflector
        $this->inflector = new Inflector();
    }

    /**
     * Init the top level json object, we assume there is an object with one top level key
     */
    public function setTopLevel()
    {
        if (is_object($this->decoded) || is_array($this->decoded)) {
            $count = 0;
            foreach ($this->decoded as $k => $v) {
                $this->topLevelObjectKey = $k;
                $count++;
            }
            if ($count > 1) {
                throw new Exception('Top Level Key must be exactly 1');
            }
        }
    }

    /**
     * Get the Key of the top level object
     */
    public function getTopLevelKey()
    {
        return $this->topLevelObjectKey;
    }

    /**
     * If you wish to use a dir other than default
     * @param type $outputDir
     */
    public function setOutputDir($outputDir)
    {
        $this->outputDir = $outputDir;
    }

    /**
     * Get the flat array and output to files
     */
    public function outputFiles()
    {
        $flat = $this->flatten();
        foreach ($flat as $k => $v) {
            file_put_contents($this->outputDir . "/json/$k.json", json_encode($v));
            $this->file_put_csv($k, $v);
            $this->file_put_map($k);
            $this->file_put_dataLoad_config($k);
        }
    }

    /**
     * Create the flat csv file for the given key
     * 
     * @param type $key - the key labelling the flat part of the json
     * @param type $value - the array of rows representing the values for the flat json
     */
    public function file_put_csv($key, $value)
    {
        $csvFile = $this->outputDir . "/csv/$key.csv";
        $jsonFile = $this->outputDir . "/json/$key.json";
        $headers = $this->getHeaders($jsonFile);
        $csv = implode(',', $headers) . "\r\n";
        foreach ($value as $v) {
            $row_r = [];
            foreach ($headers as $h) {
                $row_r[] = isset($v[$h]) && $v[$h] !== null ? '"' . trim($v[$h]) . '"' : '';
            }
            $csv .= implode(',', $row_r) . "\r\n";
        }
        file_put_contents($csvFile, trim($csv));
    }

    /**
     * Generate the dataload configuration for a flat csv file by key
     * 
     * @param type $key - _ separated key describing the flat level from the json
     */
    public function file_put_dataLoad_config($key)
    {

        $outfile = $this->outputDir . "/dataload/$key.dataload.json";
        $jsonFile = $this->outputDir . "/json/$key.json";
        $headers = implode(',', $this->getHeaders($jsonFile));

        $config = <<<HT
        {
            "csv": "$key.csv",
            "table": "cs_$key",
            "headers": true,
            "delim": ",",
            "enclose": "\"",
            "cols": "($headers)"
        }
HT;
        file_put_contents($outfile, $config);
    }

    /** 
     * Get Headers from flat json file 
     * 
     * @param type $jsonFile - the flat json file from which to get the headers
     * */
    public function getHeaders($jsonFile)
    {
        $json_r = json_decode(file_get_contents($jsonFile), true);
        $map = [];
        foreach ($json_r as $row) {
            foreach ($row as $k => $v) {
                $map[$k] = gettype($v);
            }
        }
        return $headers = array_keys($map);
    }

    /**
     * Create the map for Adi's framework so that tables can be autocreated
     * 
     * @param type $key
     */
    public function file_put_map($key)
    {
        $jsonFile = $this->outputDir . "/json/$key.json";
        $mapFile = $this->outputDir . "/map/$key.map";
        $json_r = json_decode(file_get_contents($jsonFile), true);
        $map = [];
        foreach ($json_r as $row) {
            foreach ($row as $k => $v) {
                $map[$k] = gettype($v);
            }
        }
        $map_str = "";
        foreach ($map as  $k => $v) {
            switch ($v) {
                case 'string':
                    $v = 'v';
                case 'integer':
                    $v = 'i';
                default:
                    $v = 'v';
            }

            $map_str .= $k . ':' . $v . "\r\n";
        }
        file_put_contents($mapFile, trim($map_str));
    }

    /**
     * Flatten the main json object and work through the stack of nested objects
     * Add in the id and __index as necessary
     * 
     * @return array flat - filename => flat object to print to the files
     */
    public function flatten()
    {
        $flat = [];
        foreach ($this->decoded->{$this->topLevelObjectKey} as $object) {
            $id = $object->id;
            $singular = $this->inflector->singularize($this->topLevelObjectKey);
            $flat[$singular][] = $this->flattenObject($object, $singular);
        }

        //Now if there are objects or arrays on the stack
        while ($this->stackCount > 0) { //we don't need to worry about count updates here, they're in the push and pop
            $fileobj = $this->stackPop(); //get next fileobj from stack to process
            $filename = $fileobj[0];
            $k = $fileobj[1];
            $topObject = $fileobj[2];
            $extras = $fileobj[3];
            if (is_array($topObject) || is_object($topObject)) {
                $index = 0;
                foreach ($topObject as $k => $v) {
                    if (is_object($v) || is_array($v)) {
                        // $pid = isset($v->id) ? $v->id : $id; // if parent has an id let's use that
                        // $extras['id'] = $id;
                        $extras['__index'] = "$index";
                        $flat[$filename][] = $this->flattenObject($v, $filename, $extras);
                        $index += 1;
                    } else {
                        foreach ($extras as $kextra => $vextra) {
                            $flat[$filename][$index][$kextra] = $vextra; // add the extra values as the given keys
                        }
                        $flat[$filename][$index][$k] = $v; // add the remaining key values
                    }
                }
            }
        }

        return $flat;
    }

    /**
     * Loop the object
     * If an array is found, it has objects, flatten them all and save
     * If an object is found, it has keys, filter them, push objects or arrays to stack
     * @param type $object
     * @param prefix - name of the higher level object if an array or object is pushed to stack
     * @param $extras - for adding on the id and __index
     * @return type
     */
    private function flattenObject($object, $prefix, $extras = [])
    {
        $flat = [];
        // $flat['id'] = isset($object->id) ? $object->id : '-99999';
        foreach ($extras as $k => $v) {
            $flat[$k] = $v;
        }
        foreach ($object as $k => $v) {
            //If its not an object or array its a flat property to write
            //Else it is an object or array to push to the stack
            if (!(is_object($v) || is_array($v))) {
                $flat[$k] = $v;
            } else if (is_object($v) || is_array($v)) {
                //Get the parentId before pushing it to the stack
                $object = $this->_helper->addObjectId($object); //add id to parent object if it doesn't exist
                $parentId = is_object($object) ? $object->id : null;
                $fileObj = $this->createFileObj($k, $v, $prefix, $parentId, $extras);
                $this->stackPush($fileObj);
            }
        }
        return $flat;
    }

    /**
     * Write the flattened object to file
     * @param type $filename
     * @param type $flat
     */
    public function writeFlatToFile($filename, $flat)
    {
        file_put_contents($filename, json_encode($flat));
    }

    /**
     * HELPER FUNCTIONS BELOW
     */

    /**
     * Check that json is valid before constructing
     * @throws \InvalidArgumentException
     * 
     * @param type $json - the json to validate, if it can't be decoded it's invalid
     */
    public function ensureValidJson($json)
    {
        //Try to decode the json, if its not decoded then it is not valid
        $ob = json_decode($json);
        if ($ob === null) {
            throw new \InvalidArgumentException(
                sprintf(
                    '"%s" is not a valid json',
                    $json
                )
            );
        }
    }

    /**
     * We'll push a value or an object to the stack and increment the stackCount
     * @param type $val
     */
    public function stackPush($val)
    {
        $this->stack[] = $val;
        $this->stackCount += 1;
    }

    /**
     * We'll decrement stack count and return the last value
     * @param type $val
     */
    public function stackPop()
    {
        if (!empty($this->stack) && $this->stackCount > 0) {
            end($this->stack);
            $lastKey = key($this->stack);
            $pop = $this->stack[$lastKey];
            unset($this->stack[$lastKey]);
            $this->stackCount -= 1;
            return $pop;
        } else {
            return null;
        }
    }

    /**
     * Handle singularization of the table names and appending the outer object name as prefix
     * @param type $k - current key (Ex. items)
     * @param type $v - current value (Ex. list of items)
     * @param type $prefix - parent object key singularized (Ex. menu)
     * @param type $parentId - parent id if parent key is given (Ex. 1)
     * @param type $extras - for additional fields like id and __index
     */
    private function createFileObj($k, $v, $prefix = '', $parentId = '', $extras = [])
    {
        $delim = !empty($prefix) ? "_" : '';
        $parentIdKey = !empty($prefix) ? "${prefix}_id" : '';
        if (!empty($parentIdKey)) {
            $extras[$parentIdKey] = $parentId;
        }
        $filename = $prefix . $delim . $this->inflector->singularize($k); //We create the new filename for the object as the outerobjects_object and the object name is in singular
        return [$filename, $k, $v, $extras]; //We'll push this to stack so we have both the actual objectname and the filename
    }
}
