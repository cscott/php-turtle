<?php

namespace Wikimedia\PhpTurtle\Tests;

use Wikimedia\PhpTurtle\Interpreter;

class InterpreterTest extends \PHPUnit\Framework\TestCase {

	private function doScriptTest( array $script ) {
		$this->markTestIncomplete( 'Interpreter not complete yet' );
		$i = new Interpreter();
		foreach ( $script as $line ) {
			$given = $line[0];
			$expected = $line[1];
			$rv = $i->repl( $given );
			$this->assertSame( $expected, Environment::valToPhpStr( $rv ) );
		}
	}

	/** @covers Wikimedia\PhpTurtle\Interpreter */
	public function testRepl1() {
		$this->doScriptTest( [
			[ "1 + 2", "3" ],
			[ "var x = 4*10 + 2;", "undefined" ],
			[ "x", "42" ],
			[ "console.log('seems to work');", "undefined" ],
			[ "var fib = function(n) { return (n<2) ? 1 : fib(n-1) + fib(n-2); };", "undefined" ],
			[ "fib(10)", "89" ]
		] );
	}

	/** @covers Wikimedia\PhpTurtle\Interpreter */
	public function testParseInt() {
		$this->doScriptTest( [
			// sanity check numeric types
			[ "NaN", "NaN" ],
			[ "Infinity", "Infinity" ],
			[ "-Infinity", "-Infinity" ],
			// test parseInt
			[ "parseInt('10', 16)", "16" ],
			[ "parseInt('10', '16')", "16" ],
			[ "parseInt('10', -10)", "NaN" ],
			[ "parseInt('10', -1)", "NaN" ],
			[ "parseInt('10', 'a')", "10" ],
			[ "parseInt('10', 'ab')", "10" ],
			[ "parseInt('10', NaN)", "10" ],
			[ "parseInt('10', 'NaN')", "10" ],
			[ "parseInt('10', Infinity)", "10" ],
			[ "parseInt('10', 'Infinity')", "10" ],
			[ "parseInt('10', -Infinity)", "10" ],
			[ "parseInt('10', '-Infinity')", "10" ],
			[ "parseInt('11')", "11" ],
			//["parseInt('11z')", "11"], // xxx currently fails
			//["parseInt(' 11z')", "11"], // xxx currently fails
			[ "parseInt('10', '16.5')", "16" ],
			[ "parseInt('10', 16.5)", "16" ],
		] );
	}

	// XXX test_cmp, etc.
}
