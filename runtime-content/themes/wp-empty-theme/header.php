<?php
/**
 * The template for displaying the header
 *
 * @package wp-empty-theme
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'wp-empty-theme' ); ?></a>

	<header id="masthead" class="site-header" role="banner">
		<div class="site-branding">
			<?php
			if ( has_custom_logo() ) {
				?>
				<div class="site-logo"><?php the_custom_logo(); ?></div>
				<?php
			}

			if ( get_bloginfo( 'name', 'display' ) ) {
				?>
				<p class="site-title">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
				</p>
				<?php
			}

			if ( get_bloginfo( 'description', 'display' ) ) {
				?>
				<p class="site-description">
					<?php bloginfo( 'description' ); ?>
				</p>
				<?php
			}
			?>
		</div><!-- .site-branding -->
	</header><!-- #masthead -->

	<div id="content" class="site-content">
		<div id="primary" class="content-area">
			<main id="main" class="site-main" role="main">
