// Production build output of React 17, which hoists Symbol.for into a local
// variable before creating the symbols, so the element marker never appears as
// a literal Symbol.for( "react.element" ) call.
( function ( exports ) {
	var k = 60103;
	if ( "function" === typeof Symbol && Symbol.for ) {
		var w = Symbol.for;
		k = w( "react.element" );
	}
	exports.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED = { ReactCurrentOwner: { current: null } };
	exports.createElement = function ( type ) {
		return { $$typeof: k, type: type };
	};
}( {} ) );
