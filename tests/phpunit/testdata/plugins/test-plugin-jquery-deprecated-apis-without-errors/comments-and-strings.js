// Used to call $.trim( value ) and jQuery.parseJSON( text ).
/* jQuery.isArray( items ) was replaced by Array.isArray(). */
var message = 'Do not call $.parseJSON( text ) or jQuery.type( value ).';
var template = `jQuery.now() is gone, so is ${ 'jQuery.unique( nodes )' }`;
var pattern = /jQuery\.isFunction\( x \)/;
