<script type="text/template" id="tmpl-plugin-check-results-table">
	<h4><?php esc_html_e( 'FILE:', 'plugin-check' ); ?> {{ data.file }}</h4>
	<table id="plugin-check__results-table-{{data.index}}" class="widefat plugin-check__results-table">
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
			</tr>
		</thead>
		<tbody id="plugin-check__results-body-{{data.index}}"></tbody>
	</table>
	<br>
</script>
