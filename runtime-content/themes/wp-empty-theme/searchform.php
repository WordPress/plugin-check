<?php
/**
 * The searchform.php template.
 *
 * @package wp-empty-theme
 * @link https://developer.wordpress.org/reference/functions/wp_unique_id/
 * @link https://developer.wordpress.org/reference/functions/get_search_form/
 */

$wp_empty_theme_unique_id = wp_unique_id( 'search-form-' );
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label for="<?php echo esc_attr( $wp_empty_theme_unique_id ); ?>">
		<?php esc_html_e( 'Search', 'wp-empty-theme' ); ?>
	</label>
	<input type="search" id="<?php echo esc_attr( $wp_empty_theme_unique_id ); ?>" class="search-field" value="<?php echo get_search_query(); ?>" name="s" />
	<input type="submit" class="search-submit" value="<?php echo esc_attr_x( 'Search', 'submit button', 'wp-empty-theme' ); ?>" />
</form>
