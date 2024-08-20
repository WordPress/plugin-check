<tr class="plugin-check__results-row">
	<td>
		{{data.line}}
	</td>
	<td>
		{{data.column}}
	</td>
	<td>
		{{data.type}}
	</td>
	<td>
		{{data.code}}
	</td>
	<td>
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

