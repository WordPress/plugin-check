// Production build output that inlines the React DOM renderer. The renderer
// defines the removed APIs itself, and those definitions must not be reported
// on top of the inlined package.
( function ( exports ) {
	var k = Symbol.for( "react.element" );
	var randomKey = Math.random().toString( 36 ).slice( 2 );
	var fiberKey = "__reactFiber$" + randomKey;
	var propsKey = "__reactProps$" + randomKey;
	function unmountComponentAtNode( container ) {
		container[ fiberKey ] = null;
		container[ propsKey ] = null;
		return k;
	}
	exports.unmountComponentAtNode = unmountComponentAtNode;
}( {} ) );
