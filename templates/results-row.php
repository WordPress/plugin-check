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
	<td>
		<# if ( data.link ) { #>
			<a href="{{data.link}}" aria-label="<?php _e( 'View file in the plugin file editor.', 'plugin-check' ); ?>" target="_blank"><?php _e( 'View in code editor', 'plugin-check' ); ?></a>
		<# } #>
	</td>
</tr>

