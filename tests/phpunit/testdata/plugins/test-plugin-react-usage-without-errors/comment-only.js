// This build references removed React APIs only in comments and strings.
( function () {
	// findDOMNode( node ) and unmountComponentAtNode( node ) were removed in React 19.
	var note = 'Do not call ReactDOM.render( app, el ) any more.';
	return note;
}() );
