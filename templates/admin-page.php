<?php
/**
 * Template for the Admin page.
 *
 * @package plugin-check
 */

?>

<div class="wrap">

	<h1><?php esc_html_e( 'Plugin Check', 'plugin-check' ); ?></h1>

	<div class="plugin-check-content">

		<?php if ( ! empty( $available_plugins ) ) { ?>

			<form>
				<h2>
					<label class="title" for="plugin-check__plugins">
						<?php esc_html_e( 'Check the Plugin', 'plugin-check' ); ?>
					</label>
				</h2>

				<select id="plugin-check__plugins" name="plugin_check_plugins">
					<option><?php esc_html_e( 'Select Plugin', 'plugin-check' ); ?></option>
					<?php foreach ( $available_plugins as $plugin_basename => $available_plugin ) { ?>
						<option value="<?php echo esc_attr( $plugin_basename ); ?>"<?php selected( $selected_plugin_basename, $plugin_basename ); ?>>
							<?php echo esc_html( $available_plugin['Name'] ); ?>
						</option>
					<?php } ?>
				</select>

				<input type="submit" value="<?php esc_attr_e( 'Check it!', 'plugin-check' ); ?>" />
			</form>

		<?php } else { ?>

			<h2><?php esc_html_e( 'No plugins available.', 'plugin-check' ); ?></h2>

		<?php } ?>
	</div>

</div>
