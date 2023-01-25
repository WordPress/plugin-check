<?php
/**
 * The template for displaying the footer
 *
 * @package wp-empty-theme
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 */

?>
			</main><!-- #main -->
		</div><!-- #primary -->
	</div><!-- #content -->

	<footer id="colophon" class="site-footer" role="contentinfo">
		<div class="site-info">
			<div class="site-name">
				<?php
				if ( get_bloginfo( 'name' ) ) {
					if ( is_front_page() && ! is_paged() ) {
						bloginfo( 'name' );
					} else {
						?>
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
						<?php
					}
				}
				?>
			</div><!-- .site-name -->
			<div class="powered-by">
				<?php
				printf(
					/* translators: %s: WordPress. */
					esc_html__( 'Proudly powered by %s.', 'wp-empty-theme' ),
					'<a href="' . esc_url( __( 'https://wordpress.org/', 'wp-empty-theme' ) ) . '">WordPress</a>'
				);
				?>
			</div><!-- .powered-by -->
		</div><!-- .site-info -->
	</footer><!-- #colophon -->

</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
