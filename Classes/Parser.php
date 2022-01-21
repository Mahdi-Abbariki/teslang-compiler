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
	private $scopeId = 0;
	private IR $ir;

	public function __construct($fileAddress)
	{
		parent::__construct($fileAddress);
		$this->symbolTable = new SymbolTable(true);
		$this->ir = new IR(true);
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
							$this->ir->write("proc	" . $funcName . "\n");



							$this->scopeId++;
							//function scope is started so add it to Symbol Table
							$functionNode = SymbolTable::setFunction($funcName, $funcType, count($funcParams));
							foreach ($funcParams as $symbol)
								$functionNode->addNode($symbol, $this->scopeId);
							$this->symbolTable->addNode($functionNode, 0);

							$this->getNextToken();

							$this->body($functionNode);

							$token = $this->getToken();
							if ($token == self::END_KEYWORD) {
								$this->ir->write("\n");

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

	private function stmt($funcNode)
	{

		//variable
		if ($this->defvar()) {
			$token = $this->getToken();
			if ($token == self::SEMICOLON_KEYWORD) {
				$this->ir->write("\n");

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
						$this->ir->write("jnz $expAddr, $out");

						$this->stmt($funcNode);

						$token = $this->getToken();
						if ($token == self::ELSE_KEYWORD) {
							$this->getNextToken();

							$this->ir->write("$out: \n");

							$this->stmt($funcNode);
							return true;
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

			$this->ir->write("$beg: \n");

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

							if ($this->stmt($funcNode)){
								$this->ir->write("jmp $beg \n $out \n");
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

				$token = $this->getToken();
				if ($this->iden($token, false)) {
					$this->getNextToken();

					$token = $this->getToken();
					if ($token == self::OF_KEYWORD) {
						$this->getNextToken();

						if ($this->expr()) {

							$token = $this->getToken();
							if ($token == self::CLOSE_PARENTHESES) {
								$this->getNextToken();

								if ($this->stmt($funcNode))
									return true;
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

					$this->ir->write("mov r0, $type->addr \n ret \n");

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
			} else
				$this->syntaxError("expected `" . self::END_KEYWORD . "`; $token given");
		}

		//expression
		if ($this->expr()) {
			$token = $this->getToken();
			if ($token == self::SEMICOLON_KEYWORD) {
				$this->ir->write("\n");

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
			if($res)
				$this->symbolTable->addNode(SymbolTable::setVariable($varName, $varType), $this->scopeId,$this->ir->temp());

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
			$secondType = $this->shortIfExpr();


			$symbol = SymbolTable::contains($this->symbolTable->getTable(), $type->name, $this->scopeId);
			if ($type->typeName == "identifier") {
				if (($secondType->type ?? '') != SymbolTable::INT_TYPE) {
					$secSym = SymbolTable::contains($this->symbolTable->getTable(), $secondType->name, $this->scopeId);
					if ($secSym) {
						$secondType->type = $secSym->getType();
						$secondType->typeName = SymbolTable::getTypeName($secondType->type);
					}
				}
				if ($symbol) {
					//check if it is a identifier the assignment is with same type 
					if ($symbol->getType() != ($secondType->type ?? 'undefined')) {
						$id = $symbol->getId();
						$typeName = SymbolTable::getTypeName($symbol->getType());
						$this->log("$id is being assigned to a wrong type '($typeName) = ($secondType->typeName)'");
					}else{
						$this->ir->write("mov $symbol,$secondType \n");
					}
				} else { // identifier is not defined but it is being assigned we can assign it based on assignment type

					$this->symbolTable->addNode(SymbolTable::setVariable($type->name, $secondType->type ?? ''), $this->scopeId);
				}
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

			$out = $this->ir->label();
			$this->ir->write("jnz $type->addr, $out");

			$this->orExpr();

			$token = $this->getToken();
			if ($token == self::COLON) { //colon for else not for new scope
				$this->getNextToken();

				$this->ir->write("jnz $type->addr, $out");

			} else
				$this->syntaxError("expected `" . self::COLON . "`; $token given");
		}
		return $type;
	}

	private function orExpr()
	{
		$type = $this->andExpr();

		while ($this->getToken() == self::OR_KEYWORD) {
			$this->getNextToken();
			$this->andExpr();
		}
		return $type;
	}

	private function andExpr()
	{
		$type = $this->compExpr();

		while ($this->getToken() == self::AND_KEYWORD) {
			$this->getNextToken();
			$this->compExpr();
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
		while (in_array($this->getToken(), $tokens)) {
			$this->getNextToken();
			$this->sumExpr();
		}
		return $type;
	}

	private function sumExpr()
	{
		$type = $this->termExpr();
		$token = $this->getToken();
		while (in_array($token, [self::ADDITION_KEYWORD, self::SUBTRACT_KEYWORD])) {
			$this->getNextToken();
			$this->termExpr();
			$token = $this->getToken();
		}
		return $type;
	}

	private function termExpr()
	{
		$type = $this->factorExpr();

		while (in_array($this->getToken(), [self::MULTIPLICATION_KEYWORD, self::DIVIDE_KEYWORD, self::MODULE_KEYWORD])) {
			$this->getNextToken();
			$this->factorExpr();
		}
		return $type;
	}

	private function factorExpr()
	{
		// $type = 
		while (in_array($this->getToken(), [self::POSITIVATE_KEYWORD, self::NEGATE_KEYWORD, self::NOT_KEYWORD])) {
			$this->getNextToken();
			//return $this->identifierExpr();
		}
		return $this->identifierExpr();;
	}

	private function identifierExpr()
	{
		$type = $this->primaryExpr();
		$token = $this->getToken();
		if ($token == self::OPEN_BRACKET) {
			$this->getNextToken();
			$type = $this->primaryExpr();

			$token = $this->getToken();
			if ($token == self::CLOSE_BRACKET) {
				$this->getNextToken();
				return $type;
			} else
				$this->syntaxError("expected `" . self::CLOSE_BRACKET . "`; $token given");
		} else if ($token == self::OPEN_PARENTHESES) {
			$this->getNextToken();
			$type = $this->primaryExpr();

			$token = $this->getToken();
			if ($token == self::CLOSE_PARENTHESES) {
				$this->getNextToken();
				return self::NUll_TYPE;
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
			$this->getNextToken();
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
						$this->log("$object->name needs $paramCount arguments but only $provided given!");
					if ($paramCount < $provided)
						$this->log("$object->name needs $paramCount arguments but $provided given, some of them are useless!");

					if ($provided >= $paramCount) {
						$funcParamsSymbols = $functionSymbol->getTable();
						for ($i = 0; $i < $paramCount; $i++) {
							if (!$params[$i]->hasType() || $funcParamsSymbols[$i]->getType() != $params[$i]->getType())
								$this->log("wrong type for argument " . $i + 1 . " of '$object->name'");
						}
					}
				}


				$token = $this->getToken();
				if ($token == self::CLOSE_PARENTHESES) {
					$this->getNextToken();

					//function
					$object->typeName = "function";
				} else
					$this->syntaxError("expected `" . self::CLOSE_PARENTHESES . "`; $token given");
			}


			//identifier
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

		if (!isset($type->type)) {
			$typeSym = SymbolTable::contains($this->symbolTable->getTable(), $type->name, $this->scopeId);
			if ($typeSym) {
				$type->type = $typeSym->getType();
				$type->typeName = SymbolTable::getTypeName($typeSym->getType());
			}
		}

		$paramType = $type->type ?? '';
		$paramName = $type->name;

		$res[] = SymbolTable::setVariable($paramName, $paramType);

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
		if (preg_match('/^[0-9]+$/', $token)) {

			return true;
		} else
			return false;
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
		//$bt = debug_backtrace(1);
		//$caller = array_shift($bt);
		//var_dump($caller);
		echo "\e[31mParser Error :\nLine Number :" . $this->getCounter() . ", Reason : $string\e[0m\n\n";
		die(1);
	}

	private function log($string)
	{
		//$bt = debug_backtrace(1);
		//$caller = array_shift($bt);
		//var_dump($caller);
		echo "\e[93mParser Warning :\nLine Number :" . $this->getCounter() . ", Reason : $string\n\n";
	}
}
