<?php

require_once 'src/Flattner.php';
require_once 'src/Inflector.php';

$inputFiles = [
    // 'data/restaurants.json',
    // 'data/accounts.json',
    // 'data/donuts.json',
    // 'data/startups.json',
    'data/fullmenu.json'
];

foreach ($inputFiles as $inputFile) {
    $flattner = new AcmeTutor\Flattner($inputFile);
    $flattner->outputFiles();
}

echo "Done processing files.  See output folder for the resulting files.";