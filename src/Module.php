<?php

namespace Wikimedia\PhpTurtle;

/**
 * This represents a compilation unit (which can be as small as a
 * single function).
 */
class Module {
	/** @var array<BytecodeFunction> */
	public $functions;
	/** @var array */
	public $literals;

	/**
	 * Create a new Module from its functions and literals.
	 * @param array $functions
	 * @param array $literals
	 */
	private function __construct( array $functions, array $literals ) {
		$this->functions = $functions;
		$this->literals = $literals;
	}

	/**
	 * Create a new startup module.
	 * @return Module
	 */
	public static function newStartupModule(): Module {
		$functions = [];
		$literals = [];
		// Startup::startup_init($functions, $literals);
		return new Module( $functions, $literals );
	}

	/**
	 * Create a new module from its serialized representation.
	 * @param string $buf Serialized module
	 * @return Module
	 */
	public static function newFromBytes( string $buf ): Module {
		$reader = new ModuleReader( $buf );
		// Parse the functions
		$num_funcs = $reader->decodeUint();
		$functions = [];
		$func_id = 0;
		while ( $func_id < $num_funcs ) {
			$nargs = $reader->decodeUint();
			$max_stack = $reader->decodeUint();
			$name = $reader->decodeUtf8Str();
			$blen = $reader->decodeUint();
			$bytecode = [];
			while ( count( $bytecode ) < $blen ) {
				$bytecode[] = $reader->decodeUint();
			}
			$functions[] = new BytecodeFunction(
				( $name === "" ) ? null : $name,
				$func_id,
				$nargs,
				$max_stack,
				$bytecode
			);
			$func_id++;
		}
		// Parse literals
		$num_lits = $reader->decodeUint();
		$literals = [];
		while ( count( $literals ) < $num_lits ) {
			switch ( $reader->decodeUint() ) {
			case 0: // Number tag
				$num = $reader->decodeUtf8Str();
				if ( "Infinity" === $num ) {
					$val = INF;
				} elseif ( "-Infinity" === $num ) {
					$val = -INF;
				} else {
					$val = floatval( $num );
				}
				break;
			case 1:
				// XXX literal table is UTF8?
				$val = $reader->decodeUtf8Str();
				break;
			// boolean tags
			case 2:
				$val = true;
				break;
			case 3:
				$val = false;
				break;
			case 4:
				$val = null;
				break;
			case 5:
				$val = JsUndefined::value();
				break;
			default:
				throw new \Error( "unreachable" );
			}
			$literals[] = $val;
		}
		return new Module( $functions, $literals );
	}
}
