<h4 id="plugin-check__results-heading-{{data.index}}" class="plugin-check__results-heading" data-index="{{data.index}}">
	<?php esc_html_e( 'FILE:', 'plugin-check' ); ?> {{ data.file }} 
	<button class="collapse-btn" data-state="collapse"><?php esc_html_e( 'Collapse', 'plugin-check' ); ?></button>
</h4>
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
