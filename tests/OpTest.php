<?php

namespace Wikimedia\PhpTurtle\Tests;

use Wikimedia\PhpTurtle\Op;

class OpTest extends \PHPUnit\Framework\TestCase {

	/** @covers Wikimedia\PhpTurtle\Op */
	public function testInvoke() {
		$op = Op::INVOKE;
		$args = [ 3 ];
		$this->assertSame( 5, Op::stackpop( $op, $args ) );
	}

	/** @covers Wikimedia\PhpTurtle\Op */
	public function testCast() {
		$this->assertSame( 1, Op::PUSH_LITERAL );
	}
}
