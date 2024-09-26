<?php
/**
 * Custom template tags for this theme
 *
 * @package wp-empty-theme
 */

if ( ! function_exists( 'wp_empty_theme_posted_on' ) ) {
	/**
	 * Prints HTML with meta information for the current post-date/time.
	 */
	function wp_empty_theme_posted_on() {
		$time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';

		$time_string = sprintf(
			$time_string,
			esc_attr( get_the_date( DATE_W3C ) ),
			esc_html( get_the_date() )
		);
		echo '<span class="posted-on">';
		printf(
			/* translators: %s: Publish date. */
			esc_html__( 'Published %s', 'wp-empty-theme' ),
			$time_string // phpcs:ignore WordPress.Security.EscapeOutput
		);
		echo '</span>';
	}
}

if ( ! function_exists( 'wp_empty_theme_posted_by' ) ) {
	/**
	 * Prints HTML with meta information about theme author.
	 */
	function wp_empty_theme_posted_by() {
		if ( ! get_the_author_meta( 'description' ) && post_type_supports( get_post_type(), 'author' ) ) {
			echo '<span class="byline">';
			printf(
				/* translators: %s: Author name. */
				esc_html__( 'By %s', 'wp-empty-theme' ),
				'<a href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '" rel="author">' . esc_html( get_the_author() ) . '</a>'
			);
			echo '</span>';
		}
	}
}

if ( ! function_exists( 'wp_empty_theme_entry_meta_footer' ) ) {
	/**
	 * Prints HTML with meta information for the categories, tags and comments.
	 * Footer entry meta is displayed differently in archives and single posts.
	 */
	function wp_empty_theme_entry_meta_footer() {

		// Early exit if not a post.
		if ( 'post' !== get_post_type() ) {
			return;
		}

		// Hide meta information on pages.
		if ( ! is_single() ) {

			if ( is_sticky() ) {
				echo '<p>' . esc_html_x( 'Featured post', 'Label for sticky posts', 'wp-empty-theme' ) . '</p>';
			}

			// Posted on.
			wp_empty_theme_posted_on();

			// Edit post link.
			edit_post_link(
				sprintf(
					/* translators: %s: Name of current post. Only visible to screen readers. */
					esc_html__( 'Edit %s', 'wp-empty-theme' ),
					'<span class="screen-reader-text">' . get_the_title() . '</span>'
				),
				'<span class="edit-link">',
				'</span><br>'
			);

			if ( has_category() || has_tag() ) {

				echo '<div class="post-taxonomies">';

				/* translators: Used between list items, there is a space after the comma. */
				$categories_list = get_the_category_list( __( ', ', 'wp-empty-theme' ) );
				if ( $categories_list ) {
					printf(
						/* translators: %s: List of categories. */
						'<span class="cat-links">' . esc_html__( 'Categorized as %s', 'wp-empty-theme' ) . ' </span>',
						$categories_list // phpcs:ignore WordPress.Security.EscapeOutput
					);
				}

				/* translators: Used between list items, there is a space after the comma. */
				$tags_list = get_the_tag_list( '', __( ', ', 'wp-empty-theme' ) );
				if ( $tags_list ) {
					printf(
						/* translators: %s: List of tags. */
						'<span class="tags-links">' . esc_html__( 'Tagged %s', 'wp-empty-theme' ) . '</span>',
						$tags_list // phpcs:ignore WordPress.Security.EscapeOutput
					);
				}
				echo '</div>';
			}
		} else {

			echo '<div class="posted-by">';
			// Posted on.
			wp_empty_theme_posted_on();
			// Posted by.
			wp_empty_theme_posted_by();
			// Edit post link.
			edit_post_link(
				sprintf(
					/* translators: %s: Name of current post. Only visible to screen readers. */
					esc_html__( 'Edit %s', 'wp-empty-theme' ),
					'<span class="screen-reader-text">' . get_the_title() . '</span>'
				),
				'<span class="edit-link">',
				'</span>'
			);
			echo '</div>';

			if ( has_category() || has_tag() ) {

				echo '<div class="post-taxonomies">';

				/* translators: Used between list items, there is a space after the comma. */
				$categories_list = get_the_category_list( __( ', ', 'wp-empty-theme' ) );
				if ( $categories_list ) {
					printf(
						/* translators: %s: List of categories. */
						'<span class="cat-links">' . esc_html__( 'Categorized as %s', 'wp-empty-theme' ) . ' </span>',
						$categories_list // phpcs:ignore WordPress.Security.EscapeOutput
					);
				}

				/* translators: Used between list items, there is a space after the comma. */
				$tags_list = get_the_tag_list( '', __( ', ', 'wp-empty-theme' ) );
				if ( $tags_list ) {
					printf(
						/* translators: %s: List of tags. */
						'<span class="tags-links">' . esc_html__( 'Tagged %s', 'wp-empty-theme' ) . '</span>',
						$tags_list // phpcs:ignore WordPress.Security.EscapeOutput
					);
				}
				echo '</div>';
			}
		}
	}
}
