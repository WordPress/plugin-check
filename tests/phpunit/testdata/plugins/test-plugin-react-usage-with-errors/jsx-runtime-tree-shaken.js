// Build output that externalizes react and react-dom but still inlines the
// pre-19 JSX runtime. Only the jsx export is used, so the bundler tree-shook
// jsxs away and the runtime must be recognized from jsx alone.
( function ( modules ) {
	var React = ( modules[ 1609 ] = window.React );
	var k = Symbol.for( "react.element" );
	var owner = React.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED.ReactCurrentOwner;
	modules[ 1020 ] = {};
	modules[ 1020 ].jsx = function ( type, props ) {
		return { $$typeof: k, type: type, props: props, _owner: owner.current };
	};
}( {} ) );
