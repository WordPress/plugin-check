// react-is, which arrives with prop-types, names the React element symbol, and
// the syntax highlighter bundled alongside it defines a jsx language. Neither
// inlines any React code, so the file creates no elements of its own.
( function ( exports ) {
	var REACT_ELEMENT_TYPE = Symbol.for( "react.element" );
	exports.isElement = function ( value ) {
		return !! value && value.$$typeof === REACT_ELEMENT_TYPE;
	};
	Prism.languages.jsx = { comment: /\/\/.*/ };
}( {} ) );
