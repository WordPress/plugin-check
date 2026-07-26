<div class="plugin-check__file-section plugin-check__file-section--collapsed" id="plugin-check__file-section-{{data.index}}">
	<button type="button" class="plugin-check__file-section-header" aria-expanded="false" aria-controls="plugin-check__file-section-content-{{data.index}}">
		<span class="plugin-check__file-section-title">
			<span class="plugin-check__file-section-icon dashicons dashicons-media-document" aria-hidden="true"></span>
			<?php esc_html_e( 'FILE:', 'plugin-check' ); ?> {{ data.file }}<#
			if ( data.errorLabel || data.warningLabel ) { #><span class="plugin-check__file-section-counts"><#
				if ( data.errorLabel ) { #><span class="plugin-check__file-section-count plugin-check__file-section-count--error">{{ data.errorLabel }}</span><# }
				if ( data.warningLabel ) { #><span class="plugin-check__file-section-count plugin-check__file-section-count--warning">{{ data.warningLabel }}</span><# } #></span><# } #>
		</span>
		<span class="plugin-check__file-section-chevron dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
	</button>
	<div class="plugin-check__file-section-content" id="plugin-check__file-section-content-{{data.index}}">
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

