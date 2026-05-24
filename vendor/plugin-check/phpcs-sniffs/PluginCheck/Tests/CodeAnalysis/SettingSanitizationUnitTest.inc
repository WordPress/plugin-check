<?php
register_setting( 'my_options_group', 'my_option_name' ); // Error.
register_setting( 'my_options_group', 'my_option_name', 10 ); // Error.
register_setting( 'my_options_group', 'my_option_name', false ); // Error.
register_setting( 'my_options_group', 'my_option_name', 'absint' ); // Good.
register_setting('my_options_group','my_option_name', 'sanitize_text_field' ); // Good.

class TestClass {
	public function register_setting() {
	}
}

$obj = new TestClass();
$obj->register_setting(); // Good.
