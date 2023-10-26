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
		{{data.message}}
	</td>
	<# if ( data.hasLinks ) { #>
		<td>
			<a href="{{data.link}}"<?php echo ( ! has_filter( 'wp_plugin_check_validation_error_source_file_editor_url_template' ) ) ? ' target="_blank"' : '' ?>>
				<?php esc_html_e( 'View in code editor', 'plugin-check' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'plugin-check' ); ?></span>
			</a>
		</td>
	<# } #>
</tr>

