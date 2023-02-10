<?php
/**
 * Template for the Admin page.
 *
 * @package plugin-check
 */

if ( empty( $available_plugins ) ) {
	return;
}
?>

<div class="wrap">

	<h1><?php esc_html_e( 'Plugin Check', 'plugin-check' ); ?></h1>

	<div class="card">

		<form>
			<h2>
				<label class="title" for="plugin-check__plugins">
					<?php esc_html_e( 'Check the Plugin', 'plugin-check' ); ?>
				</label>
			</h2>

			<select id="plugin-check__plugins">
				<option><?php esc_html_e( 'Select Plugin', 'plugin-check' ); ?></option>
				<?php foreach ( $available_plugins as $plugin_basename => $available_plugin ) { ?>
					<option value="<?php echo esc_attr( $plugin_basename ); ?>">
						<?php echo esc_html( $available_plugin['Name'] ); ?>
					</option>
				<?php } ?>
			</select>

			<input type="submit" value="<?php esc_attr_e( 'Check it!', 'plugin-check' ); ?>" />
		</form>

	</div>

</div>
