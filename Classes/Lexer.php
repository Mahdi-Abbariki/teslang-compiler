<?php

namespace Classes;

class Lexer
{
    private string $sourceCode;
    private int $filePointer;
    private int $fileLength;
    private int $lineCounter = 0;

    public function __construct($fileAddress)
    {
        if (!is_file($fileAddress)) {
            echo "the specified File Address is Wrong", "\n";
            die(1);
        }
        $this->sourceCode = file_get_contents($fileAddress);
        $this->filePointer = 0;
        $this->fileLength = strlen($this->sourceCode);
    }

    public function nextToken()
    {
        $c = $this->getChar();
        while ($this->isWhiteSpace($c) || $this->isComment($c))
            $c = $this->getChar();

        if ($token = $this->isSpecialCharacter($c))
            return $token;

        if ($token = $this->isAlphabeticChar($c))
            return $token;

        if (($token = $this->isNumeric($c)) !== false)
            return (string)$token;
    }

    /**
     * determine if we are at the end of file or not
     * @return bool
     */
    public function isEOF(): bool
    {
        return $this->filePointer >= $this->fileLength;
    }

    public function getCounter(){
        return $this->lineCounter;
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
        if(in_array($char,["\n", "\r", "\r\n", PHP_EOL]))
            $this->lineCounter++;
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
                $this->lineCounter++;
                return true;
            }
            $this->unGetChar();
        }
        return false;
    }

    private function isSpecialCharacter($char)
    {
        if ($this->checkForOneCharSpecialTokens($char)) {
            return $char;
        } else {
            //check for other special chars

            if ($char == "=") {
                $c = $this->getChar();
                if ($c == "=")
                    return "==";
                $this->unGetChar();
                return "=";
            } else if ($char == "<") {
                $c = $this->getChar();
                if ($c == "=")
                    return "<=";
                $this->unGetChar();
                return "<";
            } else if ($char == ">") {
                $c = $this->getChar();
                if ($c == "=")
                    return ">=";
                $this->unGetChar();
                return ">";
            } else if ($char == "!") {
                $c = $this->getChar();
                if ($c == "=")
                    return "!=";
                $this->unGetChar();
                return "!";
            } else if ($char == "|") {
                $c = $this->getChar();
                if ($c == "|")
                    return "!=";
                $this->unGetChar();//the token is || and we don't have any token as |
                $this->unGetChar();
                return false;
            } else if ($char == "&") {
                $c = $this->getChar();
                if ($c == "&")
                    return "&&";
                $this->unGetChar();//the token is && and we don't have any token as &
                $this->unGetChar();
                return false;
            }
        }
        return false;
    }

    private function checkForOneCharSpecialTokens($char)
    {
        return in_array($char, [
            "(",
            ")",
            "[",
            "]",
            ";",
            ":",
            "?",
            "+",
            "-",
            "*",
            "/",
            "%",
            ",",
        ]);
    }

    private function isAlphabeticChar($char)
    {
        if (!preg_match("/[a-zA-Z_]/", $char))
            return false;
        $token = $char;//first char is accepted, so the regex should be changed
        $char = $this->getChar();
        while (preg_match("/[a-zA-Z_0-9]/", $char)) {// get maximum number of chars (Maximal Munch)
            $token .= $char;
            $char = $this->getChar();
        }
        $this->unGetChar();
        if ($this->isReservedToken($token))//reserved keys are more important than other strings
            return $token;

        if ($this->isValType($token))
            return $token;

        return $token;
    }

    private function isValType($token)
    {
        return in_array($token, ["Nil", "Int", "Array"]);
    }

    private function isReservedToken($token)
    {
        if (strcmp($token, "function") === 0)
            return true;
        return in_array($token, [
            "returns",
            "return",
            "if",
            "else",
            "while",
            "do",
            "foreach",
            "of",
            "end",
            "val",
        ]);
    }

    private function isNumeric($char)
    {
        $token = "";
        while (preg_match("/[0-9]/", $char)) {// get maximum number of chars (Maximal Munch)
            $token .= $char;
            $char = $this->getChar();
        }
        $this->unGetChar();
        if ($token != "")
            return $token;
        return false;
    }
}