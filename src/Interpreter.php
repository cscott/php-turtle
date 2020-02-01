<?php

namespace Wikimedia\PhpTurtle;

class Interpreter {
	/** @var Environment */
	public $env;
	/** @var JsObject */
	private $frame;
	/** @var mixed */
	private $compileFromSource;
	/** @var mixed */
	private $repl;

	/**
	 * Create a new Interpreter.
	 */
	public function __construct() {
		// Create an environment and run the startup code
		$env = $this->env = new Environment();
		$module = Module::newStartupModule();
		$frame = $this->frame = $env->makeTopLevelFrame( null, [] );
		$compileFromSource = $this->compileFromSource =
			$env->interpret( $module, 0, $frame );
		// Create repl
		$makeRepl = $compileFromSource->make_repl;
		$this->repl = $env->interpretFunction( $makeRepl, null, [] );
	}

	/**
	 * Compile a source string to bytecode and then execute it.
	 * @param string $source The TurtleScript source string (UTF-8)
	 * @return mixed TurtleScript return value
	 */
	public function interpret( string $source ) {
		// compile source to bytecode
		$bc = $this->env->interpretFunction(
			$this->compileFromSource, null, [
				Environment::valFromPhpStr( $source )
			] );
		// Create a new module from the bytecode
		if ($bc instanceof JsThrown) {
			// Syntax error during compilation.
			return $bc;
		}
		$buf = '';
		$this->env->arrayEach( $bc, function ( $val ) use ( &$buf ) {
			$buf .= chr( intval( $this->env->toNumber( $val ) ) );
			return true;
		} );
		$nm = Module::newFromBytes( $buf );
		// execute the new module.
		return $this->env->interpret( $nm, 0, $this->frame );
	}

	/**
	 * Execute source string in a REPL.
	 * @param string $source The TurtleScript source string (UTF-8)
	 * @return mixed TurtleScript return value
	 */
	public function repl( string $source ) {
		// compile source to bytecode
		$bc = $this->env->interpretFunction(
			$this->repl, null, [
				Environment::valFromPhpStr( $source )
			] );
		if ( $bc instanceof JsThrown ) {
			return $bc; // parser exception
		}
		// Create a new module from the bytecode
		$buf = '';
		$this->env->arrayEach( $bc, function ( $val ) use ( &$buf ) {
			$buf .= chr( intval( $this->env->toNumber( $val ) ) );
			return true;
		} );
		$nm = Module::newFromBytes( $buf );
		// execute the new module.
		return $this->env->interpret( $nm, 0, $this->frame );
	}
}
