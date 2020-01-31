<?php

namespace Wikimedia\PhpTurtle;

/**
 * Wrapper for a thrown value.
 */
class JsThrown {
	public $ex;

	/**
	 * Create a new thrown value.
	 * @param mixed $ex Wrapped exception
	 */
	public function __construct( $ex ) {
		$this->ex = $ex;
	}
}
