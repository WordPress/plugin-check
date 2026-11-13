<h4><?php esc_html_e( 'FILE:', 'plugin-check' ); ?> {{ data.file }}</h4>
<table id="plugin-check__results-table-{{data.index}}" class="widefat striped plugin-check__results-table">
	<thead>
		<tr>
			<th scope="col">
				<?php esc_html_e( 'Line', 'plugin-check' ); ?>
			</th>
			<th scope="col">
				<?php esc_html_e( 'Column', 'plugin-check' ); ?>
			</th>
			<th scope="col">
				<?php esc_html_e( 'Type', 'plugin-check' ); ?>
			</th>
			<th scope="col">
				<?php esc_html_e( 'Code', 'plugin-check' ); ?>
			</th>
			<th scope="col">
				<?php esc_html_e( 'Message', 'plugin-check' ); ?>
			</th>
			<# if ( data.hasLinks ) { #>
				<th scope="col">
					<?php esc_html_e( 'Edit Link', 'plugin-check' ); ?>
				</th>
			<# } #>
		</tr>
	</thead>
	<tbody id="plugin-check__results-body-{{data.index}}"></tbody>
</table>
<br>
