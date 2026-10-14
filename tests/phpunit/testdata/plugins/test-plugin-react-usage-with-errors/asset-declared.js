// Inlines a pre-19 JSX runtime even though the sibling asset file declares a
// react-jsx-runtime dependency.
( function ( exports ) {
	var k = Symbol.for( "react.element" );
	exports.jsxs = function ( type, props ) {
		return { $$typeof: k, type: type, props: props, _owner: null };
	};
}( {} ) );
