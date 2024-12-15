<?php
/* Ok */
$args = array(
	'type' => 'string',
	'sanitize_callback' => 'sanitize_text_field',
	'default' => NULL,
);
register_setting( 'my_options_group', 'my_option_name', $args );
$args = array(
	'type' => 'string',
	'sanitize_callback' => 'custom_sanitize',
	'default' => NULL,
);
register_setting( 'my_options_group', 'my_option_name', $args );
$args = array(
	'type' => 'string',
	'sanitize_callback' => 'custom_validate',
	'default' => NULL,
);
register_setting( 'my_options_group', 'my_option_name', $args );

register_setting('internal-tags', 'internal_tags_settings', array('type' => 'array', 'sanitize_callback' => array($this, 'sanitize_text_field')));
register_setting('internal-tags', 'internal_tags_settings', array('type' => 'array', 'sanitize_callback' => array($this, 'custom_sanitize')));
register_setting('internal-tags', 'internal_tags_settings', array('type' => 'array', 'sanitize_callback' => array($this, 'custom_validate')));

register_setting('icfcf7_settings_group', 'icfcf7_case_sensitive', 'sanitize_text_field');
register_setting('icfcf7_settings_group', 'icfcf7_case_sensitive', 'custom_sanitize');
register_setting('icfcf7_settings_group', 'icfcf7_case_sensitive', 'custom_validate');

register_setting( 'my_options_group', 'my_option_name', array($this, 'sanitize_text_field') );
register_setting( 'my_options_group', 'my_option_name', array($this, 'custom_sanitize') );
register_setting( 'my_options_group', 'my_option_name', array($this, 'custom_validate') );

/* Warning */
$args = array(
	'type' => 'string',
	'sanitize_callback' => 'custom',
	'default' => NULL,
);
register_setting( 'my_options_group', 'my_option_name', $args );

register_setting('internal-tags', 'internal_tags_settings', array('type' => 'array', 'sanitize_callback' => array($this, 'custom')));

register_setting('icfcf7_settings_group', 'icfcf7_case_sensitive', 'custom');

register_setting( 'my_options_group', 'my_option_name', array($this, 'custom') );

/* Wrong */
register_setting( 'my_options_group', 'my_option_name' );
