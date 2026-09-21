// Production build output that inlines the pre-React 19 JSX runtime. Like the
// real runtime it reads React's internals export without assigning it, so only
// the JSX runtime must be reported for this file.
( function ( exports, React ) {
	var k = Symbol.for( "react.element" ), l = Symbol.for( "react.fragment" );
	var n = React.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED.ReactCurrentOwner;
	function q( c, a ) {
		return { $$typeof: k, type: c, key: null, ref: null, props: a, _owner: n.current };
	}
	exports.Fragment = l;
	exports.jsx = q;
	exports.jsxs = q;
}( {}, {} ) );
