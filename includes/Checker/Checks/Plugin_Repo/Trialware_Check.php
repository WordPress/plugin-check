<?php
/**
 * Class Trialware_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Plugin_Repo;

use Exception;
use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_File_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Check to detect potential trialware and locked built-in features.
 *
 * @since 2.0.0
 */
class Trialware_Check extends Abstract_File_Check {

	use Amend_Check_Result;
	use Stable_Check;

	/**
	 * Result code for AI-only trialware candidates.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	const CANDIDATE_CODE = 'trialware_locked_feature_candidate';

	/**
	 * Result code for AI-confirmed trialware issues.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	const CONFIRMED_CODE = 'trialware_locked_feature_detected';

	/**
	 * Regex fragments that indicate possible trialware or locked features.
	 *
	 * These are adapted from the WordPress.org Plugin Review internal scanner.
	 *
	 * @since 2.0.0
	 * @var array
	 */
	const LOCKED_FEATURE_PATTERNS = array(
		'licen[sc]e[ -_]?(key|id|code|status|tier|manager|token)(?!entifier)',
		'plugin[ -_]?licen[sc]e',
		'activation[ -_]?(key|id|code)',
		'is[-_](free|paying|pro|trial|premium)(?!mpt|duct|cess|able|tocol|tected|p-valid|bably|xied|mise|vider)',
		'free[ -_]?(trial)',
		'(paying|pro|premium)[ -_]?(licen[sc]e|access)',
		'purchase_code',
		'redmuber_item_',
		'(plan|free|lite)[ -_](limit|restriction)',
		'max[ -_]free',
		'(free|lite)[ -_]version[ -_](only|has)',
		'(add|for) unlimited',
		'to unlock',
		'limit to ((max|maximum) )?[0-9]+',
		'allowed in (free|lite) version',
		'(paying|pro|trial|premium)[ -_](enabled|expiration|feature)',
		'can add up to [0-9]',
		'limit reached',
	);

	/**
	 * File extensions that can contain code controlling locked features.
	 *
	 * @since 2.0.0
	 * @var array
	 */
	const CODE_EXTENSIONS = array( 'php', 'js', 'jsx', 'ts', 'tsx', 'vue' );

	/**
	 * Gets the categories for the check.
	 *
	 * @since 2.0.0
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_PLUGIN_REPO );
	}

	/**
	 * Amends the given result by running the check on the given list of files.
	 *
	 * @since 2.0.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files  List of absolute file paths.
	 *
	 * @throws Exception Thrown when the check fails with a critical error.
	 */
	protected function check_files( Check_Result $result, array $files ) {
		$files = $this->filter_files_to_scan( $files );
		if ( empty( $files ) ) {
			return;
		}

		$matches = self::files_preg_match_all( $this->get_locked_features_pattern(), $files );
		if ( empty( $matches ) ) {
			return;
		}

		$reported = array();
		foreach ( $matches as $match ) {
			$key = $match['file'] . ':' . $match['line'] . ':' . $match['column'];
			if ( isset( $reported[ $key ] ) ) {
				continue;
			}

			$reported[ $key ] = true;
			$this->add_result_error_for_file(
				$result,
				__( 'Potential trialware or locked built-in feature candidate. AI analysis must confirm whether the plugin restricts built-in functionality behind a license key, trial, quota, payment, or other artificial limit.', 'plugin-check' ),
				self::CANDIDATE_CODE,
				$match['file'],
				$match['line'],
				$match['column'],
				$this->get_documentation_url(),
				5
			);
		}
	}

	/**
	 * Filters the file list to code-like files and known low-signal exclusions.
	 *
	 * @since 2.0.0
	 *
	 * @param array $files List of absolute file paths.
	 * @return array Files to scan.
	 */
	private function filter_files_to_scan( array $files ) {
		return array_values(
			array_filter(
				$files,
				static function ( $file ) {
					$normalized = wp_normalize_path( $file );
					$extension  = strtolower( pathinfo( $normalized, PATHINFO_EXTENSION ) );
					$basename   = strtolower( basename( $normalized ) );

					if ( ! in_array( $extension, self::CODE_EXTENSIONS, true ) ) {
						return false;
					}

					if ( 'composer.json' === $basename ) {
						return false;
					}

					return false === strpos( $normalized, '/stripe-php/lib/' );
				}
			)
		);
	}

	/**
	 * Builds the combined regular expression for locked feature indicators.
	 *
	 * @since 2.0.0
	 *
	 * @return string Regular expression.
	 */
	private function get_locked_features_pattern() {
		$patterns = array_map(
			static function ( $pattern ) {
				return '(?:' . $pattern . ')';
			},
			self::LOCKED_FEATURE_PATTERNS
		);

		return '~' . implode( '|', $patterns ) . '~i';
	}

	/**
	 * Gets the description for the check.
	 *
	 * @since 2.0.0
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return __( 'Uses AI to detect trialware and locked built-in features.', 'plugin-check' );
	}

	/**
	 * Gets the documentation URL for the check.
	 *
	 * @since 2.0.0
	 *
	 * @return string The documentation URL.
	 */
	public function get_documentation_url(): string {
		return __( 'https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/', 'plugin-check' );
	}
}
