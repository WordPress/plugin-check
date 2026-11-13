// Build output that inlines the library and the renderer and then publishes
// both under the globals WordPress uses. Writing a global is not externalizing:
// the build can only publish the copy it carries, and doing so replaces the
// copy WordPress loaded for every script that runs after it.
( function ( exports ) {
	var k = Symbol.for( "react.element" );
	var internals = { ReactCurrentOwner: { current: null } };
	exports.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED = internals;
	exports.createElement = function ( type ) {
		return { $$typeof: k, type: type, _owner: internals.ReactCurrentOwner.current };
	};
	exports.render = function ( element, container ) {
		container.__reactFiber$abc = element;
	};
	window.React = exports;
	window.ReactDOM = exports;
}( {} ) );
