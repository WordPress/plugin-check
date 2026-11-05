<?php

// Bad: add_option() with no $autoload.
add_option( 'no_autoload_a' ); // Warning.

// Bad: add_option() with only $value set.
add_option( 'no_autoload_b', 'value' ); // Warning.

// Bad: add_option() with only $deprecated set.
add_option( 'no_autoload_c', 'value', '' ); // Warning.

// Bad: update_option() with no $autoload.
update_option( 'no_autoload_d' ); // Warning.

// Bad: update_option() with only $value set.
update_option( 'no_autoload_e', 'value' ); // Warning.

// Good: add_option() with explicit autoload = false.
add_option( 'autoload_false_a', 'value', '', false );

// Good: add_option() with explicit autoload = true.
add_option( 'autoload_true_a', 'value', '', true );

// Good: update_option() with explicit autoload = false.
update_option( 'autoload_false_b', 'value', false );

// Good: update_option() with explicit autoload = true.
update_option( 'autoload_true_b', 'value', true );
