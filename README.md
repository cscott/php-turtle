# php-turtle

`php-turtle` is an implementation of
[TurtleScript](https://github.com/cscott/turtlescript) in
PHP.  TurtleScript is a syntactic
(but not semantic) subset of JavaScript, originally created for
the One Laptop per Child project.

## Build, Install, and Run

To run a TurtleScript
[REPL](http://en.wikipedia.org/wiki/Read%E2%80%93eval%E2%80%93print_loop):
```
$ php main.php
> 2+3
5
> var fact = function(x) { return (x<2) ? x : (x * fact(x-1)) ; };
undefined
> fact(42)
1405006117752880268066222604204040608686664282428002
>
```
Use Control-D (or Control-C) to exit the REPL.  You can also evaluate entire
TurtleScript scripts by passing the name on the command line:
```
$ php main.php foo.js
```

## Testing
There are quite a few unit tests built into `php-turtle` (although
never enough!).  You can build and run them with `composer test`.  See
`tests/InterpreterTest.php` for a set of script-based tests, which you
could manually reproduce in the REPL (if you were so inclined).

## Design
`php-turtle` is a simple interpreter for the bytecode emitted by
`bcompile.js` from the TurtleScript project.  It is heavily based on
`binterp.js` from that project, which is a TurtleScript interpreter written
in TurtleScript.  The `src/Startup.php` file contains the bytecode for the
TurtleScript standard library implementation (from `binterp.js`) as
well as the tokenizer, parser, and bytecode compiler itself (emitted
by `write-php-bytecode.js` in the TurtleScript project).  This allows
the `php-turtle` REPL to parse and compile the expressions you type
at it into bytecode modules which it can interpret.

Currently bytecode is interpreted; a logical next step would be to
compile directly to PHP code and eliminate the overhead of the
interpretation loop.  The goal of the JavaScript object model implementation
is to try to map JavaScript operations onto PHP operations as nearly
as possible, to keep JavaScript execution speed comparable to PHP
execution.  For example, property accesses in JavaScript map directly
to property accesses in PHP.  It wouldn't be too hard to allow the
JavaScript code to directly interrogate a 'native' PHP object.
The vice-versa case is interesting as well: PHP can very easily
access properties of native JavaScript objects.  Cross-realm
function invocation is a *little* harder, but not much.

## Future performance improvements

The representation of arrays at present leaves much to be
desired -- they are just objects with keys which are numeric strings.
These should be replaced by "real" PHP arrays, although that complicates
some of the type dispatch code.

Strings are generally represented as UTF-16, which is "native" for
JavaScript, although property names and literals are UTF-8.  This
seems to strike a good balance between fluent property access in PHP
using mostly-ASCII strings, and efficient string manipulation in
JavaScript.  It may however be interesting to allow strings to switch
representations on the fly.

Compiling the bytecode could make use of type information, perhaps
propagated from variable initialization and the types of arguments
when a function is invokes, in order to reduce the amount of dynamic
type dispatch.  A small number of specialized versions of any given
function could be compiled, falling back to the present bytecode
interpreter if the function turns out to be polyvariant.

## Future research

I would like to explore multilingual JavaScript using this platform.
There are some thoughts in
[Wikimedia phabricator](https://phabricator.wikimedia.org/T230665);
[Babylscript](http://www.babylscript.com/) also appears very interesting.

## License

TurtleScript and `php-turtle` are (c) 2020 C. Scott Ananian and
licensed under the terms of the GNU GPL v2.
