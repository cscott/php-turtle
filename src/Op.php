<?php
// generated by TurtleScript write-php-ops.js

namespace Wikimedia\PhpTurtle;

class Op {
	public const PUSH_FRAME = 0;
	public const PUSH_LITERAL = 1;
	public const NEW_OBJECT = 2;
	public const NEW_ARRAY = 3;
	public const NEW_FUNCTION = 4;
	public const GET_SLOT_DIRECT = 5;
	public const GET_SLOT_INDIRECT = 6;
	public const GET_SLOT_DIRECT_CHECK = 7;
	public const SET_SLOT_DIRECT = 8;
	public const SET_SLOT_INDIRECT = 9;
	public const INVOKE = 10;
	public const RETURN = 11;
	public const JMP = 12;
	public const JMP_UNLESS = 13;
	public const POP = 14;
	public const DUP = 15;
	public const DUP2 = 16;
	public const OVER = 17;
	public const OVER2 = 18;
	public const SWAP = 19;
	public const UN_NOT = 20;
	public const UN_MINUS = 21;
	public const UN_TYPEOF = 22;
	public const BI_EQ = 23;
	public const BI_GT = 24;
	public const BI_GTE = 25;
	public const BI_ADD = 26;
	public const BI_SUB = 27;
	public const BI_MUL = 28;
	public const BI_DIV = 29;

	/**
	 * Return the number of arguments used for the given opcode.
	 * @param int $op The opcode
	 * @return int The number of arguments required
	 */
	public static function args( int $op ): int {
		switch ( $op ) {
		case self::PUSH_LITERAL:
		case self::NEW_FUNCTION:
		case self::GET_SLOT_DIRECT:
		case self::GET_SLOT_DIRECT_CHECK:
		case self::SET_SLOT_DIRECT:
		case self::INVOKE:
		case self::JMP:
		case self::JMP_UNLESS:
			return 1;
		default:
			return 0;
		}
	}

	/**
	 * Return the number of stack slots pushed by the given opcode.
	 * @param int $op The opcode
	 * @return int The number of stack slots pushed
	 */
	public static function stackpush( int $op ): int {
		switch ( $op ) {
		case self::PUSH_FRAME:
		case self::PUSH_LITERAL:
		case self::NEW_OBJECT:
		case self::NEW_ARRAY:
		case self::NEW_FUNCTION:
		case self::GET_SLOT_DIRECT:
		case self::GET_SLOT_INDIRECT:
		case self::GET_SLOT_DIRECT_CHECK:
		case self::INVOKE:
		case self::UN_NOT:
		case self::UN_MINUS:
		case self::UN_TYPEOF:
		case self::BI_EQ:
		case self::BI_GT:
		case self::BI_GTE:
		case self::BI_ADD:
		case self::BI_SUB:
		case self::BI_MUL:
		case self::BI_DIV:
			return 1;
		case self::DUP:
		case self::SWAP:
			return 2;
		case self::OVER:
			return 3;
		case self::DUP2:
		case self::OVER2:
			return 4;
		default:
			return 0;
		}
	}

	/**
	 * Return the number of stack slots popped by the given opcode.
	 * @param int $op The opcode
	 * @param array $args The arguments to that opcode
	 * @return int The number of stack slots popped
	 */
	public static function stackpop( int $op, array $args ): int {
		switch ( $op ) {
		case self::INVOKE:
			return $args[0] + 2;
		case self::GET_SLOT_DIRECT:
		case self::GET_SLOT_DIRECT_CHECK:
		case self::RETURN:
		case self::JMP_UNLESS:
		case self::POP:
		case self::DUP:
		case self::UN_NOT:
		case self::UN_MINUS:
		case self::UN_TYPEOF:
			return 1;
		case self::GET_SLOT_INDIRECT:
		case self::SET_SLOT_DIRECT:
		case self::DUP2:
		case self::OVER:
		case self::SWAP:
		case self::BI_EQ:
		case self::BI_GT:
		case self::BI_GTE:
		case self::BI_ADD:
		case self::BI_SUB:
		case self::BI_MUL:
		case self::BI_DIV:
			return 2;
		case self::SET_SLOT_INDIRECT:
		case self::OVER2:
			return 3;
		default:
			return 0;
		}
	}

	/**
	 * Return the human-readable name for the given opcode.
	 * @param int $op The opcode
	 * @return string The name of the opcode
	 */
	public static function name( int $op ): string {
		switch ( $op ) {
		case self::PUSH_FRAME:
			return "push_frame";
		case self::PUSH_LITERAL:
			return "push_literal";
		case self::NEW_OBJECT:
			return "new_object";
		case self::NEW_ARRAY:
			return "new_array";
		case self::NEW_FUNCTION:
			return "new_function";
		case self::GET_SLOT_DIRECT:
			return "get_slot_direct";
		case self::GET_SLOT_INDIRECT:
			return "get_slot_indirect";
		case self::GET_SLOT_DIRECT_CHECK:
			return "get_slot_direct_check";
		case self::SET_SLOT_DIRECT:
			return "set_slot_direct";
		case self::SET_SLOT_INDIRECT:
			return "set_slot_indirect";
		case self::INVOKE:
			return "invoke";
		case self::RETURN:
			return "return";
		case self::JMP:
			return "jmp";
		case self::JMP_UNLESS:
			return "jmp_unless";
		case self::POP:
			return "pop";
		case self::DUP:
			return "dup";
		case self::DUP2:
			return "2dup";
		case self::OVER:
			return "over";
		case self::OVER2:
			return "over2";
		case self::SWAP:
			return "swap";
		case self::UN_NOT:
			return "un_not";
		case self::UN_MINUS:
			return "un_minus";
		case self::UN_TYPEOF:
			return "un_typeof";
		case self::BI_EQ:
			return "bi_eq";
		case self::BI_GT:
			return "bi_gt";
		case self::BI_GTE:
			return "bi_gte";
		case self::BI_ADD:
			return "bi_add";
		case self::BI_SUB:
			return "bi_sub";
		case self::BI_MUL:
			return "bi_mul";
		case self::BI_DIV:
			return "bi_div";
		}
	}
}
