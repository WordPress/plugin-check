// Inlines a pre-19 runtime even though the sibling asset file declares react-jsx-runtime.
( function () {
	var element = Symbol.for( "react.element" );
	return element;
}() );
