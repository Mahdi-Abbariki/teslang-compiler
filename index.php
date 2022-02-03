<?php
include "autoloader.php";

define("__ROOT__",__DIR__);
use Classes\Parser;

//const TEST_FILE_ADDRESS = "dist/tslang.txt"; // you can change this line for different input file
//const TEST_FILE_ADDRESS = "dist/test2.txt"; // you can change this line for different input file
// const TEST_FILE_ADDRESS = "dist/sampleFunctionForParser"; // you can change this line for different input file
// const TEST_FILE_ADDRESS = "dist/sampleFunctionForIR"; // you can change this line for different input file
const TEST_FILE_ADDRESS = "dist/sampleFunctionForIRSec"; // you can change this line for different input file


$parser = new Parser(TEST_FILE_ADDRESS);

$parser->startParsing();
