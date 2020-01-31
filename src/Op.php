<?php

namespace Wikimedia\PhpTurtle;

class Op {
	public const PUSH_FRAME = 0;

	/**
	 * Return the number of arguments used for the given $op.
	 * @param int $op The opcode
	 * @return int The number of arguments required for that opcode.
	 */
	public static function args( int $op ): int {
		return 0; // XXX!
	}
}
