<?php

namespace Wikimedia\PhpTurtle;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Shell extends Application {
	public const VERSION = 'v0.0.0';

	public const PROMPT      = '>>> ';
	public const BUFF_PROMPT = '... ';
	public const REPLAY      = '--> ';
	public const RETVAL      = '=> ';

	private $includes = [];

	/**
	 * Create a new REPL shell.
	 */
	public function __construct() {
		parent::__construct( 'Psy Shell', self::VERSION );
	}

	/**
	 * Return the version of PhpTurtle.
	 * @return string
	 */
	public function getVersion() {
		return self::VERSION;
	}

	/**
	 * Set an array of files to load and run.
	 * @param array<string> $includes
	 */
	public function setIncludes( array $includes ): void {
		$this->includes = $includes;
	}

	/**
	 * Source the list of includes, or run the REPL loop if there are no
	 * includes.
	 * @param InputInterface|null $input
	 * @param OutputInterface|null $output
	 */
	public function run( ?InputInterface $input = null, ?OutputInterface $output = null ): void {
		$i = new Interpreter();
		if ( count( $this->includes ) < 1 ) {
			while ( true ) {
				$line = readline( self::PROMPT );
				if ( $line === false ) {
					break;
				}
				readline_add_history( $line );
				$rv = $i->repl( $line );
				$this->printJsVal( $i->env, $rv );
			}
		} else {
			foreach ( $this->includes as $filename ) {
				$contents = file_get_contents( $filename );
				$rv = $i->interpret( $contents );
				if ( $rv === JsUndefined::value() ) {
					/* suppress printout */
				} else {
					$this->printJsVal( $i->env, $rv );
				}
			}
		}
	}

	/**
	 * Echo the value of $jsval.
	 * @param Environment $env
	 * @param mixed $jsval
	 */
	private function printJsVal( Environment $env, $jsval ): void {
		if ( $jsval instanceof JsThrown ) {
			// If there's a message field of the thrown object,
			// print that
			$msg = $env->getSlot( $jsval->ex, "message" );
			if ( $msg === JsUndefined::value() ) {
				$msg = $jsval->ex;
			}
			$msg = $env->toPhpString( $msg );
			echo( "* $msg\n" );
		} else {
			$s = $env->toPhpString( $jsval );
			if ( is_string( $jsval ) ) {
				$s = json_encode( $s );
			}
			echo( "$s\n" );
		}
	}
}
