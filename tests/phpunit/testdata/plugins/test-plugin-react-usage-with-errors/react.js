// Production build output that inlines the React library itself.
( function ( exports ) {
	var k = Symbol.for( "react.element" );
	function Component( props ) {
		this.props = props;
	}
	function PureComponent( props ) {
		this.props = props;
	}
	PureComponent.prototype.isPureReactComponent = true;
	exports.Component = Component;
	exports.PureComponent = PureComponent;
	var internals = { ReactCurrentOwner: { current: null } };
	exports.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED = internals;
	exports.createElement = function ( type ) {
		return { $$typeof: k, type: type, _owner: internals.ReactCurrentOwner.current };
	};
}( {} ) );
