// Calls public React APIs that were removed in React 19.
( function () {
	var container = document.getElementById( 'root' );
	ReactDOM.render( window.createApp(), container );
	return findDOMNode( container );
}() );
