<?php

namespace Classes;

use Library\Helper;
use Classes\Lexer;
use Classes\SymbolTable;
use Classes\IR;

class Parser extends Lexer
{
	private const FUNCTION_KEYWORD = "function";
	private const OPEN_PARENTHESES = "(";
	private const CLOSE_PARENTHESES = ")";
	private const RETURNS_KEYWORD = "returns";
	private const COLON = ":";
	private const END_KEYWORD = "end";
	private const SEMICOLON_KEYWORD = ";";
	private const IF_KEYWORD = "if";
	private const ELSE_KEYWORD = "else";
	private const WHILE_KEYWORD = "while";
	private const DO_KEYWORD = "do";
	private const FOREACH_KEYWORD = "foreach";
	private const OF_KEYWORD = "of";
	private const RETURN_KEYWORD = "return";
	private const OPEN_BRACKET = "[";
	private const CLOSE_BRACKET = "]";
	private const QUESTION_MARK = "?";
	private const ASSIGNMENT_KEYWORD = "=";
	private const ADDITION_KEYWORD = "+";
	private const SUBTRACT_KEYWORD = "-";
	private const MULTIPLICATION_KEYWORD = "*";
	private const DIVIDE_KEYWORD = "/";
	private const MODULE_KEYWORD = "%";
	private const SMALLER_KEYWORD = "<";
	private const GREATER_KEYWORD = ">";
	private const EQUAL_KEYWORD = "==";
	private const NOT_EQUAL_KEYWORD = "!=";
	private const SMALLER_EQUAL_KEYWORD = "<=";
	private const GREATER_EQUAL_KEYWORD = ">=";
	private const OR_KEYWORD = "||";
	private const AND_KEYWORD = "&&";
	private const NOT_KEYWORD = "!";
	private const NEGATE_KEYWORD = "-";
	private const POSITIVATE_KEYWORD = "+";
	private const INT_KEYWORD = "Int";
	private const ARRAY_KEYWORD = "Array";
	private const NIL_KEYWORD = "Nil";
	private const VAL_KEYWORD = "val";
	private const COMMA_KEYWORD = ",";

	private const NUll_TYPE = "null";



	private SymbolTable $symbolTable;
	private $scopeId = 1;
	private IR $ir;

	private $setArrayVar;

	public function __construct($fileAddress)
	{
		parent::__construct($fileAddress);
		$this->resetArrayVar();
		$this->symbolTable = new SymbolTable(true);
		$this->ir = new IR(true);
	}

	private function resetArrayVar()
	{
		$this->setArrayVar = (object)["active" => false];
	}

	public function startParsing()
	{
		return $this->prog();
	}

	private function prog()
	{
		if ($this->func())
			$this->prog();
	}

	private function func()
	{
		$token = $this->getToken();
		if ($token == self::FUNCTION_KEYWORD) {
			$this->getNextToken();

			$funcName = $this->getToken(); //functions can not be repeated even with same type
			$this->iden($funcName, true, '') ? $this->getNextToken() : null;

			$token = $this->getToken();
			if ($token == self::OPEN_PARENTHESES) {
				$this->getNextToken();

				$funcParams = $this->flist(); // array of SymbolTable Nodes containing params data

				$token = $this->getToken();
				if ($token == self::CLOSE_PARENTHESES) {
					$this->getNextToken();


					$token = $this->getToken();
					if ($token == self::RETURNS_KEYWORD) {
						$this->getNextToken();

						$funcType = $this->type();

						$token = $this->getToken();
						if ($token == self::COLON) {
							//produce IR for function
							$this->ir->write("proc	" . $funcName . "\n", false);



							$this->scopeId++;
							//function scope is started so add it to Symbol Table
							$functionNode = SymbolTable::setFunction($funcName, $funcType, count($funcParams));
							foreach ($funcParams as $symbol)
								$functionNode->addNode($symbol, $this->scopeId, $this->ir->temp());
							$this->symbolTable->addNode($functionNode, $this->scopeId - 1); //look at line 119

							$this->getNextToken();

							$this->body($functionNode);

							$token = $this->getToken();
							if ($token == self::END_KEYWORD) {
								$this->ir->write("\n");
								$this->ir->resetRegisters();

								$this->symbolTable->resetScope($this->scopeId--);
								$this->getNextToken();



								return true;
							} else
								$this->syntaxError("expected `" . self::END_KEYWORD . "`; $token given");
						} else
							$this->syntaxError("expected `" . self::COLON . "`; $token given");
					} else
						$this->syntaxError("expected `" . self::RETURNS_KEYWORD . "`; $token given");
				} else
					$this->syntaxError("expected `" . self::CLOSE_PARENTHESES . "`; $token given");
			} else
				$this->syntaxError("expected `" . self::OPEN_PARENTHESES . "`; $token given");
		} else
			if (!$this->isEOF())
			$this->syntaxError("function keyword can not be found");
	}


	/**
	 * can end the execution on error
	 */
	private function body($funcNode)
	{
		if ($this->stmt($funcNode))
			$this->body($funcNode);
	}

	private function stmt($funcNode, $fromIf = false)
	{

		//variable
		if ($this->defvar()) {
			$token = $this->getToken();
			if ($token == self::SEMICOLON_KEYWORD) {
				$this->getNextToken();
				return true;
			} else
				$this->syntaxError("expected `" . self::SEMICOLON_KEYWORD . "`; $token given");
		}

		$token = $this->getToken();

		//if
		if ($token == self::IF_KEYWORD) {
			$this->getNextToken();

			$token = $this->getToken();
			if ($token == self::OPEN_PARENTHESES) {
				$this->getNextToken();

				if ($expAddr = $this->expr()) {

					$token = $this->getToken();
					if ($token == self::CLOSE_PARENTHESES) {
						$this->getNextToken();

						$out = $this->ir->label();
						$addr = $expAddr->symbol->addr;
						$this->ir->write("jnz $addr, $out\n");

						$this->stmt($funcNode, true);

						$token = $this->getToken();
						if ($token == self::ELSE_KEYWORD) {
							$this->getNextToken();

							$this->ir->writeLabel($out);

							$this->stmt($funcNode);
							$token = $this->getToken();
							if ($token == self::END_KEYWORD) { //end token was ignored with checking $fromIf
								$this->symbolTable->resetScope($this->scopeId--);
								$this->getNextToken();
								return true;
							}
						}

						$this->ir->write("$out: \n");

						return true;
					} else
						$this->syntaxError("expected `" . self::CLOSE_PARENTHESES . "`; $token given");
				}
			} else
				$this->syntaxError("expected `" . self::OPEN_PARENTHESES . "`; $token given");
		}

		//while
		if ($token == self::WHILE_KEYWORD) {
			$this->getNextToken();

			$out = $this->ir->label();
			$beg = $this->ir->label();

			$this->ir->writeLabel($beg);

			$token = $this->getToken();
			if ($token == self::OPEN_PARENTHESES) {
				$this->getNextToken();

				if ($expAddr = $this->expr()) {

					$token = $this->getToken();
					if ($token == self::CLOSE_PARENTHESES) {
						$this->getNextToken();

						$this->ir->write("jnz $expAddr, $out \n");

						$token = $this->getToken();
						if ($token == self::DO_KEYWORD) {
							$this->getNextToken();

							if ($this->stmt($funcNode)) {
								$this->ir->write("jmp $beg");
								$this->ir->writeLabel($out);
								return true;
							}
						} else
							$this->syntaxError("expected `" . self::DO_KEYWORD . "`; $token given");
					} else
						$this->syntaxError("expected `" . self::OPEN_PARENTHESES . "`; $token given");
				}
			} else
				$this->syntaxError("expected `" . self::OPEN_PARENTHESES . "`; $token given");
		}


		//foreach
		if ($token == self::FOREACH_KEYWORD) {
			$this->getNextToken();

			$token = $this->getToken();
			if ($token == self::OPEN_PARENTHESES) {
				$this->getNextToken();

				$iden = $this->getToken();
				if ($this->iden($iden, true)) {
					$this->getNextToken();
					$symbol = SymbolTable::setVariable($iden, SymbolTable::INT_TYPE);
					$symbol = $this->symbolTable->addNode($symbol, $this->scopeId + 1, $this->ir->temp()); //+1 because it must be valid inside of foreach scope

					$token = $this->getToken();
					if ($token == self::OF_KEYWORD) {
						$this->getNextToken();

						if ($expr = $this->expr()) {


							if ($expr->typeName != "array")
								$this->log("foreach second parameter must be of type array, $expr->typeName given", true);

							$beg = $this->ir->label();
							$out = $this->ir->label();
							$arraySymbol = $expr->symbol;

							$arrayLen = $this->ir->temp();
							$forIterator = $this->ir->temp();
							$forIteratorAdder = $this->ir->temp();
							$byteSize = $this->ir->temp();
							$this->ir->write("ld $arrayLen, $arraySymbol->addr\n");
							//it should be 0 but we know first index of array is len 
							//so we add it with 1 and multiple it with DATA_BYTE , to avoid add temp(), temp(), 1 mul temp() temp(), DATA_BYTE
							$this->ir->write("mov $byteSize, " . IR::DATA_BYTE . "\n");

							$this->ir->write("mov $forIterator, 0\n");
							$this->ir->write("mov $forIteratorAdder, 1\n");
							$this->ir->writeLabel($beg);
							$cond = $this->ir->temp();
							$this->ir->write("cmp< $cond, $forIterator, $arrayLen\n");
							$this->ir->write("jz $cond, $out\n");

							$this->ir->write("mov $symbol->addr, 1\n");
							$this->ir->write("add $symbol->addr, $symbol->addr, $forIterator\n");
							$this->ir->write("mul $symbol->addr, $symbol->addr, $byteSize\n");
							$this->ir->write("add $symbol->addr, $symbol->addr, $arraySymbol->addr\n"); //desired array address is in $symbol->addr

							$this->ir->write("ld $symbol->addr, $symbol->addr\n");



							$token = $this->getToken();
							if ($token == self::CLOSE_PARENTHESES) {
								$this->getNextToken();

								if ($this->stmt($funcNode)) {
									$this->ir->write("add $forIterator, $forIterator, $forIteratorAdder\n");
									$this->ir->write("jmp $beg\n");
									$this->ir->writeLabel($out);
									return true;
								}
							} else
								$this->syntaxError("expected `" . self::CLOSE_PARENTHESES . "`; $token given");
						}
					} else
						$this->syntaxError("expected `" . self::OF_KEYWORD . "`; $token given");
				}
			} else
				$this->syntaxError("expected `" . self::OPEN_PARENTHESES . "`; $token given");
		}


		if ($token == self::RETURN_KEYWORD) {
			$this->getNextToken();

			$type = $this->expr();

			if ($type) {

				if (!isset($type->type)) {
					$typeSym = SymbolTable::contains($this->symbolTable->getTable(), $type->name, $this->scopeId);
					if ($typeSym) {
						$type->type = $typeSym->getType();
						$type->typeName = SymbolTable::getTypeName($typeSym->getType());
					}
				}
				$returnType = $type->type ?? '';
				if ($returnType != $funcNode->getType())
					$this->log("returning a value with wrong type from \"" . $funcNode->getId() . "\" !");

				$token = $this->getToken();
				if ($token == self::SEMICOLON_KEYWORD) {
					$this->getNextToken();
					if ($type->symbol->addr != "r0")
						$this->ir->write("mov r0, " . $type->symbol->addr . " \n");
					$this->ir->write("ret \n");

					return true;
				} else
					$this->syntaxError("expected `" . self::SEMICOLON_KEYWORD . "`; $token given");
			}
		}

		if ($token == self::COLON) {
			$this->scopeId++;
			$this->getNextToken();

			$this->body($funcNode);
			$token = $this->getToken();
			if ($token == self::END_KEYWORD) {
				$this->symbolTable->resetScope($this->scopeId--);
				$this->getNextToken();
				return true;
			} else if ($fromIf && $token == self::ELSE_KEYWORD)
				return true;
			else
				$this->syntaxError("expected `" . self::END_KEYWORD . "`; $token given");
		}

		//expression
		if ($this->expr()) {
			$token = $this->getToken();
			if ($token == self::SEMICOLON_KEYWORD) {
				// $this->ir->write("\n");

				$this->getNextToken();
				return true;
			} else
				$this->syntaxError("expected `" . self::SEMICOLON_KEYWORD . "`; $token given");
		}

		return false;
	}

	private function defvar()
	{
		$token = $this->getToken();
		$res = false;
		if ($token == self::VAL_KEYWORD) {
			$this->getNextToken();
			$res = true;

			$varType = $this->type();
			$varName = $this->getToken();
			$this->iden($varName, true, $varType) ? $this->getNextToken() : $res = false;
			if ($res)
				$this->symbolTable->addNode(SymbolTable::setVariable($varName, $varType), $this->scopeId, $this->ir->temp());
		}
		return $res;
	}


	/**
	 * This function chain return type can be Int, identifier, function
	 * Grammar is as following :
	 * 
	 * expr -> assign_expr
	 * 
	 * assign_expr -> assign_expr = short_if_expr | short_if_expr
	 * 
	 * short_if_expr -> short_if_expr ? or_expr : or_expr
	 * 					| or_expr
	 * 
	 * or_expr -> or_expr || and_expr | and_expr
	 * 
	 * and_expr -> and_expr && comp_expr | comp_expr
	 * 
	 * comp_expr ->	comp_expr > sum_expr
	 *             	| comp_expr < sum_expr
	 *             	| comp_expr >= sum_expr
	 *             	| comp_expr <= sum_expr
	 *             	| comp_expr == sum_expr
	 *             	| comp_expr != sum_expr
	 *             	| sum_expr
	 * sum_expr -> sum_expr + term_expr
	 *      		| sum_expr - term_expr
	 *      		| term_expr
	 * 
	 * term_expr -> term_expr * factor_expr
	 *       | term_expr / factor_expr
	 *       | term_expr % factor_expr
	 *       | factor_expr
	 * 
	 * factor_expr -> + factor_expr
	 *         | - factor_expr
	 *         | ! factor_expr
	 *         | identifier_expr
	 * 
	 * 
	 * identifier_expr -> 	( identifier_expr )
	 * 						| identifier_expr [ identifier_expr ]
	 * 						| primary_expr
	 * primary_expr ->	iden ( clist )
	 *             		| iden
	 * 					| num
	 */
	private function expr()
	{
		$type = $this->assignExpr();
		return $type;
	}

	private function assignExpr()
	{
		$type = $this->shortIfExpr();
		$token = $this->getToken();
		while ($token == self::ASSIGNMENT_KEYWORD) {
			$this->getNextToken();
			$secondType = $this->assignExpr();


			if ($type->typeName == "identifier") {
				if (($secondType->type ?? '') != SymbolTable::INT_TYPE) {
					if ($secondType->symbol) {
						$secondType->type = $secondType->symbol->getType();
						$secondType->typeName = SymbolTable::getTypeName($secondType->type);
					}
				}
				if (isset($type->symbol)) {
					//check if it is a identifier the assignment is with same type 
					if ($type->symbol->getType() != ($secondType->type ?? 'undefined')) {
						$id = $type->symbol->getId();
						$typeName = SymbolTable::getTypeName($type->symbol->getType());
						$this->log("$id is being assigned to a wrong type '($typeName) = ($secondType->typeName)'");
					}
				} else { // identifier is not defined but it is being assigned we can assign it based on assignment type

					$type->symbol = $this->symbolTable->addNode(SymbolTable::setVariable($type->name, $secondType->type ?? ''), $this->scopeId, $this->ir->temp());
				}
			}

			//if the setArrayVar->active is true, type is an array and we should set the length
			//for additional checking in future if the len is an int not a identifier
			if ($this->setArrayVar->active) {
				$typeSymbol = SymbolTable::contains($this->symbolTable->getTable(), $type->name, $this->scopeId);
				if ($typeSymbol->getType() == SymbolTable::ARRAY_TYPE)
					$typeSymbol->setParamCount($this->setArrayVar->len);
				$this->resetArrayVar();
			}

			//store in array
			if (isset($type->fromArray) && $type->fromArray) {
				$firstSym = $type->symbol;
				$this->ir->write("st $firstSym->addr, " . $secondType->symbol->addr . "\n");
			} else {
				$this->ir->write("mov " . $type->symbol->addr . "," . $secondType->symbol->addr . "\n");
				// $typeSymbol = SymbolTable::contains($this->symbolTable->getTable(), $type->name, $this->scopeId);
				// if ($typeSymbol)
				// $typeSymbol->addr = $secondType->symbol->addr; // just copy addr to avoid additional not necessary temp vars
				// else
				// $type->symbol->addr = $secondType->symbol->addr;
			}





			$token = $this->getToken();
		}
		return $type;
	}


	private function shortIfExpr()
	{
		$type = $this->orExpr();
		$token = $this->getToken();
		if ($token == self::QUESTION_MARK) {
			$this->getNextToken();
			$else = $this->ir->label();
			$out = $this->ir->label();
			$finalAnswer = $this->ir->temp();
			$this->ir->write("jnz " . $type->symbol->addr . ", $else\n");

			$secType = $this->shortIfExpr();
			$this->ir->write("mov $finalAnswer, " . $secType->symbol->addr . "\n");
			$this->ir->write("jmp $out\n");
			$token = $this->getToken();
			if ($token == self::COLON) { //colon for else not for new scope
				$this->getNextToken();
				$this->ir->writeLabel($else);

				$secType = $this->shortIfExpr();
				$this->ir->write("mov $finalAnswer, " . $secType->symbol->addr . "\n");
				$this->ir->writeLabel($out);
			} else
				$this->syntaxError("expected `" . self::COLON . "`; $token given");

			$type->symbol->setAddr($finalAnswer);
		}
		return $type;
	}

	private function orExpr()
	{
		$type = $this->andExpr();
		while ($this->getToken() == self::OR_KEYWORD) {
			$this->getNextToken();
			$finalAnswer = $this->ir->temp();
			$first = $type->symbol->addr;

			$out = $this->ir->label();
			$this->ir->write("mov $finalAnswer, 1\n");
			$this->ir->write("jnz $first, $out\n");

			$secType = $this->expr();
			$second = $secType->symbol->addr;

			$this->ir->write("jnz $second, $out\n");
			$this->ir->write("mov $finalAnswer, 0\n");

			$this->ir->writeLabel($out);
			$type->symbol->setAddr($finalAnswer);
			$type->name .= "||" .  $secType->name;
		}
		return $type;
	}

	private function andExpr()
	{
		$type = $this->compExpr();

		while ($this->getToken() == self::AND_KEYWORD) {
			$this->getNextToken();
			$finalAnswer = $this->ir->temp();
			$first = $type->symbol->addr;

			$out = $this->ir->label();
			$this->ir->write("mov $finalAnswer, 0\n");
			$this->ir->write("jz $first, $out\n");

			$secType = $this->expr();
			$second = $secType->symbol->addr;

			$this->ir->write("jz $second, $out\n");
			$this->ir->write("mov $finalAnswer, 1\n");

			$this->ir->writeLabel($out);
			$type->symbol->setAddr($finalAnswer);
			$type->name .= "&&" .  $secType->name;
		}
		return $type;
	}

	private function compExpr()
	{
		$type = $this->sumExpr();

		$tokens = [
			self::GREATER_KEYWORD,
			self::GREATER_EQUAL_KEYWORD,
			self::SMALLER_KEYWORD,
			self::SMALLER_EQUAL_KEYWORD,
			self::NOT_EQUAL_KEYWORD,
			self::EQUAL_KEYWORD
		];
		$token = $this->getToken();
		while (in_array($token, $tokens)) {
			$this->getNextToken();

			$secType = $this->expr();

			$temp = $this->ir->temp();
			switch ($token) {
				case self::EQUAL_KEYWORD: {
						$type->name .= "==" .  $secType->name;
						$this->ir->write("cmp= $temp, " . $type->symbol->addr . "," . $secType->symbol->addr . "\n");
						break;
					}

				case self::NOT_EQUAL_KEYWORD: {
						$type->name .= "!=" .  $secType->name;
						$this->ir->doNotEqual($temp, $type->symbol->addr, $secType->symbol->addr);
						break;
					}

				case self::SMALLER_EQUAL_KEYWORD: {
						$type->name .= "<=" .  $secType->name;
						$this->ir->write("cmp<= $temp," . $type->symbol->addr . "," . $secType->symbol->addr . "\n");
						break;
					}

				case self::SMALLER_KEYWORD: {
						$type->name .= "<" .  $secType->name;
						$this->ir->write("cmp< $temp," . $type->symbol->addr . "," . $secType->symbol->addr . "\n");
						break;
					}

				case self::GREATER_EQUAL_KEYWORD: {
						$type->name .= "=>" .  $secType->name;
						$this->ir->write("cmp>= $temp," . $type->symbol->addr . "," . $secType->symbol->addr . "\n");
						break;
					}
				case self::GREATER_KEYWORD: {
						$type->name .= ">" .  $secType->name;
						$this->ir->write("cmp> $temp," . $type->symbol->addr . "," . $secType->symbol->addr . "\n");
						break;
					}
			}
			$type->symbol->setAddr($temp);
			$token = $this->getToken();
		}
		return $type;
	}

	private function sumExpr()
	{
		$type = $this->termExpr();
		$token = $this->getToken();
		while (in_array($token, [self::ADDITION_KEYWORD, self::SUBTRACT_KEYWORD])) {
			$this->getNextToken();
			$secondType = $this->sumExpr();

			if ($type->typeName == "identifier" && !$type->symbol)
				$this->syntaxError("$type->id value is used, but is not defined");

			if ($secondType->typeName == "identifier" && !$secondType->symbol)
				$this->syntaxError("$secondType->id value is used, but is not defined");

			$temp = $this->ir->temp();
			switch ($token) {
				case self::ADDITION_KEYWORD: {
						$type->name .= "+" .  $secondType->name;
						$this->ir->write("add $temp, " . $type->symbol->addr . ", " . $secondType->symbol->addr . "\n");
						break;
					}

				case self::SUBTRACT_KEYWORD: {
						$type->name .= "-" .  $secondType->name;
						$this->ir->write("sub $temp, " . $type->symbol->addr . ", " . $secondType->symbol->addr . "\n");
						break;
					}
			}
			$type->symbol->setAddr($temp);
			$token = $this->getToken();
		}
		return $type;
	}

	private function termExpr()
	{
		$type = $this->factorExpr();

		$token = $this->getToken();
		while (in_array($token, [self::MULTIPLICATION_KEYWORD, self::DIVIDE_KEYWORD, self::MODULE_KEYWORD])) {
			$this->getNextToken();
			$secondType = $this->termExpr();

			if ($type->typeName == "identifier" && !$type->symbol)
				$this->syntaxError("$type->name value is used, but is not defined");

			if ($secondType->typeName == "identifier" && !$secondType->symbol)
				$this->syntaxError("$secondType->name value is used, but is not defined");

			$temp = $this->ir->temp();
			switch ($token) {
				case self::MULTIPLICATION_KEYWORD: {
						$type->name .= "*" .  $secondType->name;
						$this->ir->write("mul $temp, " . $type->symbol->addr . ", " . $secondType->symbol->addr . "\n");
						break;
					}

				case self::DIVIDE_KEYWORD: {
						$type->name .= "/" .  $secondType->name;
						$this->ir->write("div $temp, " . $type->symbol->addr . ", " . $secondType->symbol->addr . "\n");
						break;
					}

				case self::MODULE_KEYWORD: {
						$type->name .= "%" .  $secondType->name;
						$this->ir->write("mod $temp, " . $type->symbol->addr . ", " . $secondType->symbol->addr . "\n");
						break;
					}
			}
			$type->symbol->setAddr($temp);
			$token = $this->getToken();
		}
		return $type;
	}

	private function factorExpr()
	{
		$token = $this->getToken();
		if (in_array($token, [self::POSITIVATE_KEYWORD, self::NEGATE_KEYWORD, self::NOT_KEYWORD])) {
			$this->getNextToken();
			$type = $this->identifierExpr();
			$finalAnswer = $this->ir->temp();
			switch ($token) {
				case self::POSITIVATE_KEYWORD: {
						//no op
						break;
					}
				case self::NEGATE_KEYWORD: {
						$temp = $this->ir->temp();
						$this->ir->write("mov $temp, -1\n");
						$this->ir->write("mul $finalAnswer, " . $type->symbol->addr . ", $temp\n");
						break;
					}

				case self::NOT_KEYWORD: {
						$out = $this->ir->label();
						$this->ir->write("mov $finalAnswer, 0\n");
						$this->ir->write("jnz " . $type->symbol->addr . ", $out\n");
						$this->ir->write("mov $finalAnswer, 1\n");
						$this->ir->writeLabel($out);
						break;
					}
			}
			$type->symbol->setAddr($finalAnswer);
			return $type;
		}

		return $this->identifierExpr();;
	}

	private function identifierExpr()
	{
		$type = $this->primaryExpr();
		$token = $this->getToken();
		if ($token == self::OPEN_BRACKET) {
			$this->getNextToken();
			$firstSymbol = $type->symbol;

			$secType = $this->expr();
			$secondSym = $secType->symbol;

			if ($type->typeName != "array")
				$this->syntaxError("unexpected token [ after $type->name");
			// if ($secType->typeName != SymbolTable::getTypeName(SymbolTable::INT_TYPE))
			// 	$this->syntaxError("unexpected index");

			$index = (((int)$secondSym->getId()));
			$arrayLen = $firstSymbol->getParamCount();
			if ($arrayLen > 0 && $index >=  $arrayLen)
				$this->log("set value out of bounds of array max index is " . $arrayLen - 1 . " setting $index", true);

			$t = $this->ir->temp();
			$t2 = $this->ir->temp();
			$this->ir->write("mov $t, 1\n");
			$this->ir->write("mov $t2, " . IR::DATA_BYTE . "\n");
			$this->ir->write("add $t, $t, $secondSym->addr\n");
			$this->ir->write("mul $t, $t, $t2\n");
			$this->ir->write("add $t, $t, $firstSymbol->addr\n"); //desired array address is in $t


			$object = (object)[];
			$object->name = $firstSymbol->addr . "[$secondSym->addr]";
			$object->fromArray = true;
			$object->typeName = SymbolTable::getTypeName(SymbolTable::INT_TYPE);
			$object->type = SymbolTable::INT_TYPE;
			$addr = $t;
			$object->symbol = SymbolTable::setInt($firstSymbol->addr . "[$secondSym->addr]", $addr);



			$token = $this->getToken();
			if ($token == self::CLOSE_BRACKET) {
				$this->getNextToken();
				return $object;
			} else
				$this->syntaxError("expected `" . self::CLOSE_BRACKET . "`; $token given");
		} else if ($token == self::OPEN_PARENTHESES) {
			$this->getNextToken();

			$type = $this->expr();

			$token = $this->getToken();
			if ($token == self::CLOSE_PARENTHESES) {
				$this->getNextToken();
				return $type;
			} else
				$this->syntaxError("expected `" . self::CLOSE_PARENTHESES . "`; $token given");
		}
		return $type;
	}

	private function primaryExpr()
	{
		$token = $this->getToken();
		$object = false;
		if ($this->num($token)) {
			$object = (object)[];
			$object->name = $token;
			$object->typeName = SymbolTable::getTypeName(SymbolTable::INT_TYPE);
			$object->type = SymbolTable::INT_TYPE;
			$addr = $this->ir->temp();
			$object->symbol = SymbolTable::setInt($token, $addr);
			//write ir directly
			$this->ir->write("mov $addr, $token\n");

			$this->getNextToken();
			return $object;
		} else if ($this->iden($token, false)) {
			$object = (object)[];
			$object->name = $token;
			$object->typeName = "identifier";
			$this->getNextToken();

			$token = $this->getToken();
			if ($token == self::OPEN_PARENTHESES) {
				$this->getNextToken();

				$functionSymbol = SymbolTable::contains($this->symbolTable->getTable(), $object->name, $this->scopeId);
				$params = $this->clist();

				if ($functionSymbol && $functionSymbol->isFunction()) { //if function is defined start checking params
					$paramCount = $functionSymbol->getParamCount();
					$provided = count($params);
					if ($paramCount > $provided)
						$this->log("$object->name needs $paramCount arguments but only $provided given!", true);
					if ($paramCount < $provided)
						$this->log("$object->name needs $paramCount arguments but $provided given, some of them are useless!");

					if ($provided >= $paramCount) {
						$funcParamsSymbols = $functionSymbol->getTable();
						for ($i = 0; $i < $paramCount; $i++) {
							if (!$params[$i]->hasType() || $funcParamsSymbols[$i]->getType() != $params[$i]->getType())
								$this->log("wrong type for argument " . $i + 1 . " of '$object->name'");
						}
					}


					$token = $this->getToken();
					if ($token == self::CLOSE_PARENTHESES) {
						$this->getNextToken();

						//function
						$object->typeName = "function";

						$funcName = $object->name;
						$addr = (isset($params[0])) ? $params[0]->addr : $this->ir->temp();
						$functionSymbol->setAddr($addr);
						$resIR = "call $funcName, $addr";

						if (count($params) > 1)
							foreach ($params as $key => $symbol)
								if ($key > 0)
									$resIR .= ", $symbol->addr";


						$this->builtInFunctionsAddons($funcName, $params);
						$this->ir->write($resIR . "\n");
					} else
						$this->syntaxError("expected `" . self::CLOSE_PARENTHESES . "`; $token given");
				} else
					$this->syntaxError("call to undefined function $object->name");
			}


			//identifier
		}
		if ($object) {
			$obj =
				SymbolTable::contains($this->symbolTable->getTable(), $object->name, $this->scopeId);
			if ($obj) {
				if ($obj->getType() == SymbolTable::ARRAY_TYPE)
					$object->typeName = "array";
				$object->symbol = clone $obj;
			}
		}
		return $object;
	}

	/**
	 * can end the execution on error
	 */
	private function flist($canBeNull = true): array
	{
		$token = $this->getToken();
		// flist used only once and ) comes after it,
		// so if the next token is ) the flist is Empty
		if ($token == self::CLOSE_PARENTHESES && $canBeNull)
			return []; //empty list

		$res = [];

		$paramType = $this->type();
		$paramName = $this->getToken();
		$this->iden($paramName, true, $paramType) ? $this->getNextToken() : null;

		$res[] = SymbolTable::setVariable($paramName, $paramType);

		$token = $this->getToken();
		if ($token == self::COMMA_KEYWORD) {
			$this->getNextToken();
			array_push($res, $this->flist(false));
		}


		return Helper::array_flatten($res);
	}

	private function clist($canBeNull = true): array
	{
		$token = $this->getToken();
		// clist used only once and ) comes after it,
		// so if the next token is ) the clist is Empty
		if ($token == self::CLOSE_PARENTHESES && $canBeNull)
			return [];

		$type = $this->expr();

		$res = [];
		array_push($res, $type->symbol);

		$token = $this->getToken();
		if ($token == self::COMMA_KEYWORD) {
			$this->getNextToken();
			array_push($res, $this->clist(false));
		}

		return Helper::array_flatten($res);
	}

	/**
	 * can end the execution on error
	 */
	private function type()
	{
		$token = $this->getToken();
		$res = "";
		if ($token == self::ARRAY_KEYWORD)
			$res = SymbolTable::ARRAY_TYPE;
		else if ($token == self::NIL_KEYWORD)
			$res = SymbolTable::NULL_TYPE;
		else if ($token == self::INT_KEYWORD)
			$res = SymbolTable::INT_TYPE;
		else
			$this->syntaxError("Type must be one of following : " . self::ARRAY_KEYWORD . " or " . self::INT_KEYWORD . " or " . self::NIL_KEYWORD);

		$this->getNextToken();
		return $res;
	}

	private function num($token)
	{
		return (bool)preg_match('/^[0-9]+$/', $token);
	}

	/**
	 * can end the execution on error
	 * if an iden is not being declared it is being used (an iden has only two state 1)beingDeclared 2)being used)
	 */
	private function iden($token, $beingDeclared = false, $type = '')
	{
		if (in_array($token, Lexer::BUILT_IN_TOKENS))
			return false;
		if (preg_match('/^[a-zA-z][a-zA-Z_0-9]*$/', $token)) {
			if ($beingDeclared) {
				$alreadYDeclared = $this->checkDeclared($token, false);
				if ($alreadYDeclared) { //variable can be declare again with same type but functions can't
					$typeName = SymbolTable::getTypeName($alreadYDeclared->getType());
					if ($alreadYDeclared->getType() != $type) // syntax error will exit the process so log won't be called again
						$this->syntaxError("$token is already Declared with another Type ($typeName)");
					$this->log("$token is already Declared with the same Type ($typeName)");
				}
			} else { //beingUsed so it must be declared
				$this->checkDeclared($token);
			}

			return true;
		} else
			return false;
	}

	/**
	 * check if token is declared
	 */
	private function checkDeclared($token, $showWarning = true): bool | SymbolTable
	{
		$symbol = SymbolTable::contains($this->symbolTable->getTable(), $token, $this->scopeId);
		if (!$symbol && $showWarning)
			$this->log("$token is not defined");
		return $symbol;
	}

	private function syntaxError($string)
	{
		$this->ir->stopWriting();
		echo "\e[31mParser Error :\nLine Number :" . $this->getCounter() . ", Reason : $string\e[0m\n\n";
		die(1);
	}

	private function log($string, $stopWritingIR = false)
	{
		if ($stopWritingIR)
			$this->ir->stopWriting();
		echo "\e[93mParser Warning :\nLine Number :" . $this->getCounter() . ", Reason : $string\n\n";
	}

	private function builtInFunctionsAddons($funcName, $params)
	{
		switch ($funcName) {
			case "createArray": {
					$lenSym = $params[0];
					$id = $lenSym->getId();
					if ($this->num($id)) {
						if ((int)$id <= 0)
							$this->log("array len must be greater than 0", true);
						$this->setArrayVar->active = true;
						$this->setArrayVar->len = (int)$lenSym->getId();
					}

					break;
				}
		}
	}
}
