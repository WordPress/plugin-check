// Build output that inlines the React library while externalizing the
// renderer. The reference to the renderer global must not be taken for a
// reference to the library global.
( function ( exports ) {
	var k = Symbol.for( "react.element" );
	var internals = { ReactCurrentOwner: { current: null } };
	exports.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED = internals;
	exports.createElement = function ( type ) {
		return { $$typeof: k, type: type, _owner: internals.ReactCurrentOwner.current };
	};
	window.ReactDOM.createRoot( document.body ).render( exports.createElement( "div" ) );
}( {} ) );
