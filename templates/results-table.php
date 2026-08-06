<div class="plugin-check__file-results">
	<div class="plugin-check__file-results-header">
		<button
			type="button"
			class="plugin-check__file-results-toggle"
			aria-expanded="true"
			aria-controls="plugin-check__results-panel-{{data.index}}"
		>
			<span class="plugin-check__file-results-leading">
				<span class="plugin-check__file-results-file-icon dashicons dashicons-media-default" aria-hidden="true"></span>
				<span class="plugin-check__file-results-label">
					<?php esc_html_e( 'FILE:', 'plugin-check' ); ?> {{ data.file }}
				</span>
			</span>
			<span class="plugin-check__file-results-toggle-icon dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
		</button>
	</div>
	<div id="plugin-check__results-panel-{{data.index}}" class="plugin-check__file-results-panel open">
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
	</div>
</div>
