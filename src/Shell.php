<?php

namespace Wikimedia\PhpTurtle;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Shell extends Application {
	const VERSION = 'v0.0.0';

	const PROMPT      = '>>> ';
	const BUFF_PROMPT = '... ';
	const REPLAY      = '--> ';
	const RETVAL      = '=> ';

	public $includes = [];

	public function __construct() {
		parent::__construct( 'Psy Shell', self::VERSION );
	}

	public function getVersion() {
		return self::VERSION;
	}

	public function setIncludes( array $includes ) {
		$this->includes = $includes;
	}

	public function run( ?InputInterface $input = null, ?OutputInterface $output = null ) {
		$i = new Interpreter();
		if ( count( $this->includes ) < 1 ) {
			while ( true ) {
				$line = readline( self::PROMPT );
				if ( $line === false ) {
					break;
				}
				readline_add_history( $line );
				$rv = $i->repl( $line );
				$this->print_jsval( $i->env, $rv );
			}
		} else {
			foreach ( $this->includes as $filename ) {
				$contents = file_get_contents( $filename );
				$rv = $i->interpret( $contents );
				if ( $rv === JsUndefined::value() ) {
					/* suppress printout */
				} else {
					$this->print_jsval( $i->env, $rv );
				}
			}
		}
	}

	private function print_jsval( Environment $env, $jsval ) {
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
			echo( "$s\n" );
		}
	}
}
