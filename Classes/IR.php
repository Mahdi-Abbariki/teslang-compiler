<?php

namespace Classes;

class IR
{
    private $outputFile;
    private $filePointer;
    private $registerCount;
    private $labelCount;
    private $canWrite;

    public function __construct($clearFile = false, $file = __ROOT__ . "/dist/IR/output")
    {
        $this->outputFile = $file;
        $this->registerCount = 0;
        $this->labelCount = 0;
        $this->canWrite = true;

        if ($clearFile)
            @unlink($this->outputFile);
        $this->openFile();
    }

    public function __destruct()
    {
        fclose($this->filePointer);
    }

    private function openFile()
    {
        $this->filePointer = fopen($this->outputFile, 'a+');
    }

    public function write($data,$withTab = true)
    {
        if ($this->canWrite){
            if($withTab)
                $data = "   ".$data;
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
}
