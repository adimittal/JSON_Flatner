<?php

/**
 * Description of FlatnerTest
 * 
 * Unit tests for the Flatner Class
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
 * Normally I use autoloader for larger projects 
 * to manage dependencies but here I will just require
 **/
require_once __DIR__ . '/../src/Flatner.php';
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/Inflector.php';

use PHPUnit\Framework\TestCase;
use Helpers;

/**
 * Test cases for Flatner
 * 
 * @category PHP
 * @package  JSON_Flatner
 * @author   Aditya Mittal <scientificchess@gmail.com>
 * @license  Aditya Mittal
 * @link     https://github.com/adimittal/JSON_Flatner
 */
class FlatnerTest extends TestCase
{
    private $_helper;

    protected function setUp(): void
    {
        //Initialize the test case
        //Called for every defined test
        $this->_helper = new Helper();
    }

    // Clean up the test case, called for every defined test
    protected function tearDown():void { }

    /**
     * Add UUID to object if it doesn't have an id
     * 
     * @return null
     */
    public function testAddObjectId()
    {
        //Object doesn't already have an id
        $id = "6a6a0857-3d09-4d7d-9e61-447b6d4f9b3e";
        $obj = json_decode(file_get_contents(("data/accounts.json")));
        $objD = json_decode(file_get_contents(("desiredOutput/accountsWithId.json")));
        $out = $this->_helper->addObjectId($obj, $id);
        $this->assertEquals($out, $objD);

        //Object already has an id
        $id = "6a6a0857-3d09-4d7d-9e61-447b6d4f9b3e";
        $objD = json_decode(file_get_contents(("desiredOutput/accountsWithId.json")));
        $out = $this->_helper->addObjectId($objD, $id);
        $this->assertEquals($out, $objD);
    }

    /**
     * Test flattening of accounts json
     */
    public function testFlattenAccounts()
    {
        //Start by testing the flattening of accounts since it is the flattest json
        $flatner = new Flatner("data/accounts.json");
        $result = $flatner->flatten();
        $flatner->outputFiles();
        $desiredJson = $this->_helper->readJsonFileWithoutEndlines("desiredOutput/account.json");
        $actualJson = json_encode($result['account']);

        $this->assertEquals($desiredJson, $actualJson);
    }

    /**
     * Test flattening of restaurants json
     */
    public function testFlattenRestaurants()
    {
        //Next test flattened restaurant json with multiple objects
        $flatner = new Flatner("data/restaurants.json");
        $result = $flatner->flatten();
        $flatner->outputFiles();

        $desiredJson = $this->_helper->readJsonFileWithoutEndlines("desiredOutput/restaurant.json");
        $actualJson = json_encode($result['restaurant']);
        $this->assertEquals($desiredJson, $actualJson);

        $desiredJson = $this->_helper->readJsonFileWithoutEndlines("desiredOutput/restaurant_grade.json");
        $actualJson = json_encode($result['restaurant_grade']);
        $this->assertEquals($desiredJson, json_encode(json_decode($actualJson)));

        $desiredJson = $this->_helper->readJsonFileWithoutEndlines("desiredOutput/restaurant_address.json");
        $actualJson = json_encode($result['restaurant_address']);
        $this->assertEquals($desiredJson, $actualJson);

        $desiredJson = $this->_helper->readJsonFileWithoutEndlines("desiredOutput/restaurant_grade_score.json");
        $actualJson = json_encode($result['restaurant_grade_score']);
        $this->assertEquals($desiredJson, $actualJson);
    }

    /**
     * Test the json validator is working properly
     * 
     * @return null
     **/
    public function testEnsureValidJson()
    {
        //Valid json
        $flatner = new Flatner("data/restaurants.json");
        $json = file_get_contents("data/restaurants.json");
        $result = $flatner->ensureValidJson($json);
        $this->assertEquals(null, $result); //expecting null, actual result for valid json

        //Invalid json - expecting an exception for invalid json
        $this->expectException(\InvalidArgumentException::class);
        $json = file_get_contents("data/invalidJson.json");
        $result = $flatner->ensureValidJson($json);
    }

    //Testing the push and pop stack by pushing and popping bunch of values
    //I'm not testing stackCount for now so as not to introduce extra getters and setters in the class
    public function testPushPopStack()
    {
        /**
         * 1 value
         */
        $pushed = 'val1';
        $flatner = new Flatner("data/restaurants.json");
        //Let's push something to stack
        $flatner->stackPush($pushed);
        //Let's pop from stack
        $popped = $flatner->stackPop();
        //Let's validate we got the correct value back
        $this->assertEquals($pushed, $popped);

        /**
         * multiple values in mixed push pop order
         */
        $flatner = new Flatner("data/restaurants.json");
        //Let's push something to stack
        $flatner->stackPush('val1');
        $flatner->stackPush('val2');
        $flatner->stackPush('val3');
        //Let's pop from stack
        $popped = $flatner->stackPop();
        //Let's validate we got the correct value back
        $this->assertEquals('val3', $popped);
        //Let's pop from stack
        $popped = $flatner->stackPop();
        //Let's validate we got the correct value back
        $this->assertEquals('val2', $popped);
        //Let's push something more to stack
        $flatner->stackPush('val4');
        $flatner->stackPush('val5');

        //Let's pop from stack
        $popped = $flatner->stackPop();
        //Let's validate we got the correct value back
        $this->assertEquals('val5', $popped);
        //Let's pop from stack
        $popped = $flatner->stackPop();
        //Let's validate we got the correct value back
        $this->assertEquals('val4', $popped);
        //Let's pop from stack
        $popped = $flatner->stackPop();
        //Let's validate we got the correct value back
        $this->assertEquals('val1', $popped);
        //Let's pop from stack once more to see if we get null
        $popped = $flatner->stackPop();
        //Let's validate we got the correct value back
        $this->assertEquals(null, $popped);
    }
}
