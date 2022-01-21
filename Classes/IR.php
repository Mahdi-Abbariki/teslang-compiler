<?php

namespace Classes;

class IR
{
    private $outputFile;
    private $filePointer;
    private $registerCount;
    private $labelCount;

    public function __construct($clearFile = false, $file = __ROOT__."/dist/IR/output")
    {
        $this->outputFile = $file;
        $this->registerCount = 0;
        $this->labelCount = 0;

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
}
