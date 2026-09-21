// Uses the React 19 entry points. The render call here is a method on a root,
// not the removed ReactDOM.render.
( function () {
	var container = document.getElementById( 'root' );
	var root = window.ReactDOM.createRoot( container );
	root.render( window.createApp() );
	window.addEventListener( 'unload', function () {
		root.unmount();
	} );
}() );
