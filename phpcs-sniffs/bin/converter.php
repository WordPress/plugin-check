<?php
/**
 * Prepares core translations
 *
 * @package plugin-check
 */

/**
 * Generates a PHP file containing exported message keys from a source file.
 *
 * @since 1.0.0
 *
 * @param string $source_file_path Path to the source file to process.
 * @param string $output_file_path Path where the generated file will be saved.
 * @param int    $buffer_size      Number of keys to buffer before writing to file (default: 1000).
 */
function pcp_generate_vars_file( string $source_file_path, string $output_file_path, int $buffer_size = 1000 ) {
	$source_data = include $source_file_path;

	$file_handle = fopen( $output_file_path, 'w' );
	fwrite( $file_handle, "<?php\nreturn [\n" );

	$current_buffer = '';
	$keys_processed = 0;

	foreach ( array_keys( $source_data['messages'] ) as $key ) {
		$current_buffer .= '    ' . var_export( $key, true ) . ",\n";

		++$keys_processed;

		if ( $keys_processed % $buffer_size === 0 ) {
			fwrite( $file_handle, $current_buffer );
			$current_buffer = '';
		}
	}

	// Write remaining buffer.
	if ( ! empty( $current_buffer ) ) {
		fwrite( $file_handle, $current_buffer );
	}

	fwrite( $file_handle, "];\n" );
	fclose( $file_handle );
}

pcp_generate_vars_file( 'data/i18n/core.php', 'PluginCheck/Vars/i18n-core.php' );
pcp_generate_vars_file( 'data/i18n/admin.php', 'PluginCheck/Vars/i18n-admin.php' );
