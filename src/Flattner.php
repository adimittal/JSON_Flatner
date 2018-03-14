<?php

namespace DataCoral;

/**
 * Description of Flattner - flatten a JSON object and reconstruct it
 *
 * @author Aditya Mittal
 */
class Flattner {

    private $json;
    private $decoded;
    private $stack = [];
    private $stackCount = 0;
    private $topLevelObject = '';
    private $id;
    private $outputDir = 'output';
    private $inflector;

    /**
     * CONSTRUCTOR:
     * Construct a flattner for the json to flatten and ensure its valid json
     * Input: jsonFile to flatten
     */
    public function __construct($jsonFile) {
        //Get the jsonFile contents, throw any errors like not finding the file
        try {
            $json = file_get_contents($jsonFile);
        } catch (Exception $e) {
            echo 'Caught exception getting file contents: ', $e->getMessage(), "\n";
        }

        //ensure the file contents are valid json
        try {
            $this->ensureValidJson($json);
        } catch (\InvalidArgumentException $e) {
            echo 'Caught exception validating json: ', $e->getMessage(), "\n";
        }

        $this->init($json);
    }

    /**
     * Setup the Flattner
     * @param type $json
     */
    private function init($json) {
        //set the valid json into this flattner object
        $this->json = $json;

        //set the decoded json into this
        $this->decoded = json_decode($json); //defaults to depth 512 - depth is an optional 3rd parameter
        //set the top level
        $this->setTopLevel();

        //Setup an inflector
        $this->inflector = new Inflector();
    }

    /**
     * Init the top level json object
     */
    public function setTopLevel() {
        if (is_object($this->decoded) || is_array($this->decoded)) {
            foreach ($this->decoded as $k => $v) {
                $this->topLevelObject = $k;
            }
        }
    }

    public function getTopLevel() {
        return $this->topLevelObject;
    }

    /**
     * If you wish to use a dir other than default
     * @param type $outputDir
     */
    public function setOutputDir($outputDir) {
        $this->outputDir = $outputDir;
    }

    /**
     * Get the flat array and output to files
     */
    public function outputFiles() {
        $flat = $this->flatten();
        foreach ($flat as $k => $v) {
            file_put_contents($this->outputDir . "/$k.json", json_encode($v));
        }
    }

    /*
     * Flatten the main json object and work through the stack of nested objects
     * Add in the id and __index as necessary
     * @return - an array with filename as the index and the object to print to the files
     */

    public function flatten() {
        $flat = [];
        foreach ($this->decoded->{$this->topLevelObject} as $object) {
            $id = $object->id;
            $singular = $this->inflector->singularize($this->topLevelObject);
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
                        $extras = ['id' => $id, '__index' => $index];
                        $flat[$filename][] = $this->flattenObject($v, $filename, $extras);
                        $index += 1;
                    } else {
                        if (isset($extras['id']) && isset($extras['__index'])) {
                            $id = $extras['id'];
                            $index = $extras['__index'];
                        }
                        $flat[$filename][$index]['id'] = $id; //add the id as the top level id
                        $flat[$filename][$index]['__index'] = $index;
                        $flat[$filename][$index][$k] = $v;
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
    private function flattenObject($object, $prefix, $extras = []) {
        $flat = [];
        foreach ($extras as $k => $v) {
            $flat[$k] = $v;
        }
        foreach ($object as $k => $v) {
            //If its not an object or array its a flat property to write
            //Else it is an object or array to push to the stack
            if (!(is_object($v) || is_array($v))) {
                $flat[$k] = $v;
            } else if (is_object($v) || is_array($v)) {
                $fileObj = $this->createFileObj($k, $v, $prefix, $extras);
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
    public function writeFlatToFile($filename, $flat) {
        file_put_contents($filename, json_encode($flat));
    }

    /**
     * HELPER FUNCTIONS BELOW
     */

    /**
     * Check that json is valid before constructing
     * @throws \InvalidArgumentException
     */
    public function ensureValidJson() {
        //Try to decode the json, if its not decoded then it is not valid
        $ob = json_decode($this->json);
        if ($ob === null) {
            throw new \InvalidArgumentException(
            sprintf(
                    '"%s" is not a valid json', $this->json
            )
            );
        }
    }

    /**
     * We'll push a value or an object to the stack and increment the stackCount
     * @param type $val
     */
    public function stackPush($val) {
        $this->stack[] = $val;
        $this->stackCount += 1;
    }

    /**
     * We'll decrement stack count and return the last value
     * @param type $val
     */
    public function stackPop() {
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
     * @param type $k
     * @param type $prefix
     * @return type
     */
    private function createFileObj($k, $v, $prefix = '', $extras = []) {
        $delim = !empty($prefix) ? "_" : '';
        $filename = $prefix . $delim . $this->inflector->singularize($k); //We create the new filename for the object as the outerobjects_object and the object name is in singular
        $object = $v;
        return $fileobj = [$filename, $k, $object, $extras]; //We'll push this to stack so we have both the actual objectname and the filename
    }

    /**
     * Just to help during debugging test printouts
     * @param type $obj
     */
    private function quickPrint($obj) {
        fwrite(STDOUT, print_r($obj, true));
    }

}
