// react-is only names the React symbols so that other libraries can identify
// element types. Bundling it inlines no React code at all.
( function ( exports ) {
	var element = Symbol.for( "react.element" );
	var portal = Symbol.for( "react.portal" );
	var fragment = Symbol.for( "react.fragment" );
	exports.isElement = function ( object ) {
		return "object" === typeof object && null !== object && object.$$typeof === element;
	};
	exports.typeOf = function ( object ) {
		switch ( object && object.$$typeof ) {
			case element:
				return element;
			case portal:
				return portal;
			default:
				return fragment;
		}
	};
}( {} ) );
