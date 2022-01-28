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

    public function write($data)
    {
        if ($this->canWrite)
            fwrite($this->filePointer, $data);
    }

    public function temp()
    {
        return "r" . $this->registerCount++;
    }

    public function label()
    {
        return "l" . $this->labelCount++;
    }

    public function doOr($res, $first, $second)
    {
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
