<?php

parse_str( $param_one, $param_two ); // Good.
parse_str( $param_one ); // Bad.

$str = "first=value&arr[]=foo+bar&arr[]=baz";
parse_str($str, $output); // Good.
parse_str($str); // Bad.

parse_str("My Value=Something", $output); // Good.
parse_str("My Value=Something"); // Bad.
