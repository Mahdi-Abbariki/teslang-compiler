<?php

namespace Classes;

class SymbolTable
{
    const NULL_TYPE = 1;    //NIL 
    const ARRAY_TYPE = 2;   //Array
    const INT_TYPE = 3;     //INT


    private string $id;
    private int $scope;
    private int $type;
    private bool $isFunc;
    private int $paramCount = 0;
    private array $table = []; // for child nodes that is a function (isFunc =true) it contains params info
    public string $addr;


    /**
     * @param bool $setBaseFunction
     */
    public function __construct($setBaseFunction = false)
    {
        if ($setBaseFunction)
            $this->setBaseFunctions();
    }

    public static function setFunction($id, $type, $paramCount)
    {
        $new = new SymbolTable(false);
        $new->setId($id);
        $new->setType($type);
        $new->setIsFunc(true);
        $new->setParamCount($paramCount);
        $new->setScope(0);
        return $new;
    }

    public static function setVariable($id, $type)
    {
        $new = new SymbolTable(false);
        $new->setId($id);
        $new->setType($type);
        $new->setIsFunc(false);
        return $new;
    }


    //Helpers
    public static function contains($table, $id, $scope): bool | SymbolTable
    {
        $res = false;
        foreach ($table as $symbol) {
            if ($symbol->isFunction())
                $res = SymbolTable::contains($symbol->getTable(), $id, $scope);
            if ($id == $symbol->getId() && $scope >= $symbol->getScope())
                return $symbol;
        }

        return $res;
    }

    public static function getTypeName($type)
    {
        switch ($type) {
            case 1: {
                    return 'Nil';
                }
            case 2: {
                    return 'Array';
                }
            case 3: {
                    return 'Int';
                }
        }
    }

    public function resetScope($scope)
    {
        $table = $this->getTable();
        foreach ($table as $key => $symbol) {
            if ($symbol->getScope() >= $scope)
                unset($table[$key]);
        }
        $this->table = array_values($table);
    }

    public static function getTypeBasedOnType($name)
    {
        switch ($name) {
            case "Nil": {
                    return self::NULL_TYPE;
                }
            case 'Array': {
                    return self::ARRAY_TYPE;
                }
            case 'Int': {
                    return self::INT_TYPE;
                }
        }
    }

    private function setBaseFunctions()
    {
        $getIntFunctionNode = SymbolTable::setFunction('getInt', SymbolTable::INT_TYPE, 0);
        $this->addNode($getIntFunctionNode, 0);


        $printIntFunctionNode = SymbolTable::setFunction('printInt', SymbolTable::NULL_TYPE, 1);
        $printIntFunctionNode->addNode(SymbolTable::setVariable('n', SymbolTable::INT_TYPE), -1);
        $this->addNode($printIntFunctionNode, 0);

        $createArrayFunctionNode = SymbolTable::setFunction('createArray', SymbolTable::ARRAY_TYPE, 1);
        $createArrayFunctionNode->addNode(SymbolTable::setVariable('n', SymbolTable::INT_TYPE), -1);
        $this->addNode($createArrayFunctionNode, 0);

        $arrayLengthFunctionNode = SymbolTable::setFunction('arrayLength', SymbolTable::INT_TYPE, 1);
        $arrayLengthFunctionNode->addNode(SymbolTable::setVariable('v', SymbolTable::ARRAY_TYPE), -1);
        $this->addNode($arrayLengthFunctionNode, 0);

        $exitFunctionNode = SymbolTable::setFunction('exit', '', SymbolTable::NULL_TYPE, 1);
        $exitFunctionNode->addNode(SymbolTable::setVariable('n', SymbolTable::INT_TYPE), -1);
        $this->addNode($exitFunctionNode, 0);
    }


    //Setters
    public function setId($id)
    {
        $this->id = $id;
    }

    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    public function setAddr($addr)
    {
        $this->addr = $addr;
    }

    public function setType($type)
    {
        if (in_array($type, [self::ARRAY_TYPE, self::INT_TYPE, self::NULL_TYPE]))
            $this->type = $type;
    }

    public function setIsFunc($val)
    {
        if (is_bool($val))
            $this->isFunc = $val;
    }

    public function setParamCount($count)
    {
        if (is_numeric($count))
            $this->paramCount = $count;
    }

    /**
     * @param SymbolTable $symbol
     */
    public function addNode($symbol, $scope, $addr = "")
    {
        if ($symbol instanceof SymbolTable) {
            $symbol->setScope($scope);
            $symbol->setAddr($addr);
            array_push($this->table, $symbol);
        }
    }

    //Getters
    public function getId()
    {
        return $this->id;
    }

    public function isFunction()
    {
        return (bool) $this->isFunc;
    }

    public function hasType(): bool
    {
        return isset($this->type);
    }

    public function getType()
    {
        return $this->type;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getParamCount()
    {
        return $this->paramCount;
    }

    public function getScope()
    {
        return $this->scope;
    }
}
