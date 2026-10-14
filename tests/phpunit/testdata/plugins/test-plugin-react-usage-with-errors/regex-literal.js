// The quote inside the regular expression must not be read as the start of a
// string literal, which would swallow the call that follows it.
( function () {
	var quoteRe = /"/g;
	var segmentRe = /[^/]+/g;
	findDOMNode( document.getElementById( 'root' ) );
	return quoteRe.source + segmentRe.source + "done";
}() );
