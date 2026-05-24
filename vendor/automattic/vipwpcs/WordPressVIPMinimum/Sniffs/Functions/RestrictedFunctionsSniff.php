<?php
/**
 * WordPressVIPMinimum Coding Standard.
 *
 * @package VIPCS\WordPressVIPMinimum
 */

namespace WordPressVIPMinimum\Sniffs\Functions;

use PHP_CodeSniffer\Util\Tokens;
use WordPressCS\WordPress\AbstractFunctionRestrictionsSniff;

/**
 * Restricts usage of some functions in VIP context.
 */
class RestrictedFunctionsSniff extends AbstractFunctionRestrictionsSniff {

	/**
	 * Groups of functions to restrict.
	 *
	 * @return array
	 */
	public function getGroups() {

		$groups = [
			'opcache' => [
				'type'      => 'error',
				'message'   => '`%s` is prohibited on the WordPress VIP platform due to memory corruption.',
				'functions' => [
					'opcache_reset',
					'opcache_invalidate',
					'opcache_compile_file',
				],
			],
			'config_settings' => [
				'type'      => 'error',
				'message'   => '`%s` is not recommended for use on the WordPress VIP platform due to potential setting changes.',
				'functions' => [
					'opcache_is_script_cached',
					'opcache_get_status',
					'opcache_get_configuration',
				],
			],
			'internal' => [
				'type'      => 'error',
				'message'   => '`%1$s()` is for internal use only.',
				'functions' => [
					'wpcom_vip_irc',
				],
			],
			'flush_rewrite_rules' => [
				'type'      => 'error',
				'message'   => '`%s` should not be used in any normal circumstances in the theme code.',
				'functions' => [
					'flush_rewrite_rules',
				],
			],
			'flush_rules' => [
				'type'       => 'error',
				'message'    => '`%s` should not be used in any normal circumstances in the theme code.',
				'functions'  => [
					'flush_rules',
				],
				'object_var' => [
					'$wp_rewrite' => true,
				],
			],
			'attachment_url_to_postid' => [
				'type'      => 'error',
				'message'   => '`%s()` is prohibited, please use `wpcom_vip_attachment_url_to_postid()` instead.',
				'functions' => [
					'attachment_url_to_postid',
				],
			],
			// @link https://docs.wpvip.com/technical-references/code-review/vip-notices/#h-switch_to_blog
			'switch_to_blog' => [
				'type'      => 'warning',
				'message'   => '%s() may not work as expected since it only changes the database context for the blog and does not load the plugins or theme of that site. Filters or hooks on the blog you are switching to will not run.',
				'functions' => [
					'switch_to_blog',
				],
			],
			'url_to_postid' => [
				'type'      => 'error',
				'message'   => '%s() is prohibited, please use wpcom_vip_url_to_postid() instead.',
				'functions' => [
					'url_to_postid',
				],
			],
			// @link https://docs.wpvip.com/how-tos/customize-user-roles/
			'custom_role' => [
				'type'      => 'error',
				'message'   => 'Use wpcom_vip_add_role() instead of %s().',
				'functions' => [
					'add_role',
				],
			],
			'count_user_posts' => [
				'type'      => 'error',
				'message'   => '%s() is highly discouraged due to not being cached; please use wpcom_vip_count_user_posts() instead.',
				'functions' => [
					'count_user_posts',
				],
			],
			'wp_old_slug_redirect' => [
				'type'      => 'error',
				'message'   => '%s() is highly discouraged due to not being cached; please use wpcom_vip_old_slug_redirect() instead.',
				'functions' => [
					'wp_old_slug_redirect',
				],
			],
			'get_adjacent_post' => [
				'type'      => 'error',
				'message'   => '%s() is highly discouraged due to not being cached; please use wpcom_vip_get_adjacent_post() instead.',
				'functions' => [
					'get_adjacent_post',
					'get_previous_post',
					'get_previous_post_link',
					'get_next_post',
					'get_next_post_link',
				],
			],
			'get_intermediate_image_sizes' => [
				'type'      => 'error',
				'message'   => 'Intermediate images do not exist on the VIP platform, and thus get_intermediate_image_sizes() returns an empty array() on the platform. This behavior is intentional to prevent WordPress from generating multiple thumbnails when images are uploaded.',
				'functions' => [
					'get_intermediate_image_sizes',
				],
			],
			// @link https://docs.wpvip.com/technical-references/code-review/vip-warnings/#h-mobile-detection
			'wp_is_mobile' => [
				'type'      => 'error',
				'message'   => '%s() found. When targeting mobile visitors, jetpack_is_mobile() should be used instead of wp_is_mobile. It is more robust and works better with full page caching.',
				'functions' => [
					'wp_is_mobile',
				],
			],
			'session' => [
				'type'      => 'error',
				'message'   => 'The use of PHP session function %s() is prohibited.',
				'functions' => [
					'session_abort',
					'session_cache_expire',
					'session_cache_limiter',
					'session_commit',
					'session_create_id',
					'session_decode',
					'session_destroy',
					'session_encode',
					'session_gc',
					'session_get_cookie_params',
					'session_id',
					'session_is_registered',
					'session_module_name',
					'session_name',
					'session_regenerate_id',
					'session_register_shutdown',
					'session_register',
					'session_reset',
					'session_save_path',
					'session_set_cookie_params',
					'session_set_save_handler',
					'session_start',
					'session_status',
					'session_unregister',
					'session_unset',
					'session_write_close',
				],
			],
			'file_ops' => [
				'type'      => 'error',
				'message'   => 'Filesystem writes are forbidden, please do not use %s().',
				'functions' => [
					'file_put_contents',
					'flock',
					'fputcsv',
					'fputs',
					'fwrite',
					'ftruncate',
					'is_writable',
					'is_writeable',
					'link',
					'rename',
					'symlink',
					'tempnam',
					'touch',
					'unlink',
				],
			],
			'directory' => [
				'type'      => 'error',
				'message'   => 'Filesystem writes are forbidden, please do not use %s().',
				'functions' => [
					'mkdir',
					'rmdir',
				],
			],
			'chmod' => [
				'type'      => 'error',
				'message'   => 'Filesystem writes are forbidden, please do not use %s().',
				'functions' => [
					'chgrp',
					'chown',
					'chmod',
					'lchgrp',
					'lchown',
				],
			],
			'stats_get_csv' => [
				'type'      => 'error',
				'message'   => 'Using `%s` outside of Jetpack context pollutes the stats_cache entry in the wp_options table. We recommend building a custom function instead.',
				'functions' => [
					'stats_get_csv',
				],
			],
			'wp_mail' => [
				'type'      => 'warning',
				'message'   => '`%s` should be used sparingly. For any bulk emailing should be handled by a 3rd party service, in order to prevent domain or IP addresses being flagged as spam.',
				'functions' => [
					'wp_mail',
					'mail',
				],
			],
			'is_multi_author' => [
				'type'      => 'warning',
				'message'   => '`%s` can be very slow on large sites and likely not needed on many VIP sites since they tend to have more than one author.',
				'functions' => [
					'is_multi_author',
				],
			],
			'advanced_custom_fields' => [
				'type'      => 'warning',
				'message'   => '`%1$s` does not escape output by default, please echo and escape with the `get_*()` variant function instead (i.e. `get_field()`).',
				'functions' => [
					'the_sub_field',
					'the_field',
				],
			],
			// @link https://docs.wpvip.com/technical-references/code-review/vip-warnings/#h-remote-calls
			'wp_remote_get' => [
				'type'      => 'warning',
				'message'   => '%s() is highly discouraged. Please use vip_safe_wp_remote_get() instead which is designed to more gracefully handle failure than wp_remote_get() does.',
				'functions' => [
					'wp_remote_get',
				],
			],
			// @link https://docs.wpvip.com/technical-references/code-review/vip-errors/#h-cache-constraints
			'cookies' => [
				'type'      => 'error',
				'message'   => 'Due to server-side caching, server-side based client related logic might not work. We recommend implementing client side logic in JavaScript instead.',
				'functions' => [
					'setcookie',
				],
			],
			// @todo Introduce a sniff specific to get_posts() that checks for suppress_filters=>false being supplied.
			'get_posts' => [
				'type'      => 'warning',
				'message'   => '%s() is uncached unless the "suppress_filters" parameter is set to false. If the suppress_filter parameter is set to false this can be safely ignored. More Info: https://docs.wpvip.com/technical-references/caching/uncached-functions/.',
				'functions' => [
					'get_posts',
					'wp_get_recent_posts',
					'get_children',
				],
			],
			'create_function' => [
				'type'      => 'warning',
				'message'   => '%s() is highly discouraged, as it can execute arbritary code (additionally, it\'s deprecated as of PHP 7.2): https://docs.wpvip.com/technical-references/code-review/vip-warnings/#h-eval-and-create_function. )',
				'functions' => [
					'create_function',
				],
			],
		];

		$deprecated_vip_helpers = [
			'get_term_link'        => 'wpcom_vip_get_term_link',
			'get_term_by'          => 'wpcom_vip_get_term_by',
			'get_category_by_slug' => 'wpcom_vip_get_category_by_slug',
		];
		foreach ( $deprecated_vip_helpers as $restricted => $helper ) {
			$groups[ $helper ] = [
				'type'      => 'warning',
				'message'   => "`%s()` is deprecated, please use `{$restricted}()` instead.",
				'functions' => [
					$helper,
				],
			];
		}

		return $groups;
	}

	/**
	 * Verify the current token is a function call or a method call on a specific object variable.
	 *
	 * This differs to the parent class method that it overrides, by also checking to see if the
	 * function call is actually a method call on a specific object variable. This works best with global objects,
	 * such as the `flush_rules()` method on the `$wp_rewrite` object.
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 *
	 * @return bool
	 */
	public function is_targetted_token( $stackPtr ) {
		// Exclude function definitions, class methods, and namespaced calls.
		if ( $this->tokens[ $stackPtr ]['code'] === \T_STRING && isset( $this->tokens[ $stackPtr - 1 ] ) ) {
			// Check if this is really a function.
			$next = $this->phpcsFile->findNext( Tokens::$emptyTokens, $stackPtr + 1, null, true );
			if ( $next !== false && $this->tokens[ $next ]['code'] !== T_OPEN_PARENTHESIS ) {
				return false;
			}

			$prev = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $stackPtr - 1, null, true );
			if ( $prev !== false ) {

				// Start difference to parent class method.
				// Check to see if function is a method on a specific object variable.
				if ( ! empty( $this->groups[ $this->tokens[ $stackPtr ]['content'] ]['object_var'] ) ) {
					$prevPrev = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $stackPtr - 2, null, true );

					return $this->tokens[ $prev ]['code'] === \T_OBJECT_OPERATOR && isset( $this->groups[ $this->tokens[ $stackPtr ]['content'] ]['object_var'][ $this->tokens[ $prevPrev ]['content'] ] );
				} // End difference to parent class method.

				// Skip sniffing if calling a same-named method, or on function definitions.
				$skipped = [
					\T_FUNCTION        => \T_FUNCTION,
					\T_CLASS           => \T_CLASS,
					\T_AS              => \T_AS, // Use declaration alias.
					\T_DOUBLE_COLON    => \T_DOUBLE_COLON,
					\T_OBJECT_OPERATOR => \T_OBJECT_OPERATOR,
					\T_NEW             => \T_NEW,
				];
				if ( isset( $skipped[ $this->tokens[ $prev ]['code'] ] ) ) {
					return false;
				}
				// Skip namespaced functions, ie: `\foo\bar()` not `\bar()`.
				if ( $this->tokens[ $prev ]['code'] === \T_NS_SEPARATOR ) {
					$pprev = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $prev - 1, null, true );
					if ( $pprev !== false && $this->tokens[ $pprev ]['code'] === \T_STRING ) {
						return false;
					}
				}
			}
			return true;
		}
		return false;
	}
}
