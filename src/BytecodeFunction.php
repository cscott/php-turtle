<?php

namespace Wikimedia\PhpTurtle;

/**
 * Function type.
 */
class BytecodeFunction {
	/** @var ?string */
	public $name;
	/** @var int */
	public $id;
	/** @var int */
	public $nargs;
	/** @var int */
	public $max_stack;
	/** @var array */
	public $bytecode;

	/**
	 * Create a new bytecode function.
	 * @param ?string $name Name of the function
	 * @param int $id Function id
	 * @param int $nargs Number of arguments expected for the function
	 * @param int $max_stack Maximum stack space required
	 * @param array $bytecode Bytecode to execute the function.
	 */
	public function __construct(
		?string $name, int $id, int $nargs, int $max_stack, array $bytecode
	) {
		$this->name = $name;
		$this->id = $id;
		$this->nargs = $nargs;
		$this->max_stack = $max_stack;
		$this->bytecode = $bytecode;
	}
}
