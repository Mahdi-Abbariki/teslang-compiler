<?php
include "autoloader.php";


use Classes\Lexer;


//const TEST_FILE_ADDRESS = "dist/tslang.txt"; // you can change this line for different input file
const TEST_FILE_ADDRESS = "dist/test2.txt"; // you can change this line for different input file


$lex = new Lexer(TEST_FILE_ADDRESS);


while (!$lex->isEOF())
    echo $lex->nextToken(), "\n";
