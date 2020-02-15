<?php

namespace Wikimedia\PhpTurtle\Tests;

use Wikimedia\PhpTurtle\BytecodeFunction;
use Wikimedia\PhpTurtle\Environment;
use Wikimedia\PhpTurtle\JsUndefined;
use Wikimedia\PhpTurtle\Module;
use Wikimedia\PhpTurtle\Op;

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
					Op::PUSH_LITERAL, 0,     // 0: push_literal(0)
					Op::PUSH_LITERAL, 1,     // 2: push_literal(1)
					Op::BI_ADD,       // 4: bi_add
					Op::RETURN        // 5: return
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
				Op::PUSH_FRAME,          // 0: push_frame
				Op::PUSH_LITERAL, 0,     // 1: push_literal(0)
				Op::SET_SLOT_DIRECT, 1,  // 3: set_slot_direct(1)
				Op::PUSH_FRAME,          // 5: push_frame
				Op::NEW_FUNCTION, 1,     // 6: new_function(1)
				Op::SET_SLOT_DIRECT, 1,  // 8: set_slot_direct(1)
				Op::PUSH_FRAME,          // 10: push_frame
				Op::GET_SLOT_DIRECT, 1,  // 11: get_slot_direct(1)
				Op::PUSH_LOCAL_FRAME,    // 13: push_local_frame
				Op::GET_SLOT_DIRECT, 8,  // 14: get_slot_direct(8)
				Op::PUSH_LITERAL, 9,     // 16: push_literal(9)
				Op::INVOKE, 1,           // 18: invoke(1)
				Op::RETURN               // 20: return
			] ),
			new BytecodeFunction( // "fib"
			"fib",     // name
			1,         // id
			1,         // nargs
			5,         // max_stack
			[
				Op::PUSH_LOCAL_FRAME,    // 0: push_local_frame
				Op::GET_SLOT_DIRECT, 2,  // 1: get_slot_direct(2)
				Op::DUP,                 // 3: dup
				Op::GET_SLOT_DIRECT, 3,  // 4: get_slot_direct(3)
				Op::PUSH_LOCAL_FRAME,    // 6: push_local_frame
				Op::SWAP,                // 7: swap
				Op::SET_SLOT_DIRECT, 4,  // 8: set_slot_direct(4)
				Op::POP,                 // 10: pop
				Op::PUSH_LOCAL_FRAME,    // 11: push_local_frame
				Op::GET_SLOT_DIRECT, 4,  // 12: get_slot_direct(4)
				Op::PUSH_LITERAL, 5,     // 14: push_literal(5)
				Op::SWAP,                // 16: swap
				Op::BI_GT,               // 17: bi_gt
				Op::JMP_UNLESS, 25, 58,  // 18: jmp_unless(25,58)
				Op::PUSH_LITERAL, 6,     // 21: push_literal(6)
				Op::JMP, 58,             // 23: jmp(58)
				Op::PUSH_FRAME,          // 25: push_frame
				Op::GET_SLOT_DIRECT, 7,  // 26: get_slot_direct(7)
				Op::GET_SLOT_DIRECT, 1,  // 28: get_slot_direct(1)
				Op::PUSH_LOCAL_FRAME,    // 30: push_local_frame
				Op::GET_SLOT_DIRECT, 8,  // 31: get_slot_direct(8)
				Op::PUSH_LOCAL_FRAME,    // 33: push_local_frame
				Op::GET_SLOT_DIRECT, 4,  // 34: get_slot_direct(4)
				Op::PUSH_LITERAL, 6,     // 36: push_literal(6)
				Op::BI_SUB,              // 38: bi_sub
				Op::INVOKE, 1,           // 39: invoke(1)
				Op::PUSH_FRAME,          // 41: push_frame
				Op::GET_SLOT_DIRECT, 7,  // 42: get_slot_direct(7)
				Op::GET_SLOT_DIRECT, 1,  // 44: get_slot_direct(1)
				Op::PUSH_LOCAL_FRAME,    // 46: push_local_frame
				Op::GET_SLOT_DIRECT, 8,  // 47: get_slot_direct(8)
				Op::PUSH_LOCAL_FRAME,    // 49: push_local_frame
				Op::GET_SLOT_DIRECT, 4,  // 50: get_slot_direct(4)
				Op::PUSH_LITERAL, 5,     // 52: push_literal(5)
				Op::BI_SUB,              // 54: bi_sub
				Op::INVOKE, 1,           // 55: invoke(1)
				Op::BI_ADD,              // 57: bi_add
				Op::PHI,                 // 58: phi
				Op::RETURN               // 59: return
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
