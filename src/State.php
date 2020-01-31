<?php

namespace Wikimedia\PhpTurtle;

/**
 * Main interpreter state.
 */
class State {
	/**
	 * Calling context (another state).
	 * @var ?State
	 */
	public $parent;
	/** @var JsObject */
	public $frame;
	/** @var array */
	public $stack;
	/** @var int */
	public $pc;
	// From bytecode file
	/** @var Module */
	public $module;
	/** @var BytecodeFunction */
	public $function;

	/**
	 * Create a main interpreter state.
	 * @param ?State $parent Calling context
	 * @param JsObject $frame
	 * @param Module $module
	 * @param BytecodeFunction $function
	 */
	public function __construct(
		?State $parent, JsObject $frame,
		Module $module, BytecodeFunction $function
	) {
		$this->parent = $parent;
		$this->frame = $frame;
		$this->stack = []; // size: $function->max_stack
		$this->pc = 0;
		$this->module = $module;
		$this->function = $function;
	}
}
