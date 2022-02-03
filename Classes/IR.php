<?php

namespace Classes;

class IR
{
    private $outputFile;
    private $filePointer;
    private $registerCount;
    private $labelCount;
    private $canWrite;

    private const DATA_BYTE = 32;

    public function __construct($clearFile = false, $file = __ROOT__ . "/dist/IR/output")
    {
        $this->outputFile = $file;
        $this->registerCount = 0;
        $this->labelCount = 0;
        $this->canWrite = true;

        if ($clearFile)
            @unlink($this->outputFile);
        $this->openFile();

        $this->writeBuiltInFunctions();
    }

    public function __destruct()
    {
        fclose($this->filePointer);
    }

    private function openFile()
    {
        $this->filePointer = fopen($this->outputFile, 'a+');
    }

    public function write($data, $withTab = true)
    {
        if ($this->canWrite) {
            if ($withTab)
                $data = "   " . $data;
            fwrite($this->filePointer, $data);
        }
    }

    public function writeLabel($label)
    {
        if ($this->canWrite)
            fwrite($this->filePointer, $label . ":\n");
    }

    public function temp()
    {
        return "r" . $this->registerCount++;
    }

    public function label()
    {
        return "lb" . $this->labelCount++;
    }

    public function doNotEqual($res, $first, $second)
    {
        $this->write("mov $res, 0\n");
        $temp = $this->temp();
        $label = $this->label();
        $this->write("cmp= $temp, $first, $second\n");
        $this->write("jz $temp, $label\n");
        $this->writeLabel($label);
        $this->write("mov $res, 1");
    }

    public function resetRegisters()
    {
        $this->registerCount = 0;
    }

    public function stopWriting()
    {
        $this->write("----- ERROR");
        $this->canWrite = false;
    }

    private function writeBuiltInFunctions()
    {
        $this->getInt();
        $this->printInt();
        $this->createArray();
        $this->arrayLength();
    }

    private function getInt()
    {
        $this->write("proc getInt\n", false);
        $this->write("call iget, r0\n");
        $this->write("ret\n\n");
    }

    private function printInt()
    {
        $this->write("proc printInt\n", false);
        $this->write("call iput, r0\n");
        $this->write("ret\n\n");
    }

    private function createArray()
    {
        $this->write("proc createArray\n", false);
        //it has only one input in r0

        //add array len by 1
        $this->write("mov r1, 1\n");
        $this->write("add r1, r0, r1\n");

        //bytes
        $bytes = self::DATA_BYTE;
        $this->write("mov r2, $bytes\n");
        $this->write("mul r1, r1, r2\n");

        //get the memory
        $this->write("call mem, r2\n");

        //write array len to index 0 of array (r2)
        $this->write("st r1, r2\n");

        //return res
        $this->write("mov r0, r2\n");
        $this->write("ret\n\n");
    }

    private function arrayLength()
    {
        $this->write("proc arrayLength\n", false);
        $this->write("ld r1, r0\n"); //get array len
        $this->write("mov r0, r1\n"); //return it
        $this->write("ret\n\n");
    }
}
