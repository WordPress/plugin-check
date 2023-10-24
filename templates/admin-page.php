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
					<label class="title" for="plugin-check__plugins-dropdown">
						<?php esc_html_e( 'Check the Plugin', 'plugin-check' ); ?>
					</label>
				</h2>

				<select id="plugin-check__plugins-dropdown" name="plugin_check_plugins">
					<option value=""><?php esc_html_e( 'Select Plugin', 'plugin-check' ); ?></option>
					<?php foreach ( $available_plugins as $plugin_basename => $available_plugin ) { ?>
						<option value="<?php echo esc_attr( $plugin_basename ); ?>"<?php selected( $selected_plugin_basename, $plugin_basename ); ?>>
							<?php echo esc_html( $available_plugin['Name'] ); ?>
						</option>
					<?php } ?>
				</select>

				<input type="submit" value="<?php esc_attr_e( 'Check it!', 'plugin-check' ); ?>" id="plugin-check__submit" class="button button-primary" />
				<span id="plugin-check__spinner" class="spinner" style="float: none;"></span>
				<h4><?php esc_attr_e( 'Categories', 'plugin-check' ); ?></h4>
				<?php
				if ( ! empty( $categories ) ) {
				?>
				<table>
				<?php
				foreach ( $categories as $category ) { ?>
					<tr>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><?php echo esc_html( $category ); ?></legend>
								<label for="<?php echo esc_attr( $category ); ?>">
									<?php
										$checked = false;
										if ( $user_settings ) {
											$selected_categories = explode( '__', $user_settings );
											if ( in_array( $category, $selected_categories, true ) ) {
												$checked = true;
											}
										} else {
											$checked = true;
										}
									?>
									<input type="checkbox" id="<?php echo esc_attr( $category ); ?>" name="categories" value="<?php echo esc_attr( $category ); ?>"<?php echo ( $checked ? ' checked="checked"' : '' ); ?> />
									<?php echo esc_html( ucfirst( str_replace( '_', ' ', $category ) ) ); ?>
								</label>
							</fieldset>
						</td>
					</tr>
				<?php } ?>
				</table>
				<?php } ?>
			</form>

		<?php } else { ?>

			<h2><?php esc_html_e( 'No plugins available.', 'plugin-check' ); ?></h2>

		<?php } ?>
	</div>

	<div id="plugin-check__results"></div>

</div>
