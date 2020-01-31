<?php

namespace Wikimedia\PhpTurtle;

class Environment {
	/** @var JsObject */
	public $myObject, $myArray, $myFunction, $myString, $myNumber, $myBoolean;
	public $myTrue, $myFalse, $myMath;

	public function __construct() {
		$myObject = $this->myObject = new JsObject( null );
		$myObject->__setHidden( 'type', 'object' );

		$myArray = $this->myArray = new JsObject( $myObject );
		$myArray->__setHidden( 'type', 'array' );
		$myArray->__setHidden( 'length', 0.0 );

		$myFunction = $this->myFunction = new JsObject( $myObject );
		$myFunction->__setHidden( 'type', 'function' );
		$myFunction->__setHidden( 'value', JsUndefined::value() ); // save space

		$myString = $this->myString = new JsObject( $myObject );
		$myString->__setHidden( 'type', 'string' );

		$myNumber = $this->myNumber = new JsObject( $myObject );
		$myNumber->__setHidden( 'type', 'number' );

		$myBoolean = $this->myBoolean = new JsObject( $myObject );
		$myBoolean->__setHidden( 'type', 'boolean' );

		$myTrue = $this->myTrue = new JsObject( $myBoolean );
		$myTrue->__setHidden( 'value', 1.0 );

		$myFalse = $this->myFalse = new JsObject( $myBoolean );
		$myFalse->__setHidden( 'value', 0.0 );

		$myMath = $this->myMath = new JsObject( $myObject );
	}

	/**
	 * Helper function to create an object representing a native function
	 * and add it to $obj with the given $name.
	 * @param JsObject $frame Parent frame for this native function.
	 * @param JsObject $obj The object to which the native function should be
	 *   added.
	 * @param string $name The property name to use.
	 * @param callable $f The native function implementation
	 * @param bool $isHidden Allows the creation of hidden properties.
	 * @return JsObject The JS object representing this native function
	 */
	private function addNativeFunc(
		JsObject $frame, JsObject $obj, string $name,
		callable $f, bool $isHidden = false
	) : JsObject {
		$myFunc = new JsObject( $this->myFunction );
		$myFunc->__setHidden( 'parentFrame', $frame );
		$myFunc->__setHidden( 'value', $f );
		if ( $isHidden ) {
			$obj->__setHidden( $name, $myFunc );
		} else {
			$obj->$name = $myFunc;
		}
		return $myFunc;
	}

	/**
	 * Create a new top-level frame.  This contains all of the 'global'
	 * definitions.
	 * @param ?JsObject $context The `this` object for the frame.
	 * @param array $arguments The `arguments` value for the frame.
	 * @return JsObject The new frame object.
	 */
	public function makeTopLevelFrame( ?JsObject $context, array $arguments ): JsObject {
		$frame = new JsObject( null ); // Object.create(null)
		$undefined = JsUndefined::value();

		// set up 'this' and 'arguments'
		$thisStr = 'this';
		$frame->$thisStr = $context;
		$frame->arguments = $this->arrayCreate( $arguments );

		// constructors
		$myFunction = $this->myFunction;
		$mkConstructor = function ( $name, $proto ) use ( $myFunction, $frame ) {
			$cons = new JsObject( $this->myFunction );
			$cons->prototype = $proto;
			$frame->$name = $cons;
			return $cons;
		};

		$myObjectCons = $mkConstructor( "Object", $this->myObject );
		$mkConstructor( "Array", $this->myArray );
		$mkConstructor( "Function", $this->myFunction );
		$myBooleanCons = $mkConstructor( "Boolean", $this->myBoolean );
		$myStringCons = $mkConstructor( "String", $this->myString );
		$mkConstructor( "Number", $this->myNumber );

		$frame->Math = $this->myMath;

		// helper function
		$getarg = function ( array $args, int $i ) use ( $undefined ) {
			return ( count( $args ) > $i ) ? $args[$i] : $undefined;
		};

		// Boolean called as a function
		$myBooleanCons->__setHidden( 'parentFrame', $frame );
		$myBooleanCons->__setHidden( 'value', function (
			$_this, $args
		) use ( $getarg ) {
			return $this->toBoolean( $getarg( $args, 0 ) );
		} );

		// support for console.log
		$myConsole = new JsObject( $this->myObject );
		$frame->console = $myConsole;

		// native functions
		$this->addNativeFunc( $frame, $myConsole, 'log', function (
			$_this, $args
		) use ( $undefined ) {
			$sargs = [];
			foreach ( $args as $a ) {
				$sargs = $this->toPhpString( $a );
			}
			echo( implode( ' ', $sargs ) );
			return $undefined;
		} );
		$opts = $this->addNativeFunc( $frame, $this->myObject, 'toString', function (
			$_this, $args
		) {
			$o = $this->toObject( $_this );
			// XXX fetch the [[Class]] internal property of o
			return self::valFromPhpStr( "[object]" );
		} );
		$this->addNativeFunc( $frame, $this->myArray, 'toString', function (
			$_this, $args
		) use ( $opts ) {
			$o = $this->toObject( $_this );
			$func = $o->join;
			if ( !$this->isCallable( $func ) ) {
				$func = $opts;
			}
			return $this->interpretFunction( $func, $o, [] );
		} );
		$this->addNativeFunc( $frame, $this->myObject, 'valueOf', function (
			$_this, $args
		) {
			$o = $this->toObject( $_this );
			// XXX host object support?
			return $o;
		} );
		$this->addNativeFunc( $frame, $this->myObject, 'defaultValue', function (
			$_this, $args
		) use ( $getarg ) {
			$isDate = false; // XXX fix when we support Date objects
			$hint = self::valToPhpStr( $getarg( $args, 0 ) );
			if ( $hint !== 'String' && $hint !== 'Number' ) {
				// @phan-suppress-next-line PhanImpossibleCondition
				$hint = ( $isDate ? 'String' : 'Number' );
			}
			$toString = $_this->toString;
			$valueOf = $_this->valueOf;
			if ( $hint === 'String' ) {
				$first = $toString;
				$second = $valueOf;
			} else {
				$first = $valueOf;
				$second = $toString;
			}
			if ( $this->isCallable( $first ) ) {
				$rv = $this->interpretFunction( $first, $_this, [] );
				if ( !( $rv instanceof JsObject ) ) {
					return $rv;
				}
			}
			if ( $this->isCallable( $second ) ) {
				$rv = $this->interpretFunction( $second, $_this, [] );
				if ( !( $rv instanceof JsObject ) ) {
					return $rv;
				}
			}
			self::fail( 'TypeError' ); // XXX throw
		}, true /* hidden */ );

		$this->addNativeFunc( $frame, $this->myObject, 'hasOwnProperty', function (
			$_this, $args
		) use ( $getarg ) {
			$prop = $this->toPhpString( $getarg( $args, 0 ) );
			if ( is_bool( $_this ) ) {
				$_this = $_this ? $this->myTrue : $this->myFalse;
			}
			if ( $_this instanceof JsObject ) {
				return property_exists( $_this, $prop );
			}
			if ( is_string( $_this ) ) {
				if ( $prop === 'length' ) {
					return true;
				} elseif ( is_numeric( $prop ) &&
						  ( 2 * intval( $prop ) ) < strlen( $_this ) ) {
					return true;
				} else {
					return false;
				}
			}
			if ( is_int( $_this ) || is_float( $_this ) ) {
				return false;
			}
			if ( $_this === null || $_this === JsUndefined::value() ) {
				self::fail( 'TypeError' ); // XXX should throw
			}
			self::fail( 'Unexpected type in hasOwnProperty' );
		} );
		$this->addNativeFunc( $frame, $myObjectCons, 'create', function (
			$_this, $args
		) use ( $getarg ) {
			$parent = $getarg( $args, 0 );
			if ( $parent instanceof JsObject ) {
				return new JsObject( $parent );
			} elseif ( $parent === null ) {
				return new JsObject( null );
			}
			self::fail( 'TypeError' ); // XXX should throw
		} );
		$this->addNativeFunc( $frame, $this->myBoolean, 'valueOf', function (
			$_this, $args
		) use ( $getarg ) {
			if ( is_bool( $_this ) ) {
				return $this;
			} elseif ( $_this instanceof JsObject ) {
				self::fail( 'Boolean.valueOf() unimplemented' );
			} else {
				self::fail( 'TypeError' ); // XXX should throw
			}
		} );
		$this->addNativeFunc( $frame, $frame, 'isNaN', function (
			$_this, $args
		) use ( $getarg ) {
			return is_nan( $this->toNumber( $getarg( $args, 0 ) ) );
		} );
		$this->addNativeFunc( $frame, $frame, 'isFinite', function (
			$_this, $args
		) use ( $getarg ) {
			return is_finite( $this->toNumber( $getarg( $args, 0 ) ) );
		} );
		$this->addNativeFunc( $frame, $frame, 'parseInt', function (
			$_this, $args
		) use ( $getarg ) {
			$number = $getarg( $args, 0 );
			$radix = $getarg( $args, 1 );
			// falsy radix values become radix 10
			if ( $radix === false || $radix === '' || $radix === null ||
				$radix === JsUndefined::value() ) {
				$radix = 10;
			} else {
				$r = $this->toNumber( $radix );
				if ( !is_finite( $r ) ) {
					$radix = 10;
				} elseif ( $r < 2 || $r >= 37 ) {
					$radix = 0; // aka bail
				} else {
					$radix = intval( $r );
				}
			}
			if ( $radix !== 0 ) {
				if ( is_string( $number ) ) {
					// XXX parseInt(' 10x ', 16) = 16, so we seem to trim
					//     non-digit chars from the right.
					$s = trim( $this->toPhpString( $number ) );
					return floatval( intval( $s, $radix ) );
				} elseif ( is_float( $number ) || is_int( $number ) ) {
					// this is weird, but seems to match EcmaScript
					return floatval( intval( strval( $number ), $radix ) );
				}
			}
			return NAN;
		} );
		$this->addNativeFunc( $frame, $frame, 'now', function (
			$_this, $args
		) {
			self::fail( 'now() unimplemented' );
		} );

		return $frame;
	}

	/**
	 * Check whether the given JavaScript value is a native or interpreted
	 * function.
	 * @param mixed $val
	 * @return bool
	 */
	public function isCallable( $val ): bool {
		if ( $val instanceof JsObject ) {
			$f = $val->__getHidden( 'value' );
			if ( is_callable( $f ) ) {
				return true; // Native function
			}
			if ( $f instanceof InterpretedFunction ) {
				return true; // Interpreted function
			}
		}
		return false;
	}

	/**
	 * Convert the given JavaScript value to an object.
	 * @param mixed $val
	 * @return JsObject
	 */
	public function toObject( $val ): JsObject {
		if ( $val instanceof JsObject ) {
			return $val;
		}
		if ( $val === null || $val === JsUndefined::value() ) {
			self::fail( 'TypeError' ); // xxx throw w/in JS
		}
		// XXX should create wrapper types for bool, number, string
		self::fail( 'unimplemented' );
	}

	/**
	 * Convert the given JavaScript object to a primitive (non-object)
	 * JavaScript value.
	 * @param JsObject $val
	 * @param string $hint A hint on what type of primitive to return.
	 * @return mixed
	 */
	public function toPrimitive( JsObject $val, string $hint ) {
		$funcDefaultValue = $val->__getHidden( 'defaultValue' );
		return $this->interpretFunction( $funcDefaultValue, $val, [
			self::valFromPhpStr( $hint )
		] );
	}

	/**
	 * Convert the given JavaScript value to a JavaScript string (UTF-16).
	 * @param mixed $val
	 * @return string
	 */
	public function toJsString( $val ) : string {
		if ( is_string( $val ) ) {
			return $val;
		}
		if ( $val instanceof JsObject ) {
			return $this->toJsString( $this->toPrimitive( $val, 'String' ) );
		}
		return self::valFromPhpStr( self::valToPhpStr( $val ) );
	}

	/**
	 * Convert the given JavaScript value to a native/PHP string (UTF-8).
	 * @param mixed $val
	 * @return string
	 */
	public function toPhpString( $val ) : string {
		if ( $val instanceof JsObject ) {
			return $this->toPhpString( $this->toPrimitive( $val, 'String' ) );
		}
		return self::valToPhpStr( $val );
	}

	/**
	 * Convert the given JavaScript value to a native/PHP boolean.
	 * @param mixed $val
	 * @return bool
	 */
	public function toBoolean( $val ): bool {
		if ( is_bool( $val ) ) {
			return $val;
		} elseif ( is_string( $val ) ) {
			return $val !== '';
		} elseif ( $val === null || $val === JsUndefined::value() ) {
			return false;
		} elseif ( is_float( $val ) ) {
			return !( is_nan( $val ) || $val === 0.0 ); /* +0,-0, or NaN*/
		} elseif ( is_int( $val ) ) {
			return $val != 0;
		} elseif ( $val instanceof JsObject ) {
			return true;
		}
		self::fail( 'unimplemented case for toBoolean' );
	}

	/**
	 * Convert the given JavaScript value to a native/PHP float.
	 * @param mixed $val
	 * @return float
	 */
	public function toNumber( $val ): float {
		// this is the conversion done by (eg) bi_mul
		if ( is_int( $val ) || is_float( $val ) ) {
			return $val;
		} elseif ( $val instanceof JsObject ) {
			return $this->toNumber( $this->toPrimitive( $val, 'Number' ) );
		} elseif ( is_string( $val ) ) {
			$s = trim( self::valToPhpStr( $val ) );
			switch ( $s ) {
			case '+Infinity':
			case 'Infinity':
				return INF;
			case '-Infinity':
				return -INF;
			case '': // empty string is zero
				return 0.0;
			default:
				// XXX should support 0xNN format
				return floatval( $s );
			}
		} elseif ( $val === JsUndefined::value() ) {
			return NAN;
		} elseif ( is_bool( $val ) ) {
			return $val ? 1 : 0;
		} elseif ( $val === null ) {
			return 0;
		}
		self::fail( "can't convert to number" );
	}

	/**
	 * Return the value of the given slot in the specified object.  This does
	 * the necessary redirection for primitives, etc.
	 * @param mixed $obj The object
	 * @param string $name The name of the property to get
	 * @return mixed The result of the property lookup
	 */
	public function getSlot( $obj, string $name ) {
		if ( is_string( $obj ) ) {
			if ( $name === '__proto__' ) {
				return $this->myString;
			} elseif ( $name === 'length' ) {
				return floatval( strlen( $obj ) / 2 ); // UTF-16 length is twice JS
			} elseif ( is_numeric( $name ) ) {
				$n = intval( $name ) << 1;
				if ( $n < strlen( $obj ) ) {
					return substr( $obj, $n, 2 );
				} else {
					return JsUndefined::value();
				}
			} else {
				return $this->myString->$name;
			}
		} elseif ( is_bool( $obj ) ) {
			if ( $name === '__proto__' ) {
				return $this->myBoolean;
			} else {
				return ( $obj ? $this->myTrue : $this->myFalse )->$name;
			}
		} elseif ( is_float( $obj ) || is_int( $obj ) ) {
			if ( $name === '__proto__' ) {
				return $this->myNumber;
			} else {
				return $this->myNumber->$name;
			}
		} elseif ( $obj instanceof JsObject ) {
			// XXX add basic typed array support here?
			return $obj->$name; // xxx prototype chains can't include special types
		} elseif ( $obj === null ) {
			self::fail( "dereference of null; should throw exception" );
		} elseif ( $obj === JsUndefined::value() ) {
			self::fail( "dereference of undefined; should throw exception" );
		} else {
			self::fail( "dereference of unexpected type" );
		}
	}

	/**
	 * Create a new JavaScript array object from the provided PHP array of
	 * JavaScript values.
	 * @param array $vals
	 * @return JsObject
	 */
	public function arrayCreate( array $vals ) : JsObject {
		$arr = new JsObject( $this->myArray );
		$arr->length = floatval( count( $vals ) );
		$i = 0;
		foreach ( $vals as $v ) {
			// XXX converting array indexes to strings is a bit of fail.
			$arr->$i = $v;
			$i++;
		}
		return $arr;
	}

	/**
	 * Iterate over a JavaScript array, invoking the callable on each
	 * element.
	 * @param JsObject $arr The JavaScript array
	 * @param callable $f The function to invoke on each element.  If this
	 *   function returns false, iteration will end.
	 * @return bool False if iteration was prematurely halted
	 */
	public function arrayEach( JsObject $arr, callable $f ): bool {
		$i = 0;
		$len = self::valToUint( $arr->length );
		if ( $len === null ) { self::fail( "no length" );
  }
		while ( $i < $len ) {
			// @phan-suppress-next-line PhanTypeInvalidPropertyName
			$v = $arr->$i;
			if ( !$f( $v ) ) {
				return false;
			}
			$i++;
			// This next part isn't strictly necessary for most cases, but
			// it makes the iterator more robust
			$len = self::valToUint( $arr->length );
			if ( $len === null ) { self::fail( "length disappeared" );
   }
		}
		return true;
	}

	/**
	 * Throw an exception (wrapped as a JsThrown).
	 * @param State $state
	 * @param JsThrown $ex The exception to throw
	 * @return State The result state
	 */
	public function throw( State $state, JsThrown $ex ) : State {
		// $ex should be instance of JsThrown
		// xxx set private 'stack' field of the exception to the frame
		// (assuming frame stores function names)
		while ( $state->parent !== null ) {
			$state = $state->parent;
			if ( $state === null ) {
				self::fail( "throw from top of stack" );
			}
		}
		$state->stack[] = $ex;
		// @phan-suppress-next-line PhanTypeMismatchReturnNullable
		return $state;
	}

	/**
	 * Invoke a function from the stack.
	 * @param State $state
	 * @param int $arg1 The number of arguments given to the invocation.
	 * @return State The result state
	 */
	private function invoke( State $state, int $arg1 ): State {
		// collect arguments
		$nativeArgs = [];
		for ( $i = 0; $i < $arg1; $i++ ) {
			$nativeArgs[] = array_pop( $state->stack );
		}
		array_reverse( $nativeArgs );
		// collect 'this'
		$myThis = array_pop( $state->stack );
		// get function object
		$func = array_pop( $state->stack );
		if ( $func instanceof JsObject ) {
			return $this->invokeInternal( $state, $func, $myThis, $nativeArgs );
		}
		self::fail( "Not a function at {$state->pc} function {$state->function->id}" );
	}

	/**
	 * Invoke a function from the stack (after function object, context, and
	 * arguments have been popped off the stack).
	 * @param State $state
	 * @param JsObject $func The function object to invoke
	 * @param mixed $myThis The context object
	 * @param array $args The arguments to the function
	 * @return State The result state
	 */
	private function invokeInternal(
		State $state, JsObject $func, $myThis, array $args
	) : State {
		// assert that $func is a function
		if ( $func->__getHidden( 'type' ) !== 'function' ) {
			// xxx throw a TypeError
			self::fail( "Not a function at $state->pc" );
		}
		$f = $func->__getHidden( 'value' );
		if ( is_callable( $f ) ) { // native function
			$rv = $f( $myThis, $args );
			// handle "apply-like" natives
			if ( $func->__getHidden( 'isApply' ) === true ) {
				$nArgs = 0;
				$this->arrayEach( $rv, function ( $v ) use ( &$nArgs, $state ) {
					$state->stack[] = $v;
					$nArgs++;
					return true;
				} );
				return $this->invoke( $state, $nArgs - 2 );
			}
			// handle exceptions
			if ( $rv instanceof JsThrown ) {
				return $this->throw( $state, $rv );
			}
			$state->stack[] = $rv;
			return $state;
		}
		if ( $f instanceof InterpretedFunction ) {
			// create new frame
			$parentFrame = $func->__getHidden( 'parentFrame' );
			if ( !( $parentFrame instanceof JsObject ) ) {
				self::fail( "couldn't find parent frame" );
			}
			$nFrame = new JsObject( $parentFrame );
			$nFrame->this = $myThis;
			$nFrame->arguments = $this->arrayCreate( $args );
			// construct new child state
			return new State( $state, $nFrame, $f->module, $f->function );
		}
		self::fail( "bad function object" );
	}

	/**
	 * Helper function to execute a unary operation.  The operand is popped
	 * from the stack, and the result is pushed onto the stack.
	 * @param State $state
	 * @param callable $uop The implementation of the unary operation.
	 */
	private function unary( State $state, callable $uop ): void {
		$arg = array_pop( $state->stack );
		$state->stack[] = $uop( $arg );
	}

	/**
	 * Helper function to execute a binary operation.  Operands are popped
	 * from the stack, and the result is pushed onto the stack.
	 * @param State $state
	 * @param callable $bop The implementation of the binary operation.
	 */
	private function binary( State $state, callable $bop ): void {
		$right = array_pop( $state->stack );
		$left = array_pop( $state->stack );
		$state->stack[] = $bop( $left, $right );
	}

	/**
	 * Interpret a JavaScript function object, given the context object
	 * and arguments.
	 * @param JsObject $function The JavaScript function object to interpret
	 * @param mixed $myThis The context object for the execution
	 * @param array $args The function arguments for the execution
	 * @return mixed The result of calling the function.
	 */
	public function interpretFunction( JsObject $function, $myThis, array $args ) {
		// Lookup the module and function id from the function JsVal
		$value = $function->__getHidden( 'value' );
		if ( is_callable( $value ) ) { // native function
			$rv = $value( $myThis, $args );
			// "apply-like" natives
			if ( $function->__getHidden( 'isApply' ) === true ) {
				$nArgs = [];
				$this->arrayEach( $rv, function ( $v ) use ( &$nArgs ) {
					$nArgs[] = $v;
					return true;
				} );
				$nFunction = array_shift( $nArgs );
				$nThis = array_shift( $nArgs );
				return $this->interpretFunction( $nFunction, $nThis, $nArgs );
			}
			return $rv; // might be a thrown exception
		}
		if ( $value instanceof InterpretedFunction ) {
			$parentFrame = $function->__getHidden( 'parentFrame' );
			if ( !( $parentFrame instanceof JsObject ) ) {
				self::fail( "couldn't find parent frame" );
			}
			// make a frame for the function invocation
			$nFrame = new JsObject( $parentFrame );
			$nFrame->this = $myThis;
			$nFrame->arguments = $this->arrayCreate( $args );
			return $this->interpret(
				$function->module, $function->function->id, $nFrame
			);
		}
		self::fail( 'not a function' );
	}

	/**
	 * Interpret an entire function (typically the module initializer).
	 * @param Module $module The module containing the function definition.
	 * @param int $funcId Which function in the module should be executed.
	 * @param ?JsObject $frame The frame in which to execute the function.
	 * @return mixed The return value from the function
	 */
	public function interpret( Module $module, int $funcId, ?JsObject $frame ) {
		if ( $frame === null ) {
			$frame = $this->makeTopLevelFrame( null, [] );
		}
		$function = $module->functions[$funcId];
		$top = new State( null, $frame, $module, $function );
		$state = new State( $top, $frame, $module, $function );
		while ( $state->parent !== null ) { // wait for $state===$top
			$state = $this->interpretOne( $state );
		}
		return array_pop( $state->stack );
	}

	/**
	 * Take one step in the interpreter (ie interpret one bytecode op).
	 * @param State $state
	 * @return State
	 */
	public function interpretOne( State $state ): State {
		$op = $state->function->bytecode[$state->pc++];
		switch ( $op ) {
		case Op::PUSH_FRAME:
			$state->stack[] = $state->frame;
			break;
		case Op::PUSH_LITERAL:
			$arg1 = $state->function->bytecode[$state->pc++];
			$lit = $state->module->literals[$arg1];
			if ( is_string( $lit ) ) {
				$lit = self::valFromPhpStr( $lit );
			}
			$state->stack[] = $lit;
			break;
		case Op::NEW_OBJECT:
			$state->stack[] = new JsObject( $this->myObject );
			break;
		case Op::RETURN:
			$retval = array_pop( $state->stack );
			// go up to the parent state
			$state = $state->parent;
			if ( $state === null ) {
				self::fail( "return from top of stack" );
				throw new \Error( 'just for phan' );
			}
			$state->stack[] = $retval;
			// continue in parent state
			break;
		// branches
		// stack manipulation
		// unary operators
		// binary operators
		case Op::BI_ADD:
			$this->binary( $state, function ( $left, $right ) {
				$lprim = ( $left instanceof JsObject ) ?
					   $this->toPrimitive( $left, '' ) : $left;
				$rprim = ( $right instanceof JsObject ) ?
					   $this->toPrimitive( $right, '' ) : $right;
				if ( is_string( $lprim ) || is_string( $rprim ) ) {
					return $this->toJsString( $lprim ) . $this->toJsString( $rprim );
				}
				return $this->toNumber( $lprim ) + $this->toNumber( $rprim );
			} );
			break;
		case Op::BI_SUB:
			$this->binary( $state, function ( $left, $right ) {
				return $this->toNumber( $left ) - $this->toNumber( $right );
			} );
			break;
		case Op::BI_MUL:
			$this->binary( $state, function ( $left, $right ) {
				return $this->toNumber( $left ) * $this->toNumber( $right );
			} );
			break;
		case Op::BI_DIV:
			$this->binary( $state, function ( $left, $right ) {
				$left = $this->toNumber( $left );
				$right = $this->toNumber( $right );
				if ( $right === 0.0 ) {
					return ( $left > 0 ) ? INF : -INF;
				}
				return $left / $right;
			} );
			break;
		default:
			self::fail( "unknown opcode: " . Op::name( $op ) );
		}
		return $state;
	}

	/**
	 * Create a JavaScript string value from the given UTF-8 encoded
	 * PHP string.
	 * @param string $val
	 * @return mixed
	 */
	public static function valFromPhpStr( string $val ) {
		// XXX in the future we might do different things for ascii and
		// non-ascii strings (ie, wrap the latter)
		return mb_convert_encoding(
			$val,
			'UTF-16', // to
			'UTF-8' // from
		);
	}

	/**
	 * Convert the JavaScript vaue to an unsigned integer.
	 * @param mixed $val
	 * @return ?int The integer value, or null if not convertible
	 */
	public static function valToUint( $val ) : ?int {
		if ( is_int( $val ) ) {
			return $val;
		} elseif ( is_float( $val ) ) {
			return intval( $val );
		} elseif ( is_string( $val ) ) {
			return intval( self::valToPhpStr( $val ) );
		} else {
			return null;
		}
	}

	/**
	 * Convert the JavaScript value to a UTF-8 encoded PHP string.
	 * @param mixed $val
	 * @return string A UTF-8 encoded PHP string.
	 */
	public static function valToPhpStr( $val ) : string {
		if ( $val instanceof JsObject ) {
			return '[object]';
		} elseif ( is_bool( $val ) ) {
			return $val ? 'true' : 'false';
		} elseif ( is_string( $val ) ) { // js string
			return mb_convert_encoding(
				$val,
				'UTF-8', // to
				'UTF-16' // from
			);
		} elseif ( is_float( $val ) || is_int( $val ) ) {
			if ( $val === INF ) {
				return 'Infinity';
			} elseif ( $val === -INF ) {
				return '-Infinity';
			} else {
				return strval( $val );
			}
		} elseif ( $val === null ) {
			return 'null';
		} elseif ( $val === JsUndefined::value() ) {
			return 'undefined';
		} elseif ( $val instanceof InterpretedFunction ) {
			return '[function]';
		} elseif ( is_callable( $val ) ) {
			return '[native function]';
		} else {
			return '[UNKNOWN]';
		}
	}

	/**
	 * Helper function to throw an exception when unexpected execution
	 * occurs.
	 * @param string $msg The message explaining the impossible thing
	 *   which just apparently happened.
	 */
	private static function fail( string $msg ) {
		throw new \Error( $msg );
	}
}
