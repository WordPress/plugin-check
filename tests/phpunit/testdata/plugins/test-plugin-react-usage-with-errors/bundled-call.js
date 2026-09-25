// Build output in which the imported functions are called through a sequence
// expression, which is how a bundler drops the `this` the call would otherwise
// be made with. A closing parenthesis sits between each name and its call.
( function ( r ) {
	var container = document.getElementById( 'root' );
	( 0, r.unstable_renderSubtreeIntoContainer )( window.parent, window.createApp(), container );
	return ( 0, r.findDOMNode )( container );
}( window.ReactDOM ) );
