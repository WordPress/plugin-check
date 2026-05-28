<tr class="plugin-check__results-row">
	<td data-label="<?php esc_attr_e( 'Line', 'plugin-check' ); ?>">
		{{data.line}}
	</td>
	<td data-label="<?php esc_attr_e( 'Column', 'plugin-check' ); ?>">
		{{data.column}}
	</td>
	<td data-label="<?php esc_attr_e( 'Type', 'plugin-check' ); ?>">
		{{data.type}}
	</td>
	<td data-label="<?php esc_attr_e( 'Code', 'plugin-check' ); ?>">
		{{data.code}}
		<# if ( data.ai_analysis ) { #>
			<br>
			<# if ( data.ai_analysis.is_false_positive ) { #>
				<span class="plugin-check__ai-analysis plugin-check__ai-analysis--false-positive" title="<?php esc_attr_e( 'AI Analysis', 'plugin-check' ); ?>">
					<span class="plugin-check__ai-analysis-icon" aria-hidden="true">✨</span>
					<?php esc_html_e( 'AI: Potential false positive', 'plugin-check' ); ?>
					<# if ( data.ai_analysis.confidence ) { #>
						(<?php esc_html_e( 'Confidence', 'plugin-check' ); ?>: {{Math.round(data.ai_analysis.confidence * 100)}}%)
					<# } #>
				</span>
			<# } else { #>
				<span class="plugin-check__ai-analysis plugin-check__ai-analysis--valid" title="<?php esc_attr_e( 'AI Analysis', 'plugin-check' ); ?>">
					<span class="plugin-check__ai-analysis-icon" aria-hidden="true">✨</span>
					<?php esc_html_e( 'AI: Valid issue', 'plugin-check' ); ?>
					<# if ( data.ai_analysis.confidence ) { #>
						(<?php esc_html_e( 'Confidence', 'plugin-check' ); ?>: {{Math.round(data.ai_analysis.confidence * 100)}}%)
					<# } #>
				</span>
			<# } #>
		<# } #>
	</td>
	<td data-label="<?php esc_attr_e( 'Message', 'plugin-check' ); ?>">
		{{{data.message}}}
		<# if ( data.docs ) { #>
			<br>
			<a href="{{data.docs}}" target="_blank">
				<?php esc_html_e( 'Learn more', 'plugin-check' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'plugin-check' ); ?></span>
				<span aria-hidden="true" class="dashicons dashicons-external"></span>
			</a>
		<# } #>
		<# if ( data.ai_analysis ) { #>
			<# if ( data.ai_analysis.reasoning ) { #>
				<br>
				<em class="plugin-check__ai-reasoning">{{{data.ai_analysis.reasoning}}}</em>
			<# } #>
			<# if ( data.ai_analysis.recommendation ) { #>
				<br>
				<strong><?php esc_html_e( 'Recommendation', 'plugin-check' ); ?>:</strong> <span class="plugin-check__ai-recommendation">{{{data.ai_analysis.recommendation}}}</span>
			<# } #>
		<# } #>
	</td>
	<# if ( data.hasLinks ) { #>
		<td>
			<# if ( data.link ) { #>
				<a href="{{data.link}}" target="_blank">
					<?php esc_html_e( 'View in code editor', 'plugin-check' ); ?>
					<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'plugin-check' ); ?></span>
					<span aria-hidden="true" class="dashicons dashicons-external"></span>
				</a>
			<# } #>
		</td>
	<# } #>
</tr>
