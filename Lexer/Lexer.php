<?php

namespace Lexer;

use Library\Helper;

class Lexer
{
    private string $sourceCode;
    private int $filePointer;

    public function __construct($fileAddress)
    {
        if (!is_file($fileAddress)) {
            Helper::printString("the specified File Address is Wrong");
            die(1);
        }
        $this->sourceCode = file_get_contents($fileAddress);
        $this->filePointer = 0;
    }

    public function nextToken()
    {
        $c = $this->getChar();
        while ($this->isWhiteSpace($c) || $this->isComment($c))
            $c = $this->getChar();

    }

    private function getChar()
    {
        return substr($this->sourceCode, ($this->filePointer++), 1);// get one char from source code string and add pointer
    }

    private function unGetChar()
    {
        $this->filePointer--;// go one char backward
    }

    /**
     * check if the character is space, new line or another type of whitespace
     * @param $char
     * @return bool
     */
    private function isWhiteSpace($char): bool
    {
        return in_array($char, [" ", "\t", "\n", "\r", "\r\n", PHP_EOL]);
    }

    /**
     * check if it is a comment or not,
     * if it is a comment skip it
     * @param $char
     * @return bool
     */
    private function isComment($char): bool
    {
        if ($char == "-") {
            $char = $this->getChar();
            if ($char == "-") {
                $char = $this->getChar();
                while ($char != PHP_EOL)
                    $char = $this->getChar();
                $this->getChar();
                return true;
            }
            $this->unGetChar();
        }
        return false;
    }
}