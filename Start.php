<?php

require_once 'src/Flattner.php';
require_once 'src/Inflector.php';

$inputFiles = [
    'data/restaurants.json',
    'data/accounts.json',
    'data/donuts.json',
    'data/startups.json'
];

foreach ($inputFiles as $inputFile) {
    $flattner = new DataCoral\Flattner($inputFile);
    $flattner->outputFiles();
}

echo "Done processing files.  See output folder for the resulting files.";

foreach(glob('output/*.json') as $jsonFile){
    echo "<br /><a href=\"$jsonFile\">$jsonFile</a><br />";
}