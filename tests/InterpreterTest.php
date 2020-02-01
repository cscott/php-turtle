<?php

namespace Wikimedia\PhpTurtle\Tests;

use Wikimedia\PhpTurtle\Interpreter;

class InterpreterTest extends \PHPUnit\Framework\TestCase {

	private function doScriptTest( array $script ) {
		$i = new Interpreter();
		foreach ( $script as $line ) {
			$given = $line[0];
			$expected = $line[1];
			$rv = $i->repl( $given );
			$this->assertSame( $expected, $i->env->toPhpString( $rv ), $given );
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

	/** @covers Wikimedia\PhpTurtle\Interpreter */
	public function testCmp() {
		$this->doScriptTest( [
		  [ "'2' > '10'", "true" ],
		   [ "2 > 10", "false" ],
		   [ "2 > '10'", "false" ],
		   [ "'2' > 10", "false" ],
		   [ "'2' >= '10'", "true" ],
		   [ "2 >= 10", "false" ],
		   [ "2 >= '10'", "false" ],
		   [ "'2' >= 10", "false" ],
		   [ "'z' > 10", "false" ],
		  [ "'z' < 10", "false" ],
		] );
	}

	/** @covers Wikimedia\PhpTurtle\Interpreter */
	public function testMul() {
		$this->doScriptTest( [
		   [ "' 10z' * 1", "NaN" ],
		   [ "' 10 ' * 1", "10" ],
		] );
	}

	/** @covers Wikimedia\PhpTurtle\Interpreter */
	public function testNumber_toString() {
		$this->doScriptTest( [
		   [ "Infinity.toString()", "Infinity" ],
		   [ "Infinity.toString(16)", "Infinity" ],
		   [ "NaN", "NaN" ],
		   [ "NaN.toString(16)", "NaN" ],
		] );
	}

	/** @covers Wikimedia\PhpTurtle\Interpreter */
	public function testString_charAt() {
		$this->doScriptTest( [
		   [ "'abc'.charAt()", "a" ],
		   [ "'abc'.charAt(-1)", "" ],
		   [ "'abc'.charAt(1)", "b" ],
		   [ "'abc'.charAt(4)", "" ],
		   [ "'abc'.charAt(NaN)", "a" ],
		   [ "'abc'.charAt('a')", "a" ],
		   [ "'abc'.charAt(1.2)", "b" ],
		   [ "'abc'.charAt(2.9)", "c" ],
		] );
	}

	/** @covers Wikimedia\PhpTurtle\Interpreter */
	public function testMath_floor() {
		$this->doScriptTest( [
		   [ "Math.floor(-1.1)", "-2" ],
		   [ "Math.floor(-1)", "-1" ],
		   [ "Math.floor(0)", "0" ],
		   [ "Math.floor(3)", "3" ],
		   [ "Math.floor(3.2)", "3" ],
		   [ "Math.floor({})", "NaN" ],
		   [ "Math.floor([])", "0" ],
		   [ "Math.floor([1])", "1" ],
		   [ "Math.floor([1,2])", "NaN" ],
		   [ "Math.floor('abc')", "NaN" ],
		   [ "Math.floor(' 10 ')", "10" ],
		   [ "Math.floor()", "NaN" ],
		] );
	}

	/** @covers Wikimedia\PhpTurtle\Interpreter */
	public function testBoolean() {
		$this->doScriptTest( [
		   [ "Boolean(true)", "true" ],
		   [ "Boolean(false)", "false" ],
		   [ "Boolean(0)", "false" ],
		   [ "Boolean(NaN)", "false" ],
		   [ "Boolean('abc')", "true" ],
		   [ "Boolean('')", "false" ],
		   [ "Boolean(123)", "true" ],
		] );
	}

	/** @covers Wikimedia\PhpTurtle\Interpreter */
	public function testToNumber() {
		$this->doScriptTest( [
		   [ "11 * 1", "11" ],
		   [ "' 11\\n' * 1", "11" ],
		   [ "' -11\\n' * 1", "-11" ],
		   [ "true * 1", "1" ],
		   [ "false * 1", "0" ],
		   [ "null * 1", "0" ],
		   [ "undefined * 1", "NaN" ],
		   [ "'xxx' * 1", "NaN" ],
		   [ "'Infinity' * 1", "Infinity" ],
		   [ "'-Infinity' * 1", "-Infinity" ],
		   [ "'inf' * 1", "NaN" ],
		   [ "'-inf' * 1", "NaN" ],
		   [ "'NaN' * 1", "NaN" ],
		   [ "1e1 * 1", "10" ],
		   [ "'1e1' * 1", "10" ],
			//(~"'0x10' * 1", "16" ],// not yet supported
		   [ "'' * 1", "0" ],
		] );
	}

	/** @covers Wikimedia\PhpTurtle\Interpreter */
	public function testObjEq() {
		$this->doScriptTest( [
		   [ "var x = {};", "undefined" ],
		   [ "var y = { f: x };", "undefined" ],
		   [ "var z = { f: x };", "undefined" ],
		   [ "y===z", "false" ],
		   [ "x===x", "true" ],
		   [ "y.f === z.f", "true" ],
		   [ "z.f = {};", "undefined" ],
		   [ "y.f === z.f", "false" ],
		] );
	}

	/** @covers Wikimedia\PhpTurtle\Interpreter */
	public function testString_valueOf() {
		$this->doScriptTest( [
		   [ "var x = 'abc';", "undefined" ],
		   [ "x.valueOf()", "abc" ],
		   [ "x.toString()", "abc" ],
		   [ "x === x.valueOf()", "true" ],
		   [ "x === x.toString()", "true" ],
		   [ "x === x", "true" ],
			// XXX: now with a wrapped string object
		] );
	}

	/** @covers Wikimedia\PhpTurtle\Interpreter */
	public function testArray_join() {
		$this->doScriptTest( [
		   [ "var a = [1,2,3];", "undefined" ],
		   [ "a.toString()", "1,2,3" ],
		   [ "a.join(':')", "1:2:3" ],
		   [ "a.join(4)", "14243" ],
		] );
	}
}
