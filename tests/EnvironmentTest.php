<?php

namespace Wikimedia\PhpTurtle\Tests;

use Wikimedia\PhpTurtle\BytecodeFunction;
use Wikimedia\PhpTurtle\Environment;
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
}
