<?php
/**
 * Template for possible false positive results.
 *
 * @package plugin-check
 */

?>
<details class="plugin-check__false-positives">
	<summary>
		<?php esc_html_e( 'Possible false positives', 'plugin-check' ); ?> ({{ data.count }})
	</summary>
	<div id="plugin-check__false-positive-results-{{ data.index }}" class="plugin-check__false-positive-results"></div>
</details>
