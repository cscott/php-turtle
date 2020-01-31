<?php

namespace Wikimedia\PhpTurtle;

/**
 * Tuple representing an executable interpreted function.
 */
class InterpretedFunction {
	/** @var Module */
	public $module;
	/** @var BytecodeFunction */
	public $function;

	/**
	 * Create a new tuple.
	 * @param Module $module
	 * @param BytecodeFunction $function
	 */
	public function __construct(
		Module $module, BytecodeFunction $function
	) {
		$this->module = $module;
		$this->function = $function;
	}
}
