<?php
include "autoloader.php";


use Classes\Lexer;
use Classes\Parser;

//const TEST_FILE_ADDRESS = "dist/tslang.txt"; // you can change this line for different input file
//const TEST_FILE_ADDRESS = "dist/test2.txt"; // you can change this line for different input file
const TEST_FILE_ADDRESS = "dist/sampleFunctionForParser"; // you can change this line for different input file


$parser = new Parser(TEST_FILE_ADDRESS);

$parser->startParsing();
