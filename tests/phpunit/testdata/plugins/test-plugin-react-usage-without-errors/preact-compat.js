// preact/compat maps the React API onto Preact. It names the React element
// symbol and exports React's internals sentinel, but it creates Preact vnodes
// rather than React elements, and it never touches the React that WordPress
// ships.
( function ( exports ) {
	var REACT_ELEMENT_TYPE = Symbol.for( "react.element" );
	exports.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED = { ReactCurrentOwner: { current: null } };
	exports.isValidElement = function ( value ) {
		return !! value && value.$$typeof === REACT_ELEMENT_TYPE;
	};
	exports.findDOMNode = function ( component ) {
		return component.base;
	};
}( {} ) );
