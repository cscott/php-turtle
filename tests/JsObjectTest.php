<?php

namespace Wikimedia\PhpTurtle\Tests;

use Wikimedia\PhpTurtle\JsObject;
use Wikimedia\PhpTurtle\JsUndefined;

class JsObjectTest extends \PHPUnit\Framework\TestCase {
	/** @covers Wikimedia\PhpTurtle\JsObject */
	public function testJsObject() {
		$o = new JsObject();
		$o2 = new JsObject();
		$o2->__proto__ = $o;
		$o->b = 5;
		$this->assertSame( 5, $o->b );
		$this->assertSame( 5, $o2->b );
		$o2->b = 6;
		$this->assertSame( 5, $o->b );
		$this->assertSame( 6, $o2->b );

		$this->assertSame( JsUndefined::value(), $o->a );
		$this->assertSame( JsUndefined::value(), $o2->a );
	}
}
