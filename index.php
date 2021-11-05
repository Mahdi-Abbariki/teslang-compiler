<?php
include "autoloader.php";


use Lexer\Lexer;


const TEST_FILE_ADDRESS = "dist/tslang.txt";

$lex = new Lexer(TEST_FILE_ADDRESS);


while (!$lex->isEOF())
    echo $lex->nextToken();
