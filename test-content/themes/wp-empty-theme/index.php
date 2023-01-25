<?php
/**
 * The main template file
 *
 * @package wp-empty-theme
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 */

get_header();
?>

<?php
if ( is_home() && ! is_front_page() && ! empty( single_post_title( '', false ) ) ) {
	?>
	<header class="page-header alignwide">
		<h1 class="page-title"><?php single_post_title(); ?></h1>
	</header><!-- .page-header -->
	<?php
} elseif ( is_search() ) {
	?>
	<header class="page-header alignwide">
		<h1 class="page-title">
			<?php
			printf(
				/* translators: %s: Search term. */
				esc_html__( 'Results for "%s"', 'wp-empty-theme' ),
				esc_html( get_search_query() )
			);
			?>
		</h1>
	</header><!-- .page-header -->
	<?php
} elseif ( is_archive() ) {
	?>
	<header class="page-header alignwide">
		<?php the_archive_title( '<h1 class="page-title">', '</h1>' ); ?>
		<?php the_archive_description( '<p class="page-description">', '</p>' ); ?>
	</header><!-- .page-header -->
	<?php
} elseif ( is_404() ) {
	?>
	<header class="page-header alignwide">
		<h1 class="page-title"><?php esc_html_e( 'Nothing here', 'wp-empty-theme' ); ?></h1>
	</header><!-- .page-header -->
	<?php
}
?>

<?php
if ( have_posts() ) {

	// Load posts loop.
	while ( have_posts() ) {
		the_post();

		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

			<header class="entry-header alignwide">
				<?php
				if ( is_singular() ) {
					the_title( '<h1 class="entry-title">', '</h1>' );
				} else {
					the_title( sprintf( '<h2 class="entry-title default-max-width"><a href="%s">', esc_url( get_permalink() ) ), '</a></h2>' );
				}
				?>
				<?php
				if ( ! post_password_required() && ! is_attachment() && has_post_thumbnail() ) {
					?>
					<figure class="post-thumbnail">
						<?php the_post_thumbnail( 'post-thumbnail' ); ?>
					</figure><!-- .post-thumbnail -->
					<?php
				}
				?>
			</header><!-- .entry-header -->

			<div class="entry-content">
				<?php
				the_content();

				wp_link_pages(
					array(
						'before'   => '<nav class="page-links" aria-label="' . esc_attr__( 'Page', 'wp-empty-theme' ) . '">',
						'after'    => '</nav>',
						/* translators: %: Page number. */
						'pagelink' => esc_html__( 'Page %', 'wp-empty-theme' ),
					)
				);
				?>
			</div><!-- .entry-content -->

			<footer class="entry-footer default-max-width">
				<?php wp_empty_theme_entry_meta_footer(); ?>
			</footer><!-- .entry-footer -->

		</article><!-- #post-<?php the_ID(); ?> -->
		<?php
	}

	if ( is_singular() ) {
		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}

		the_post_navigation(
			array(
				'next_text' => '<p class="meta-nav">' . esc_html__( 'Next post', 'wp-empty-theme' ) . '</p><p class="post-title">%title</p>',
				'prev_text' => '<p class="meta-nav">' . esc_html__( 'Previous post', 'wp-empty-theme' ) . '</p><p class="post-title">%title</p>',
			)
		);
	} else {
		the_posts_pagination(
			array(
				'before_page_number' => esc_html__( 'Page', 'wp-empty-theme' ) . ' ',
				'mid_size'           => 0,
				'prev_text'          => sprintf(
					'<span class="nav-prev-text">%s</span>',
					wp_kses(
						__( 'Newer <span class="nav-short">posts</span>', 'wp-empty-theme' ),
						array(
							'span' => array(
								'class' => array(),
							),
						)
					)
				),
				'next_text'          => sprintf(
					'<span class="nav-next-text">%s</span>',
					wp_kses(
						__( 'Older <span class="nav-short">posts</span>', 'wp-empty-theme' ),
						array(
							'span' => array(
								'class' => array(),
							),
						)
					)
				),
			)
		);
	}
} else {
	?>
	<div class="page-content default-max-width">
		<p><?php esc_html_e( 'It seems we can&rsquo;t find what you&rsquo;re looking for. Perhaps searching can help.', 'wp-empty-theme' ); ?></p>
		<?php get_search_form(); ?>
	</div>
	<?php
}
?>

<?php
get_footer();
