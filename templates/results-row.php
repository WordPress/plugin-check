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
			<# if ( data.link ) { #>
				<a href="{{data.link}}" target="_blank">
					<?php esc_html_e( 'View in code editor', 'plugin-check' ); ?>
					<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'plugin-check' ); ?></span>
				</a>
			<# } #>
		</td>
	<# } #>
</tr>

