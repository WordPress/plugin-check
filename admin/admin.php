<?php
namespace WordPressdotorg\Plugin_Check\Admin;
use	function WordPressdotorg\Plugin_Check\run_all_checks;

add_action( 'admin_menu', function() {
	add_submenu_page(
		'tools.php',
		__( 'Plugin Check', 'plugin-check' ),
		__( 'Plugin Check', 'plugin-check' ),
		'manage_options',
		'plugin-check',
		__NAMESPACE__ . '\render_page'
	);
} );

function render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// @todo This should be eventually moved to a separate file.
	echo <<<HTML
		<style>
		.wp-plugin-check-code {
			white-space:pre-wrap;
			word-wrap:break-word;
		}
		.wp-plugin-check-code code {
			line-height:1.7em
		}
		</style>
	HTML;

	echo '<div class="wrap">';
	echo '<h1>' . esc_html__( 'Plugin Check', 'plugin-check' ) . '</h1>';
	echo '<p>' . esc_html__( 'Select a plugin to run the checks against.', 'plugin-check' ) . '</p>';
	echo '<form method="get" action="' . esc_url( admin_url( 'tools.php' ) ) . '">';
	echo '<input type="hidden" name="page" value="plugin-check" />';
	echo '<input type="hidden" name="action" value="check" />';
	wp_nonce_field( 'plugin-check' );
	echo '<label for="plugin">' . esc_html__( 'Plugin', 'plugin-check' ) . '</label>';
	echo '<select name="plugin" id="plugin">';
	$selected_plugin = false;
	foreach ( get_plugins() as $plugin_file => $plugin_data ) {
		// Only list plugins that are in their own directories.
		if ( ! str_contains( $plugin_file, '/' ) ) {
			continue;
		}

		$plugin_dir = dirname( $plugin_file );
		$selected   = selected( $plugin_dir, $_REQUEST['plugin'] ?? '', false );
		if ( $selected ) {
			$selected_plugin = $plugin_data;
		}

		echo '<option value="' . esc_attr( $plugin_dir ) . '"' . $selected . '>' . esc_html( $plugin_data['Name'] ) . '</option>';
	}
	echo '</select>';
	echo '<input type="submit" class="button button-primary" value="' . esc_attr__( 'Check', 'plugin-check' ) . '" />';

	echo '</form>';
	echo '</div>';

	if ( ! empty( $_REQUEST['action'] ) && 'check' === $_REQUEST['action'] ) {
		check_admin_referer( 'plugin-check' );

		$plugin = wp_unslash( $_REQUEST['plugin'] );
		$path   = path_join( WP_PLUGIN_DIR, $plugin );
		$result = run_all_checks( $path );

		echo '<h2>' . sprintf( esc_html__( 'Results for %s', 'plugin-check' ), esc_html( $selected_plugin['Name'] ?? $plugin ) ) . '</h2>';

		if ( true === $result ) {
			echo '<div class="notice inline notice-success"><p>' . esc_html__( 'No issues found.', 'plugin-check' ) . '</p></div>';
		} else {
			echo '<p>' . esc_html__( 'The following issues were found:', 'plugin-check' ) . '</p>';
			foreach ( $result as $error ) {
				printf(
					'<div class="notice inline notice-%s" data-code="%s"><p>%s</p></div>',
					esc_attr( $error->error_class ),
					esc_attr( $error->get_error_code() ),
					$error->get_error_message()
				);
			}
		}
	}
}

/**
 * Includes JS to jump to a specific line in the code editor.
 *
 * @since 0.2.1
 *
 * @param string $hook_suffix Which admin page we're on.
 *
 * @return void
 */
function jump_to_line_code_editor( $hook_suffix ) {
	$line = (int) ( $_GET['line'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $line ) {
		return;
	}

	if ( 'plugin-editor.php' !== $hook_suffix ) {
		return;
	}

	wp_add_inline_script(
		'wp-theme-plugin-editor',
		sprintf(
			'
				(
					( originalInitCodeEditor ) => {
						wp.themePluginEditor.initCodeEditor = function() {
							originalInitCodeEditor.apply( this, arguments );
							this.instance.codemirror.doc.setCursor( %d - 1 );
						};
					}
				)( wp.themePluginEditor.initCodeEditor );
			',
			wp_json_encode( $line )
		)
	);
}

/**
 * Jump to the requested line when opening the file editor.
 */
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\jump_to_line_code_editor' );
