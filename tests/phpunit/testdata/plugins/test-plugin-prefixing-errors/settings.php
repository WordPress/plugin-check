<?php
register_setting( 'my_options_group', 'my_option_full_name', 'sanitize_text_field' );
register_setting( 'my_options_group', 'my_option_email', 'sanitize_email' );
register_setting( 'my_options_group', 'my_option_address', 'sanitize_text_field' );
register_setting( 'my_options_group', 'my_option_phone', 'sanitize_text_field' );
