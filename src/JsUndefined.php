<?php

namespace Wikimedia\PhpTurtle;

/**
 * Singleton object to use for JavaScript 'undefined'.
 */
class JsUndefined {
	/** @var ?JsUndefined */
	private static $singleton = null;

	private function __construct() {
	}

	public function __toString() : string {
		return "undefined";
	}

	/**
	 * Return the singleton 'undefined' value.
	 * @return JsUndefined
	 */
	public static function value(): JsUndefined {
		if ( self::$singleton === null ) {
			self::$singleton = new JsUndefined();
		}
		return self::$singleton;
	}
}
