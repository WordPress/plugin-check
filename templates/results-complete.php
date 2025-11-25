<div class="notice notice-{{ data.type }}">
	<p><?php esc_html_e( 'Checks complete.', 'plugin-check' ); ?> {{ data.message }}</p>
</div>

<div class="pcp-download-report" style="margin:12px 0;">
	<button type="button" class="button button-primary" id="pcp-download-report-button">
		<?php esc_html_e( 'Download Report', 'plugin-check' ); ?>
	</button>
	<div id="pcp-download-menu" class="hide-if-js" style="display:none; margin-top:6px;">
		<a id="pcp-export-csv" class="button" href="#"><?php esc_html_e( 'Download as CSV', 'plugin-check' ); ?></a>
		<a id="pcp-export-pdf" class="button" href="#"><?php esc_html_e( 'Download as PDF', 'plugin-check' ); ?></a>
	</div>
</div>