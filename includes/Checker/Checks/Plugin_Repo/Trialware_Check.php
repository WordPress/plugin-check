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
 * Check to detect trialware or locked built-in functionality.
 *
 * Scans code-like files for indicators that functionality shipped with the plugin
 * is gated behind license keys, trials, payment checks, quotas, or "pro" plan gates.
 *
 * @since 1.4.0
 */
class Trialware_Check extends Abstract_File_Check {

	use Amend_Check_Result;
	use Stable_Check;

	/**
	 * Pattern groups to detect trialware indicators.
	 *
	 * Each group has:
	 * - 'patterns': array of regex patterns.
	 * - 'code':     result code for matches in this group.
	 * - 'message':  human-readable description of what was detected.
	 *
	 * @since 1.4.0
	 * @var array
	 */
	const PATTERN_GROUPS = array(
		'license_gate'     => array(
			'patterns' => array(
				'/(?:is_licensed|has_license|license_valid|check_license|verify_license)\s*\(/i',
				'/(?:if\s*\(\s*!?\s*(?:license_key|license|license_status))/i',
				'/license_key\s*(?:===|!==|==|!=)\s*[\'"](?:[a-zA-Z0-9_-]{10,}|FREE|TRIAL)[\'"]/i',
			),
			'code'     => 'trialware_license_gate_candidate',
			'message'  => 'Detected possible license key gate on plugin functionality.',
		),
		'pro_premium_gate' => array(
			'patterns' => array(
				'/if\s*\(\s*!?\s*(?:is_pro|is_premium|has_pro|has_premium|pro_user|premium_user|is_paid)\s*\(/i',
				'/if\s*\(\s*(?:!\s*)?(?:\\$this->|self::|static::)?(?:is_pro|is_premium|can_use_pro|pro_enabled|premium_enabled)\b/i',
				'/(?:current_plan|user_plan|subscription_plan)\s*(?:===|!==|==|!=)\s*[\'"](?:pro|premium|business|enterprise)[\'"]/i',
			),
			'code'     => 'trialware_pro_premium_gate_candidate',
			'message'  => 'Detected possible pro/premium gate on plugin functionality.',
		),
		'trial_gate'       => array(
			'patterns' => array(
				'/if\s*\(\s*(?:!\s*)?(?:trial_expired|is_trial_over|trial_active|has_trial|in_trial|trial_days)\s*\(/i',
				'/trial_(?:days|period|expires?|end|remaining)\s*(?:<=|>=|<|>|===|!==|==|!=)\s*\d/i',
				'/(?:free_trial|trial_limit|trial_usage|trial_count)\s*(?:<=|>=|<|>|===|!==|==|!=)/i',
			),
			'code'     => 'trialware_trial_gate_candidate',
			'message'  => 'Detected possible trial period gate on plugin functionality.',
		),
		'quota_gate'       => array(
			'patterns' => array(
				'/if\s*\(\s*(?:!\s*)?(?:quota_exceeded|limit_reached|usage_limit|over_quota|at_limit|exceeds_limit)\s*\(/i',
				'/if\s*\(\s*\\$\w*(?:usage|count|quota|limit)\s*(?:<=|>=|<|>|===|!==|==|!=)\s*\\$\w*(?:limit|max|quota|allowed)/i',
				'/(?:upgrade_to|subscribe|purchase|buy)\s*\(\s*[\'"](?:pro|premium|paid|unlimited)[\'"]/i',
			),
			'code'     => 'trialware_quota_gate_candidate',
			'message'  => 'Detected possible usage quota gate on plugin functionality.',
		),
		'payment_gate'     => array(
			'patterns' => array(
				'/if\s*\(\s*(?:!\s*)?(?:has_paid|is_paid_user|payment_valid|subscription_active|is_subscribed)\s*\(/i',
				'/(?:unlock|upgrade|go_pro|go_premium|get_pro|buy_pro)\s*\(\s*\)/i',
			),
			'code'     => 'trialware_payment_gate_candidate',
			'message'  => 'Detected possible payment gate on plugin functionality.',
		),
	);

	/**
	 * Gets the categories for the check.
	 *
	 * Every check must have at least one category.
	 *
	 * @since 1.4.0
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_PLUGIN_REPO );
	}

	/**
	 * Amends the given result by running the check on the given list of files.
	 *
	 * @since 1.4.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files  List of absolute file paths.
	 *
	 * @throws Exception Thrown when the check fails with a critical error (unrelated to any errors detected as part of
	 *                   the check).
	 */
	protected function check_files( Check_Result $result, array $files ) {
		$code_files = array_merge(
			self::filter_files_by_extension( $files, 'php' ),
			self::filter_files_by_extension( $files, 'js' )
		);

		foreach ( self::PATTERN_GROUPS as $group ) {
			$this->scan_for_pattern_group( $result, $code_files, $group );
		}
	}

	/**
	 * Scans files for a pattern group and amends the result with matches.
	 *
	 * Each pattern in the group is checked independently. Matches are de-duplicated
	 * per file so the same file is not reported multiple times for the same group.
	 *
	 * @since 1.4.0
	 *
	 * @param Check_Result $result      The check result to amend.
	 * @param array        $code_files  List of absolute file paths.
	 * @param array        $group       Pattern group with 'patterns', 'code', and 'message' keys.
	 */
	private function scan_for_pattern_group( Check_Result $result, array $code_files, array $group ) {
		$reported_files = array();

		foreach ( $group['patterns'] as $pattern ) {
			$files = self::files_preg_match_all( $pattern, $code_files );

			if ( empty( $files ) ) {
				continue;
			}

			foreach ( $files as $file ) {
				$file_path = $file['file'];

				// De-duplicate: report each file only once per group.
				if ( isset( $reported_files[ $file_path ] ) ) {
					continue;
				}

				$reported_files[ $file_path ] = true;

				$this->add_result_error_for_file(
					$result,
					$group['message'],
					$group['code'],
					$file_path,
					$file['line'],
					$file['column'],
					'https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/',
					7
				);
			}
		}
	}

	/**
	 * Gets the description for the check.
	 *
	 * Every check must have a short description explaining what the check does.
	 *
	 * @since 1.4.0
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return __( 'Detects potential trialware or locked built-in functionality in plugins.', 'plugin-check' );
	}

	/**
	 * Gets the documentation URL for the check.
	 *
	 * Every check must have a URL with further information about the check.
	 *
	 * @since 1.4.0
	 *
	 * @return string The documentation URL.
	 */
	public function get_documentation_url(): string {
		return __( 'https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/', 'plugin-check' );
	}
}
