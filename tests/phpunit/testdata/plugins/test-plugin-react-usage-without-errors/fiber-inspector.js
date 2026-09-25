// A tool that inspects the React tree on the page. It names the element symbol
// and the key the renderer hangs a fiber off every DOM node it owns, but it
// only reads them: the renderer it inspects is the copy WordPress loaded, which
// this build externalizes and reaches through the global.
( function ( exports ) {
	var elementType = Symbol.for( "react.element" );

	exports.root = window.ReactDOM.createRoot( document.body );

	exports.fiberOf = function ( node ) {
		for ( var key in node ) {
			if ( 0 === key.indexOf( "__reactFiber$" ) ) {
				return node[ key ];
			}
		}
	};

	exports.isElement = function ( value ) {
		return !! value && value.$$typeof === elementType;
	};
}( {} ) );
