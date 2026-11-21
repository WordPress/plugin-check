jQuery( function ( $ ) {
	$( '.button' ).bind( 'click', handler ).hover( over, out );
	$( document ).delegate( '.item', 'click', handler );
	var bound = $.proxy( handler, this );
} );
