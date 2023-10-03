<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks\Trademarks_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Check for trademarks.
 *
 * @since n.e.x.t
 */
class Trademarks_Check extends Abstract_File_Check {

	use Stable_Check;

	/**
	 * Lists of trademark terms that are commonly abused on WordPress.org.
	 *
	 * @since n.e.x.t
	 *
	 * @var string[]
	 */
	const TRADEMARK_SLUGS = array(
		'adobe-',
		'adsense-',
		'advanced-custom-fields-',
		'adwords-',
		'akismet-',
		'all-in-one-wp-migration',
		'amazon-',
		'android-',
		'apple-',
		'applenews-',
		'applepay-',
		'aws-',
		'azon-',
		'bbpress-',
		'bing-',
		'booking-com',
		'bootstrap-',
		'buddypress-',
		'chatgpt-',
		'chat-gpt-',
		'cloudflare-',
		'contact-form-7-',
		'cpanel-',
		'disqus-',
		'divi-',
		'dropbox-',
		'easy-digital-downloads-',
		'elementor-',
		'envato-',
		'fbook',
		'facebook',
		'fb-',
		'fb-messenger',
		'fedex-',
		'feedburner',
		'firefox-',
		'fontawesome-',
		'font-awesome-',
		'ganalytics-',
		'gberg',
		'github-',
		'givewp-',
		'google-',
		'googlebot-',
		'googles-',
		'gravity-form-',
		'gravity-forms-',
		'gravityforms-',
		'gtmetrix-',
		'gutenberg',
		'guten-',
		'hubspot-',
		'ig-',
		'insta-',
		'instagram',
		'internet-explorer-',
		'ios-',
		'jetpack-',
		'macintosh-',
		'macos-',
		'mailchimp-',
		'microsoft-',
		'ninja-forms-',
		'oculus',
		'onlyfans-',
		'only-fans-',
		'opera-',
		'paddle-',
		'paypal-',
		'pinterest-',
		'plugin',
		'skype-',
		'stripe-',
		'tiktok-',
		'tik-tok-',
		'trustpilot',
		'twitch-',
		'twitter-',
		'tweet',
		'ups-',
		'usps-',
		'vvhatsapp',
		'vvcommerce',
		'vva-',
		'vvoo',
		'wa-',
		'webpush-vn',
		'wh4tsapps',
		'whatsapp',
		'whats-app',
		'watson',
		'windows-',
		'wocommerce',
		'woocom-',
		'woocommerce',  // technically ending with '-for-woocommerce' is allowed.
		'woocomerce',
		'woo-commerce',
		'woo-',
		'wo-',
		'wordpress',
		'wordpess',
		'wpress',
		'wp-',
		'wp-mail-smtp-',
		'yandex-',
		'yahoo-',
		'yoast',
		'youtube-',
		'you-tube-',
	);

	/**
	 * Lists of trademarks that are allowed as 'for-whatever' ONLY.
	 *
	 * @since n.e.x.t
	 *
	 * @var string[]
	 */
	const FOR_USE_EXCEPTIONS = array(
		'woocommerce',
	);

	/**
	 * Lists of commonly used 'combo' names (to prevent things like 'woopress').
	 *
	 * @since n.e.x.t
	 *
	 * @var string[]
	 */
	const PORTMANTEAUS = array(
		'woo',
	);

	const CHECK_README = 1;
	const CHECK_NAME   = 2;
	const CHECK_SLUG   = 4;
	const TYPE_ALL     = 7; // Same as all of the above with bitwise OR.

	/**
	 * Bitwise flags to control check behavior.
	 *
	 * @since n.e.x.t
	 * @var int
	 */
	protected $flags = 0;

	/**
	 * Constructor.
	 *
	 * @since n.e.x.t
	 *
	 * @param int $flags Bitwise flags to control check behavior.
	 */
	public function __construct( $flags = self::TYPE_ALL ) {
		$this->flags = $flags;
	}

	/**
	 * Gets the categories for the check.
	 *
	 * Every check must have at least one category.
	 *
	 * @since n.e.x.t
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_PLUGIN_REPO );
	}

	/**
	 * Check for trademarks.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The Check Result to amend.
	 * @param array        $files  Array of plugin files.
	 */
	protected function check_files( Check_Result $result, array $files ) {

		// Check the trademarks in readme file plugin name.
		if ( $this->flags & self::CHECK_README ) {
			$this->check_for_readme( $result, $files );
		}

		// Check the trademarks in plugin name.
		if ( $this->flags & self::CHECK_NAME ) {
			$this->check_for_name( $result );
		}

		// Check the trademarks in plugin slug.
		if ( $this->flags & self::CHECK_SLUG ) {
			$this->check_for_slug( $result );
		}
	}

	/**
	 * Checks the trademarks in readme file plugin name.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The Check Result to amend.
	 * @param array        $files  Array of plugin files.
	 */
	private function check_for_readme( Check_Result $result, array $files ) {
		$plugin_relative_path = $result->plugin()->path();

		// Find the readme file.
		$readme_list = self::filter_files_by_regex( $files, '/readme\.(txt|md)$/i' );

		// Filter the readme files located at root.
		$potential_readme_files = array_filter(
			$readme_list,
			function ( $file ) use ( $plugin_relative_path ) {
				$file = str_replace( $plugin_relative_path, '', $file );
				if ( ! strpos( $file, '/' ) ) {
					return true;
				}
			}
		);

		// Find the .txt versions of the readme files.
		$readme_txt = array_filter(
			$potential_readme_files,
			function ( $file ) {
				return preg_match( '/^readme\.txt$/i', basename( $file ) );
			}
		);

		// Find the .md versions of the readme files.
		$readme_md = array_filter(
			$potential_readme_files,
			function ( $file ) {
				return preg_match( '/^readme\.md$/i', basename( $file ) );
			}
		);

		// If there's a .txt version, ignore .md versions.
		$readme = ( ! empty( $readme_txt ) ) ? $readme_txt : $readme_md;

		// If the readme file does not exist, then skip test.
		if ( empty( $readme ) ) {
			return;
		}

		$matches = array();
		// Get the plugin name from readme file.
		$file = self::file_preg_match( '/===(.*)===/i', $files, $matches );

		if ( ! $file ) {
			return;
		}

		$name = isset( $matches[1] ) ? $matches[1] : '';

		$this->look_for_trademark(
			$result,
			str_replace( $result->plugin()->path(), '', $file ),
			$name,
			__( 'The plugin name includes a restricted term.', 'plugin-check' )
		);
	}

	/**
	 * Checks the readme file for default text.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The Check Result to amend.
	 */
	private function check_for_name( Check_Result $result ) {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_main_file = WP_PLUGIN_DIR . '/' . $result->plugin()->basename();
		$plugin_header    = get_plugin_data( $plugin_main_file );

		if ( ! empty( $plugin_header['Name'] ) ) {
			$this->look_for_trademark(
				$result,
				$plugin_main_file,
				$plugin_header['Name'],
				__( 'The plugin name includes a restricted term.', 'plugin-check' )
			);
		}
	}

	/**
	 * Checks the readme file for default text.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The Check Result to amend.
	 */
	private function check_for_slug( Check_Result $result ) {
		$plugin_slug = basename( $result->plugin()->path() );

		$this->look_for_trademark(
			$result,
			WP_PLUGIN_DIR . '/' . $result->plugin()->basename(),
			$plugin_slug,
			__( 'The plugin slug includes a restricted term.', 'plugin-check' )
		);
	}

	/**
	 * Determines if we find a trademarked term.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result   The Check Result to amend.
	 * @param string       $file     The plugin file.
	 * @param string       $input    The plugin name or slug.
	 * @param string       $preamble The preamble message.
	 */
	private function look_for_trademark( $result, $file, $input, $preamble ) {
		if ( empty( $input ) ) {
			return;
		}

		$check = $this->has_trademarked_slug( $input );
		if ( ! $check ) {
			return;
		}

		if (
			trim( $check, '-' ) === $check
			&& in_array( $check, self::FOR_USE_EXCEPTIONS, true )
		) {
			// Trademarks that do NOT end in "-", but are within the FOR_USE_EXCEPTIONS array can be used, but only if it ends with 'for x'.
			$message = sprintf(
				/* translators: 1: plugin name or slug, 2: found trademarked term */
				__( 'Your chosen plugin name - %1$s - contains the restricted term "%2$s" which cannot be used within in your plugin name, unless your plugin name ends with "for %2$s". The term must still not appear anywhere else in your name.', 'plugin-check' ),
				'<code>' . esc_html( $input ) . '</code>',
				esc_html( trim( $check, '-' ) )
			);
		} elseif ( trim( $check, '-' ) === $check ) {
			// Trademarks that do NOT end in "-" indicate slug cannot contain term at all.
			$message = sprintf(
				/* translators: 1: plugin name or slug, 2: found trademarked term */
				__( 'Your chosen plugin name - %1$s - contains the restricted term "%2$s" which cannot be used at all in your plugin name.', 'plugin-check' ),
				'<code>' . esc_html( $input ) . '</code>',
				esc_html( trim( $check, '-' ) )
			);
		} else {
			// Trademarks ending in "-" indicate slug cannot BEGIN with that term.
			$message = sprintf(
				/* translators: 1: plugin name or slug, 2: found trademarked term */
				__( 'Your chosen plugin name - %1$s - contains the restricted term "%2$s" and cannot be used to begin your plugin name. We disallow the use of certain terms in ways that are abused, or potentially infringe on and/or are misleading with regards to trademarks. You may use the term "%2$s" elsewhere in your plugin name, such as "... for %2$s".', 'plugin-check' ),
				'<code>' . esc_html( $input ) . '</code>',
				esc_html( trim( $check, '-' ) )
			);
		}

		$result->add_message(
			true,
			$preamble . ' ' . $message,
			array(
				'code' => 'trademarked_term',
				'file' => $file,
			)
		);
	}

	/**
	 * Whether the plugin uses a trademark in the slug.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $slug The plugin slug.
	 * @return string|false The trademark slug if found, false otherwise.
	 */
	private function has_trademarked_slug( $slug ) {
		// We work on slugs for this check.
		$slug = sanitize_title_with_dashes( $slug );

		$has_trademarked_slug = false;

		foreach ( self::TRADEMARK_SLUGS as $trademark ) {
			if ( '-' === $trademark[-1] ) {
				// Trademarks ending in "-" indicate slug cannot begin with that term.
				if ( 0 === strpos( $slug, $trademark ) ) {
					$has_trademarked_slug = $trademark;
					break;
				}
			} elseif ( false !== strpos( $slug, $trademark ) ) {
				// Otherwise, the term cannot appear anywhere in slug.

				// check for 'for-TRADEMARK' exceptions.
				if ( $this->is_valid_for_use_exception( $slug, $trademark ) ) {
					// It is a valid for-use exception, try the next trademark.
					continue;
				}

				$has_trademarked_slug = $trademark;
				break;
			}
		}

		// Check portmanteaus.
		if ( ! $has_trademarked_slug ) {
			foreach ( self::PORTMANTEAUS as $portmanteau ) {
				if ( 0 === stripos( $slug, $portmanteau ) ) {
					$has_trademarked_slug = $portmanteau;
					break;
				}
			}
		}

		return $has_trademarked_slug;
	}

	/**
	 * Validates whether the trademark is valid with a for-use exception.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $slug The plugin slug.
	 * @param string $trademark The trademark term.
	 * @return bool True if the trademark is valid with a for-use exception, false otherwise.
	 */
	private function is_valid_for_use_exception( $slug, $trademark ) {
		if ( empty( $slug ) ) {
			return false;
		}

		if ( ! $trademark ) {
			return false;
		}

		if ( ! in_array( $trademark, self::FOR_USE_EXCEPTIONS, true ) ) {
			return false;
		}

		$for_trademark        = '-for-' . $trademark;
		$for_trademark_length = strlen( $for_trademark );
		if ( ! ( substr( $slug, -$for_trademark_length ) === $for_trademark ) ) {
			// The slug doesn't end with 'for-TRADEMARK', so it's an invalid use.
			return false;
		}

		/*
		 * Yes if slug ENDS with 'for-TRADEMARK'.
		 * Validate that the term still doesn't appear in another position of the slug.
		 */
		$short_slug = substr( $slug, 0, -1 * strlen( $for_trademark ) );

		// If the trademark still doesn't exist in the slug, it's OK.
		return false === strpos( $short_slug, $trademark );
	}
}
