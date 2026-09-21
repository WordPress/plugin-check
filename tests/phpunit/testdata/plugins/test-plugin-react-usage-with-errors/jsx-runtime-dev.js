// Development build output that inlines the pre-React 19 JSX runtime.
( function ( exports ) {
	var k = Symbol.for( "react.element" );
	function jsxWithValidation( type, props ) {
		if ( ! type ) {
			console.error( "React.jsx: type is invalid. See https://reactjs.org/link/invalid-element-type for more information." );
		}
		return { $$typeof: k, type: type, props: props };
	}
	exports.jsx = jsxWithValidation;
	exports.jsxs = jsxWithValidation;
}( {} ) );
