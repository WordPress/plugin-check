// This build references removed React APIs only in comments and strings.
( function () {
	// findDOMNode and unmountComponentAtNode were removed in React 19.
	var note = 'Avoid ReactCurrentOwner; it no longer exists.';
	return note;
}() );
