<?php

class SymbolTable 
{
    const NULL_TYPE = 0;    //NIL 
    const ARRAY_TYPE = 1;   //INT
    const INT_TYPE = 2;     //Array


    private $id;
    private $type;
    private $value;
    private $isFunc;
    private $paramCount;
    private $table = [];// for child nodes that is a function (isFunc =true) it contais params info


    /**
     * @param bool $setBaseFunction
     */
    public function __construct($setBaseFunction = true){
        if($setBaseFunction)
            $this->setBaseFunctions();

    }

    public static function setData($id,$value,$type,$isFunc,$paramCount){
        $new = new SymbolTable(false);
        $new->setId($id);
        $new->setValue($value);
        $new->setType($type);
        $new->setIsFunc($isFunc);
        $new->setParamCount($$paramCount);
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

        $getIntFunctionNode = SymbolTable::setData('getInt','',SymbolTable::INT_TYPE,true,0);
        $this->addNode($getIntFunctionNode);


        $printIntFunctionNode = SymbolTable::setData('printInt','',SymbolTable::NULL_TYPE,true,1);
        $printIntFunctionNode->addNode(SymbolTable::setData('n','',SymbolTable::INT_TYPE,false,0));
        $this->addNode($printIntFunctionNode);

        $createArrayFunctionNode = SymbolTable::setData('createArray','',SymbolTable::ARRAY_TYPE,true,1);
        $createArrayFunctionNode->addNode(SymbolTable::setData('n','',SymbolTable::INT_TYPE,false,0));
        $this->addNode($createArrayFunctionNode);

        $arrayLengthFunctionNode = SymbolTable::setData('arrayLength','',SymbolTable::INT_TYPE,true,1);
        $arrayLengthFunctionNode->addNode(SymbolTable::setData('v','',SymbolTable::ARRAY_TYPE,false,0));
        $this->addNode($arrayLengthFunctionNode);

        $exitFunctionNode = SymbolTable::setData('exit','',SymbolTable::NULL_TYPE,true,1);
        $exitFunctionNode->addNode(SymbolTable::setData('n','',SymbolTable::INT_TYPE,false,0));
        $this->addNode($arrayLengthFunctionNode);
    }


    //Setters
    public function setId($id){
        $this->id = $id;
    }

    public function setValue($val){
        $this->value = $val;
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
