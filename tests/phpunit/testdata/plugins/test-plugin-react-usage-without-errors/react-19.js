// A React 19 build uses a different element marker, so an inlined copy of it
// is not the breakage this check looks for.
( function ( exports ) {
	var k = Symbol.for( "react.transitional.element" );
	exports.jsxs = function ( type, props ) {
		return { $$typeof: k, type: type, props: props };
	};
}( {} ) );
