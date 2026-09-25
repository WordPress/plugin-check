// Build output whose runtime is externalized to the copy shipped with
// WordPress. The jsx binding is a local alias of the external runtime rather
// than an export of an inlined one.
( function () {
	var jsx = window.ReactJSXRuntime.jsx;
	var element = Symbol.for( "react.element" );
	return jsx( "div", { children: element } );
}() );
