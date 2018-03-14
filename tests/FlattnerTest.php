<?php

namespace DataCoral;

//normally I use autoloader for larger projects to manage dependencies but here I will just require
require_once 'src/Flattner.php';
require_once 'src/Inflector.php';

use PHPUnit\Framework\TestCase;

/**
 * Description of FlattnerTest
 * 
 * Unit tests for the Flattner Class
 *
 * @author Aditya Mittal
 */
class FlattnerTest extends TestCase {

    /**
     * Test flattening of accounts json
     */
    public function testFlattenAccounts() {
        //Start by testing the flattening of accounts since it is the flattest json
        $flattner = new Flattner("data/accounts.json");
        $result = $flattner->flatten();
        $desiredJson = json_encode(json_decode(file_get_contents("desiredOutput/account.json")));
        $actualJson = json_encode($result['account']);
       
        $this->assertEquals($desiredJson,$actualJson);
    }
    
    /**
     * Test flattening of restaurants json
     */
    public function testFlattenRestaurants() {
        //Next test flattened restaurant json with multiple objects
        $flattner = new Flattner("data/restaurants.json");
        $result = $flattner->flatten();
        
        $desiredJson = json_encode(json_decode(file_get_contents("desiredOutput/restaurant.json")));
        $actualJson = json_encode($result['restaurant']);
        $this->assertEquals($desiredJson,$actualJson);
        
        $desiredJson = json_encode(json_decode(file_get_contents("desiredOutput/restaurant_grade.json")));
        print_r($result);die;
        $actualJson = json_encode($result['restaurant_grade']);
        $this->assertEquals($desiredJson,$actualJson);
        
        $desiredJson = json_encode(json_decode(file_get_contents("desiredOutput/restaurant_address.json")));
        $actualJson = json_encode($result['restaurant_address']);
        $this->assertEquals($desiredJson,$actualJson);
        
        $desiredJson = json_encode(json_decode(file_get_contents("desiredOutput/restaurant_grade_score.json")));
        $actualJson = json_encode($result['restaurant_grade_score']);
        $this->assertEquals($desiredJson,$actualJson);
    }
    
    
    
    //Test the json validator is working properly
    public function testEnsureValidJson() {
        //Valid json
        $flattner = new Flattner("data/restaurants.json");
        $result = $flattner->ensureValidJson();
        $this->assertEquals(null, $result); //expecting null, actual result for valid json
        
        //Invalid json - expecting an exception for invalid json
        $this->expectException(\InvalidArgumentException::class);
        $flattner = new Flattner("data/invalidJson.json");
        $result = $flattner->ensureValidJson();
    }
    
    //Testing the push and pop stack
    //I'm not testing stackCount for now so as not to introduce extra getters and setters in the class
    public function testPushPopStack() {
        $pushed = 'somevalue';
        $flattner = new Flattner("data/restaurants.json");
        //Let's push something to stack
        $flattner->stackPush($pushed);
        //Let's pop from stack
        $popped = $flattner->stackPop();
        //Let's validate we got the correct value back
        $this->assertEquals($pushed, $popped);
    }
    

}