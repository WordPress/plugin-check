<h4><?php esc_html_e( 'FILE:', 'plugin-check' ); ?> {{ data.file }}</h4>
<table id="plugin-check__results-table-{{data.index}}" class="widefat striped plugin-check__results-table">
	<thead>
		<tr>
			<td>
				<?php esc_html_e( 'Line', 'plugin-check' ); ?>
			</td>
			<td>
				<?php esc_html_e( 'Column', 'plugin-check' ); ?>
			</td>
			<td>
				<?php esc_html_e( 'Type', 'plugin-check' ); ?>
			</td>
			<td>
				<?php esc_html_e( 'Code', 'plugin-check' ); ?>
			</td>
			<td>
				<?php esc_html_e( 'Message', 'plugin-check' ); ?>
			</td>
			<# if ( data.hasLinks ) { #>
				<td>
					<?php esc_html_e( 'Edit Link', 'plugin-check' ); ?>
				</td>
			<# } #>
		</tr>
	</thead>
	<tbody id="plugin-check__results-body-{{data.index}}"></tbody>
</table>
<br>
