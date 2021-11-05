<?php

namespace Lexer;

use Library\Helper;

class Lexer
{
    private string $sourceCode;
    private int $filePointer;

    public function __construct($fileAddress)
    {
        if (!is_file($fileAddress)){
            Helper::printString("the specified File Address is Wrong");
            die(1);
        }
        $this->sourceCode = file_get_contents($fileAddress);
        $this->filePointer = 0;
    }

    private function getChar(){
        return substr($this->sourceCode,($this->filePointer++),1);
    }

    private function unGetChar(){
        $this->filePointer--;
    }

    public function nextToken(){

    }

    
}