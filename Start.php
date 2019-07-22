<?php
use JSONFlatner\Flatner;

require_once __DIR__ . '/src/Flatner.php';
require_once __DIR__ . '/src/Helper.php';
require_once __DIR__ . '/src/Inflector.php';

$a = 5;
$b = 8;
echo $a + $b;

$inputFiles = [
    'data/restaurants.json',
    'data/accounts.json',
    'data/donuts.json',
    'data/startups.json',
    'data/fullmenu.json'
];

foreach ($inputFiles as $inputFile) {
    $flatner = new Flatner($inputFile);
    $flatner->outputFiles();
}

echo "Done processing files.  See output folder for the resulting files.";