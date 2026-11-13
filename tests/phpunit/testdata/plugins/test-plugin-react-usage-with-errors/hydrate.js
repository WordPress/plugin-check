// Server-rendered markup rehydrated through the removed legacy entry points.
( function () {
	var container = document.getElementById( 'app' );
	ReactDOM.hydrate( window.createApp(), container );
	window.addEventListener( 'unload', function () {
		unmountComponentAtNode( container );
	} );
}() );
