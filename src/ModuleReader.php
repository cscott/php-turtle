<?php

namespace Wikimedia\PhpTurtle;

/** Read/decode the bytecode buffer. */
class ModuleReader {
	/** @var string */
	private $buf;
	/** @var int */
	private $pos;

	/**
	 * Create a new reader for a byte buffer.
	 * @param string $buf The byte buffer to read.
	 */
	public function __construct( string $buf ) {
		$this->buf = $buf;
		$this->pos = 0;
	}

	/**
	 * Read the next unsigned integer from the buffer.
	 * @return int
	 */
	public function decodeUint(): int {
		$val = ord( $this->buf[$this->pos++] );
		return ( $val < 128 ) ? $val :
			// @phan-suppress-next-line PhanPossiblyInfiniteRecursionSameParams
			( ( $val - 128 ) + ( 128 * $this->decodeUint() ) );
	}

	/**
	 * Return the next string from the buffer (as recorded, as a UTF-16 string).
	 * @return string The UTF-16 encoded string
	 */
	public function decodeUtf16Str(): string {
		$len = $this->decodeUint();
		$s = "";
		for ( $i = 0; $i < $len; $i++ ) {
			$c = $this->decodeUint();
			$s .= chr( $c >> 8 );
			$s .= chr( $c & 0xFF );
		}
		return $s;
	}

	/**
	 * Return the next string from the buffer (converted to a UTF-8 string).
	 * @return string The UTF-8 encoded string
	 */
	public function decodeUtf8Str(): string {
		return mb_convert_encoding(
			$this->decodeUtf16Str(),
			'UTF-8', // to
			'UTF-16' // from
		);
	}
}
