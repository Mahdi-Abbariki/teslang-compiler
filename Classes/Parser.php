<?php

namespace Classes;

use Library\Helper;
use Classes\Lexer;
use Classes\SymbolTable;

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

	private const INT_TYPE = "int";
	private const NUll_TYPE = "null";



	private SymbolTable $symbolTable;

	public function __construct($fileAddress)
	{
		parent::__construct($fileAddress);
		$this->symbolTable = new SymbolTable();
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

			$funcName = $this->getToken();
			$this->iden($funcName) ? $this->getNextToken() : null;

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
							$this->getNextToken();

							$this->body();

							$token = $this->getToken();
							if ($token == self::END_KEYWORD) {
								$this->getNextToken();

								$functionNode = SymbolTable::setFunction($funcName, $funcType, count($funcParams));
								foreach ($funcParams as $symbol)
									$functionNode->addNode($symbol);
								$this->symbolTable->addNode($functionNode);

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
			if(!$this->isEOF())
				$this->syntaxError("function keyword can not be found");
	}


	/**
	 * can end the execution on error
	 */
	private function body()
	{
		if ($this->stmt())
			$this->body();
	}

	private function stmt()
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

				if ($this->expr()) {

					$token = $this->getToken();
					if ($token == self::CLOSE_PARENTHESES) {
						$this->getNextToken();

						$this->stmt();

						$token = $this->getToken();
						if ($token == self::ELSE_KEYWORD) {
							$this->getNextToken();

							$this->stmt();
							return true;
						}

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

			$token = $this->getToken();
			if ($token == self::OPEN_PARENTHESES) {
				$this->getNextToken();

				if ($this->expr()) {

					$token = $this->getToken();
					if ($token == self::CLOSE_PARENTHESES) {
						$this->getNextToken();

						$token = $this->getToken();
						if ($token == self::DO_KEYWORD) {
							$this->getNextToken();

							if ($this->stmt())
								return true;
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
				if ($this->iden($token)) {
					$this->getNextToken();

					$token = $this->getToken();
					if ($token == self::OF_KEYWORD) {
						$this->getNextToken();

						if ($this->expr()) {

							$token = $this->getToken();
							if ($token == self::CLOSE_PARENTHESES) {
								$this->getNextToken();

								if ($this->stmt())
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
				$token = $this->getToken();
				if ($token == self::SEMICOLON_KEYWORD) {
					$this->getNextToken();
					return true;
				} else
					$this->syntaxError("expected `" . self::SEMICOLON_KEYWORD . "`; $token given");
			}
		}

		if ($token == self::COLON) {
			$this->getNextToken();

			$this->body();
			$token = $this->getToken();
			if ($token == self::END_KEYWORD) {
				$this->getNextToken();
				return true;
			} else
				$this->syntaxError("expected `" . self::END_KEYWORD . "`; $token given");
		}

		//expression
		if ($this->expr()) { // TODO
			$token = $this->getToken();
			if ($token == self::SEMICOLON_KEYWORD) {
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
		if ($token == self::VAL_KEYWORD) {
			$this->getNextToken();
			$varType = $this->type();
			$varName = $this->getToken();
			$this->iden($varName) ? $this->getNextToken() : null;
			$this->symbolTable->addNode(SymbolTable::setVariable($varName, $varType));
			return true;
		}
		return false;
	}


	/**
	 * Grammar is as following :
	 * 
	 * expr -> assign_expr
	 * 
	 * assign_expr -> assign_expr = or_expr | or_expr
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
	 *         | short_if_expr
	 * 
	 * short_if_expr -> short_if_expr ? short_if_expr : identifier_expr
	 * 					| identifier_expr
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
		$type = $this->orExpr();

		while ($this->getToken() == self::ASSIGNMENT_KEYWORD) {
			$this->getNextToken();
			$this->orExpr();
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
		while (in_array($this->getToken(), [self::ADDITION_KEYWORD, self::SUBTRACT_KEYWORD])) {
			$this->getNextToken();
			$this->termExpr();
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
		$type = $this->shortIfExpr();
		while (in_array($this->getToken(), [self::POSITIVATE_KEYWORD, self::NEGATE_KEYWORD, self::NOT_KEYWORD])) {
			$this->getNextToken();
			return $this->shortIfExpr();
		}
		return $type;
	}

	private function shortIfExpr()
	{
		$type = $this->identifierExpr();
		$token = $this->getToken();
		if ($token == self::QUESTION_MARK) {
			$this->getNextToken();

			$this->identifierExpr();

			$token = $this->getToken();
			if ($token == self::COLON) {
				$this->getNextToken();
			} else
				$this->syntaxError("expected `" . self::COLON . "`; $token given");
		}
		return $type;
	}

	private function identifierExpr(){
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
		if ($this->num($token)) {
			$this->getNextToken();
			return self::INT_TYPE;
		} else if ($this->iden($token)) {
			$this->getNextToken();

			$token = $this->getToken();
			if ($token == self::OPEN_PARENTHESES) {
				$this->getNextToken();
				$this->clist();
				$token = $this->getToken();
				if ($token == self::CLOSE_PARENTHESES) {
					$this->getNextToken();

					return "function";
				} else
					$this->syntaxError("expected `" . self::CLOSE_PARENTHESES . "`; $token given");
			}

			return "identifier";
		}
		return false;
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
		$this->iden($paramName) ? $this->getNextToken() : null;

		$res[] = SymbolTable::setVariable($paramName, $paramType);

		$token = $this->getToken();
		if ($token == self::COMMA_KEYWORD) {
			$this->getNextToken();
			array_push($res, $this->flist(false));
		}


		return Helper::array_flatten($res);
	}

	private function clist($canBeNull = true)
	{
		$token = $this->getToken();
		// clist used only once and ) comes after it,
		// so if the next token is ) the clist is Empty
		if ($token == self::CLOSE_PARENTHESES && $canBeNull)
			return true;

		$this->expr();

		//$res = [];

		//$paramType = $this->type();
		//$paramName = $this->getToken();
		//$this->iden($paramName) ? $this->getNextToken() : null;

		//$res[] = SymbolTable::setVariable($paramName, "Nil");

		$token = $this->getToken();
		if ($token == self::COMMA_KEYWORD) {
			$this->getNextToken();
			$this->clist(false);
		}

		return true;
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
		elseif ($token == self::NIL_KEYWORD)
			$res = SymbolTable::NULL_TYPE;
		elseif ($token == self::INT_KEYWORD)
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
	 */
	private function iden($token)
	{ 
		if(in_array($token,Lexer::BUILT_IN_TOKENS))
			return false;
		if (preg_match('/^[a-zA-z][a-zA-Z_0-9]*$/', $token))
			return true;
		else
			return false;
	}

	private function syntaxError($string)
	{
		$bt = debug_backtrace(1);
		$caller = array_shift($bt);
		//var_dump($caller);
		echo "Parser Error :\nLine Number :" . $this->getCounter() . ", Reason : $string\n\n";
		die(1);
	}
}
