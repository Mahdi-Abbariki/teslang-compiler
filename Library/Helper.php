<?php

namespace Library;

class Helper
{
    public static function printString(...$toPrint)
    {
        foreach ($toPrint as $p)
            echo "$p\n";
    }
}