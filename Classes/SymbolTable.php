<?php

class SymbolTable 
{
    const NULL_TYPE = 0;    //NIL 
    const ARRAY_TYPE = 1;   //INT
    const INT_TYPE = 2;     //Array


    private static $instance = null;
    
    private $id;
    private $type;
    private $value;
    private $isFunc;
    private $paramCount;
    private $table;


    public function __construct(){
        $this->table = [];
    }

    public static function setData($id,$value,$type,$isFunc,$paramCount){
        $new = new SymbolTable();
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
