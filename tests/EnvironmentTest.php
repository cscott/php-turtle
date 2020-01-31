<?php

namespace Wikimedia\PhpTurtle\Tests;

use Wikimedia\PhpTurtle\BytecodeFunction;
use Wikimedia\PhpTurtle\Environment;
use Wikimedia\PhpTurtle\JsUndefined;
use Wikimedia\PhpTurtle\Module;

class EnvironmentTest extends \PHPUnit\Framework\TestCase {

	/** @covers Wikimedia\PhpTurtle\Environment::interpret */
	public function testBasic() {
		$env = new Environment();
		// Extremely basic module, just '{ return 1+2; }'
		$functions = [
			new BytecodeFunction(
				null,      // name
				0,         // id
				0,         // nargs
				2,         // max_stack
				[
					1, 0,     // 0: push_literal(0)
					1, 1,     // 2: push_literal(1)
					26,       // 4: bi_add
					11        // 5: return
				] ),
		];
		$literals = [
			floatval( 1 ), // 0
			floatval( 2 ), // 1
		];
		$module = new Module( $functions, $literals );
		$frame = $env->makeTopLevelFrame( null, [] );
		$result = $env->interpret( $module, 0, $frame );
		$this->assertSame( floatval( 3 ), $result );
	}

	/** @covers Wikimedia\PhpTurtle\Environment::interpret */
	public function testFib() {
		$env = new Environment();
		// Slightly more interesting module:
		// { var fib=function(n){return (n<2)?1:fib(n-1)+fib(n-2);}; return fib(10); }
		$functions = [
			new BytecodeFunction(
			null,      // name
			0,         // id
			0,         // nargs
			3,         // max_stack
			[
				0,        // 0: push_frame
				1, 0,     // 1: push_literal(0)
				8, 1,     // 3: set_slot_direct(1)
				0,        // 5: push_frame
				4, 1,     // 6: new_function(1)
				8, 1,     // 8: set_slot_direct(1)
				0,        // 10: push_frame
				5, 1,     // 11: get_slot_direct(1)
				0,        // 13: push_frame
				5, 8,     // 14: get_slot_direct(8)
				1, 9,     // 16: push_literal(9)
				10, 1,    // 18: invoke(1)
				11        // 20: return
			] ),
			new BytecodeFunction( // "fib"
			"fib",     // name
			1,         // id
			1,         // nargs
			5,         // max_stack
			[
				0,        // 0: push_frame
				5, 2,     // 1: get_slot_direct(2)
				15,       // 3: dup
				5, 3,     // 4: get_slot_direct(3)
				0,        // 6: push_frame
				19,       // 7: swap
				8, 4,     // 8: set_slot_direct(4)
				14,       // 10: pop
				0,        // 11: push_frame
				5, 4,     // 12: get_slot_direct(4)
				1, 5,     // 14: push_literal(5)
				19,       // 16: swap
				24,       // 17: bi_gt
				13, 24,   // 18: jmp_unless(24)
				1, 6,     // 20: push_literal(6)
				12, 57,   // 22: jmp(57)
				0,        // 24: push_frame
				5, 7,     // 25: get_slot_direct(7)
				5, 1,     // 27: get_slot_direct(1)
				0,        // 29: push_frame
				5, 8,     // 30: get_slot_direct(8)
				0,        // 32: push_frame
				5, 4,     // 33: get_slot_direct(4)
				1, 6,     // 35: push_literal(6)
				27,       // 37: bi_sub
				10, 1,    // 38: invoke(1)
				0,        // 40: push_frame
				5, 7,     // 41: get_slot_direct(7)
				5, 1,     // 43: get_slot_direct(1)
				0,        // 45: push_frame
				5, 8,     // 46: get_slot_direct(8)
				0,        // 48: push_frame
				5, 4,     // 49: get_slot_direct(4)
				1, 5,     // 51: push_literal(5)
				27,       // 53: bi_sub
				10, 1,    // 54: invoke(1)
				26,       // 56: bi_add
				11        // 57: return
			] ),
		];
		$literals = [
			JsUndefined::value(), // 0
			"fib",     // 1
			"arguments", // 2
			floatval( 0 ), // 3
			"n",       // 4
			floatval( 2 ), // 5
			floatval( 1 ), // 6
			"__proto__", // 7
			"this",    // 8
			floatval( 10 ), // 9
		];
		$module = new Module( $functions, $literals );
		$frame = $env->makeTopLevelFrame( null, [] );
		$result = $env->interpret( $module, 0, $frame );
		$this->assertSame( floatval( 89 ), $result );
	}
}
