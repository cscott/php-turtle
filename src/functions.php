<?php

namespace Wikimedia\PhpTurtle;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

if ( !\function_exists( 'Wikimedia\\PhpTurtle\\repl' ) ) {
	/**
	 * `phpturtle` command line executable.
	 *
	 * @return \Closure
	 */
	function repl() {
		return function () {
			$usageException = null;

			$input = new ArgvInput();
			try {
				$input->bind( new InputDefinition( [
					new InputOption( 'help',     'h',  InputOption::VALUE_NONE ),
					new InputOption( 'version',  'v',  InputOption::VALUE_NONE ),

					new InputArgument( 'include', InputArgument::IS_ARRAY ),
				] ) );
			} catch ( \RuntimeException $e ) {
				$usageException = $e;
			}

			$shell = new Shell();

			// Handle --help
			if ( $usageException !== null || $input->getOption( 'help' ) ) {
				if ( $usageException !== null ) {
					echo $usageException->getMessage() . PHP_EOL . PHP_EOL;
				}

				$version = $shell->getVersion();
				$name    = \basename( \reset( $_SERVER['argv'] ) );
				echo <<<EOL
$version
Usage:
  $name [--version] [--help] [files...]
Options:
  --help     -h Display this help message.
  --version  -v Display the PhpTurtle version.
EOL;
				exit( $usageException === null ? 0 : 1 );
			}

			// Handle --version
			if ( $input->getOption( 'version' ) ) {
				echo $shell->getVersion() . PHP_EOL;
				exit( 0 );
			}

			// Pass additional arguments to Shell as 'includes'
			$shell->setIncludes( $input->getArgument( 'include' ) );

			try {
				// And go!
				$shell->run();
			} catch ( \Exception $e ) {
				echo $e->getMessage() . PHP_EOL;

				// @todo this triggers the "exited unexpectedly" logic in the
				// ForkingLoop, so we can't exit(1) after starting the shell...
				// fix this :)

				// exit(1);
			}
		};
	}
}
