<?php

namespace Wikimedia\PhpTurtle;

/**
 * JavaScript object implementation.
 */
class JsObject {
	// This is a private property, because it's the hook that stores private
	// information associated with the object.  We'll use __get__ to expose
	// the "public" version of this.
	/**
	 * JavaScript prototype (and other private fields)
	 * @var mixed
	 */
	// phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
	private $__proto__ = null;

	/**
	 * Handle `$obj->method(...)` "JavaScript style" by fetching the contents
	 * of `$obj->method` and dispatching to it.
	 * @param string $name Method name
	 * @param array $arguments Method arguments
	 * @return mixed
	 */
	public function __call( string $name, array $arguments ) {
		// XXX This could be optimized, so we don't have to dispatch
		// through JsFunction::__invoke
		$method = $this->$name;
		return $method( $this, ...$arguments );
	}

	/**
	 * Handle `$obj->prop = ...` -- the only time this should be invoked
	 * is if JS code attempts to set the `__proto__` property.
	 * @param string $name Property name
	 * @param mixed $val Property value
	 */
	public function __set( string $name, $val ) {
		// The only time this should be invoked is for $__proto__
		if ( $name === "__proto__" ) {
			if ( is_array( $this->__proto__ ) ) {
				$this->__proto__['__proto__'] = $val;
			} elseif ( is_array( $val ) ) {
				$this->__proto__ = [ '__proto__' => $val ];
			} else {
				$this->__proto__ = $val;
			}
			return;
		}
		// Property didn't exist
		$this->$name = $val;
	}

	/**
	 * Handle `... = $obj->prop;` -- there's a special case for the `__proto__`
	 * property, but the main use case here is looking up the prototype
	 * chain for this property.
	 * @param string $name Property name
	 * @return mixed Property value
	 */
	public function __get( string $name ) {
		// Special case __proto__, which is our hook for private content
		if ( $name === "__proto__" ) {
			if ( is_array( $this->__proto__ ) ) {
				return $this->__proto__['__proto__'];
			} else {
				return $this->__proto__;
			}
		}
		// Dispatch to prototype chain.  For efficiency, don't just recursively
		// invoke __proto__->__get($name), but open code it as an iterative loop
		$parent = $this->__proto__;
		while ( true ) {
			if ( is_array( $parent ) ) {
				// strip the private info.
				$parent = $parent['__proto__'];
			}
			if ( !( $parent instanceof JsObject ) ) {
				// This includes null and JsUndefined
				break;
			}
			if ( property_exists( $parent, $name ) ) {
				return $parent->$name;
			}
			// go one level up the chain.
			$parent = $parent->__proto__;
		}
		return JsUndefined::value();
	}

	// phpcs:ignore MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
	public function __toString() : string {
		// First try to invoke JavaScript 'toString' method
		try {
			$jsStr = $this->toString();
			return mb_convert_encoding( $jsStr, 'UTF-16', 'UTF-8' );
		} catch ( \Throwable $e ) {
			return "[object Object]";
		}
	}
}
