<?php

namespace Classes;

class SymbolTable 
{
    const NULL_TYPE = 0;    //NIL 
    const ARRAY_TYPE = 1;   //INT
    const INT_TYPE = 2;     //Array


    private string $id;
    private int $type;
    private bool $isFunc;
    private int $paramCount=0;
    private array $table = [];// for child nodes that is a function (isFunc =true) it contais params info


    /**
     * @param bool $setBaseFunction
     */
    public function __construct($setBaseFunction = true){
        if($setBaseFunction)
            $this->setBaseFunctions();

    }

    public static function setFunction($id,$type,$paramCount){
        $new = new SymbolTable(false);
        $new->setId($id);
        $new->setType($type);
        $new->setIsFunc(true);
        $new->setParamCount($paramCount);
        return $new;
    }

    public static function setVariable($id,$type){
        $new = new SymbolTable(false);
        $new->setId($id);
        $new->setType($type);
        $new->setIsFunc(false);
        return $new;
    }

   
    //Helpers
    public function contains($id){
        $res = false;
        foreach($this->table as $symbol)
            if($id == $symbol->getId())
                $res = true;
        return $res;
    }

    private function setBaseFunctions(){
        $getIntFunctionNode = SymbolTable::setFunction('getInt',SymbolTable::INT_TYPE,true,0);
        $this->addNode($getIntFunctionNode);


        $printIntFunctionNode = SymbolTable::setFunction('printInt',SymbolTable::NULL_TYPE,true,1);
        $printIntFunctionNode->addNode(SymbolTable::setVariable('n',SymbolTable::INT_TYPE));
        $this->addNode($printIntFunctionNode);

        $createArrayFunctionNode = SymbolTable::setFunction('createArray',SymbolTable::ARRAY_TYPE,true,1);
        $createArrayFunctionNode->addNode(SymbolTable::setVariable('n',SymbolTable::INT_TYPE));
        $this->addNode($createArrayFunctionNode);

        $arrayLengthFunctionNode = SymbolTable::setFunction('arrayLength',SymbolTable::INT_TYPE,true,1);
        $arrayLengthFunctionNode->addNode(SymbolTable::setVariable('v',SymbolTable::ARRAY_TYPE));
        $this->addNode($arrayLengthFunctionNode);

        $exitFunctionNode = SymbolTable::setFunction('exit','',SymbolTable::NULL_TYPE,true,1);
        $exitFunctionNode->addNode(SymbolTable::setVariable('n',SymbolTable::INT_TYPE));
        $this->addNode($exitFunctionNode);
    }


    //Setters
    public function setId($id){
        $this->id = $id;
    }

    public function setType($type){
        if(in_array($type,[self::ARRAY_TYPE,self::INT_TYPE,self::NULL_TYPE]))
            $this->type = $type;
    }

    public function setIsFunc($val){
        if(is_bool($val))
            $this->isFunc = $val;
    }

    public function setParamCount($count){
        if(is_numeric($count))
            $this->paramCount = $count;
    }

    /**
     * @param SymbolTable $symbol
     */
    public function addNode($symbol)
    {
        if($symbol instanceof $symbol)
            array_push($this->table,$symbol);
    }

    //Getters
    public function getId(){
        return $this->id;
    }

    public function isFunction(){
        return (bool) $this->isFunc;
    }

    public function getTokenValue(){
        return $this->value;
    }
}
