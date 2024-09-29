<tr class="plugin-check__results-row">
	<td data-label="<?php esc_attr_e( 'Line', 'plugin-check' ); ?>">
		{{data.line}}
	</td>
	<td data-label="<?php esc_attr_e( 'Column', 'plugin-check' ); ?>">
		{{data.column}}
	</td>
	<td data-label="<?php esc_attr_e( 'Type', 'plugin-check' ); ?>">
		{{data.type}}
	</td>
	<td data-label="<?php esc_attr_e( 'Code', 'plugin-check' ); ?>">
		{{data.code}}
	</td>
	<td data-label="<?php esc_attr_e( 'Message', 'plugin-check' ); ?>">
		{{{data.message}}}
		<# if ( data.docs ) { #>
			<br>
			<a href="{{data.docs}}" target="_blank">
				<?php esc_html_e( 'Learn more', 'plugin-check' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'plugin-check' ); ?></span>
				<span aria-hidden="true" class="dashicons dashicons-external"></span>
			</a>
		<# } #>
	</td>
	<# if ( data.hasLinks ) { #>
		<td>
			<# if ( data.link ) { #>
				<a href="{{data.link}}" target="_blank">
					<?php esc_html_e( 'View in code editor', 'plugin-check' ); ?>
					<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'plugin-check' ); ?></span>
					<span aria-hidden="true" class="dashicons dashicons-external"></span>
				</a>
			<# } #>
		</td>
	<# } #>
</tr>

