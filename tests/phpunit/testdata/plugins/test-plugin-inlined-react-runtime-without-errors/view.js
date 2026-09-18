// Build output whose runtime is externalized via window.ReactJSXRuntime.
( function () {
	var jsx = window.ReactJSXRuntime;
	var element = Symbol.for( "react.element" );
	return jsx ? element : null;
}() );
