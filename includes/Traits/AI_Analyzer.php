<?php
/**
 * Trait WordPress\Plugin_Check\Traits\AI_Analyzer
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Traits;

use WordPress\Plugin_Check\Admin\Settings_Page;
use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WP_Error;

/**
 * Trait for analyzing check results for false positives using AI.
 *
 * Uses a batched approach inspired by the internal scanner: issues are grouped
 * by check code prefix, each group gets a check-specific prompt loaded from
 * the prompts/ directory, and all cases in a batch are sent in a single AI
 * request for efficiency.
 *
 * @since 2.0.0
 */
trait AI_Analyzer {

	/**
	 * Checks if AI analysis is available via WordPress core AI client.
	 *
	 * @since 2.0.0
	 *
	 * @return bool True if AI client is available, false otherwise.
	 */
	protected function is_ai_available() {
		if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
			return false;
		}

		// Check WP 7.0+ AI support.
		if ( function_exists( 'wp_supports_ai' ) && ! wp_supports_ai() ) {
			return false;
		}

		return true;
	}

	/**
	 * Gets the maximum number of cases to send per AI batch request.
	 *
	 * @since 2.0.0
	 *
	 * @return int Batch size.
	 */
	protected function get_ai_batch_size() {
		return 12;
	}

	/**
	 * Gets the maximum number of cases to analyze per check type.
	 *
	 * @since 2.0.0
	 *
	 * @return int Maximum case count.
	 */
	protected function get_ai_max_cases_per_check() {
		return 24;
	}

	/**
	 * Gets the mapping of check code prefixes to their prompt template filenames.
	 *
	 * @since 2.0.0
	 *
	 * @return array Prompt map.
	 */
	protected function get_ai_prompt_map() {
		return array(
			'WordPress.Security.EscapeOutput'              => 'ai-review-late-escaping.md',
			'PluginCheck.CodeAnalysis.EscapeOutput'        => 'ai-review-late-escaping.md',
			'WordPress.Security.NonceVerification'         => 'ai-review-nonce-verification.md',
			'WordPress.Security.ValidatedSanitizedInput'   => 'ai-review-sanitization.md',
			'WordPress.DB.DirectDatabaseQuery'             => 'ai-review-direct-db-queries.md',
			'WordPress.DB.PreparedSQL'                     => 'ai-review-direct-db-queries.md',
			'PluginCheck.CodeAnalysis.Obfuscation'         => 'ai-review-code-obfuscation.md',
			'PluginCheck.CodeAnalysis.SettingSanitization' => 'ai-review-setting-sanitization.md',
			'PluginCheck.CodeAnalysis.PluginUpdater'       => 'ai-review-plugin-updater.md',
		);
	}

	/**
	 * Analyzes check results for false positives using batched AI requests.
	 *
	 * Issues are grouped by check code prefix, and each group is analyzed
	 * with a check-specific prompt. Only issues with severity below the
	 * configured threshold are analyzed.
	 *
	 * @since 2.0.0
	 *
	 * @param Check_Result  $result           Check result to analyze.
	 * @param Check_Context $check_context    Check context instance.
	 * @param string        $model_preference Optional model preference.
	 * @return array|WP_Error Array with 'analysis' and 'stats' keys, or WP_Error on failure.
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	protected function analyze_results_with_ai( Check_Result $result, Check_Context $check_context, $model_preference = '' ) {
		if ( ! $this->is_ai_available() ) {
			return new WP_Error(
				'ai_not_available',
				__( 'AI analysis requires WordPress 7.0 or newer with AI support enabled.', 'plugin-check' )
			);
		}

		$errors   = $result->get_errors();
		$warnings = $result->get_warnings();

		if ( empty( $errors ) && empty( $warnings ) ) {
			return $this->empty_ai_result();
		}

		// Collect all issues eligible for AI review, grouped by prompt type.
		$grouped_issues = $this->collect_issues_for_ai( $errors, $warnings, $check_context );

		if ( empty( $grouped_issues ) ) {
			return $this->empty_ai_result();
		}

		// Process each group with its specific prompt.
		$analysis_results = array();
		$total_tokens     = 0;
		$input_tokens     = 0;
		$output_tokens    = 0;
		$false_positives  = 0;
		$issues_analyzed  = 0;
		$models_used      = array();
		$providers_used   = array();

		foreach ( $grouped_issues as $prompt_file => $cases ) {
			$batch_result = $this->analyze_batch( $prompt_file, $cases, $check_context, $model_preference );

			if ( is_wp_error( $batch_result ) ) {
				continue;
			}

			foreach ( $batch_result['cases'] as $case_analysis ) {
				$case_id = $case_analysis['case_id'];
				if ( isset( $cases[ $case_id ] ) ) {
					$original                     = $cases[ $case_id ];
					$analysis_results[ $case_id ] = array(
						'is_false_positive' => false === $case_analysis['issue'],
						'reasoning'         => sanitize_text_field( $case_analysis['short_explanation'] ),
						'file'              => $original['file'],
						'line'              => $original['line'],
						'column'            => $original['column'],
						'code'              => $original['code'],
						'type'              => $original['type'],
					);

					++$issues_analyzed;

					if ( false === $case_analysis['issue'] ) {
						++$false_positives;
					}
				}
			}

			if ( isset( $batch_result['token_usage']['total_tokens'] ) ) {
				$total_tokens += (int) $batch_result['token_usage']['total_tokens'];
			}
			if ( isset( $batch_result['token_usage']['prompt_tokens'] ) ) {
				$input_tokens += (int) $batch_result['token_usage']['prompt_tokens'];
			}
			if ( isset( $batch_result['token_usage']['completion_tokens'] ) ) {
				$output_tokens += (int) $batch_result['token_usage']['completion_tokens'];
			}
			if ( ! empty( $batch_result['model_used'] ) ) {
				$models_used[] = (string) $batch_result['model_used'];
			}
			if ( ! empty( $batch_result['provider_used'] ) ) {
				$providers_used[] = (string) $batch_result['provider_used'];
			}
		}

		return array(
			'analysis' => $analysis_results,
			'stats'    => array(
				'tokens_spent'    => $total_tokens,
				'input_tokens'    => $input_tokens,
				'output_tokens'   => $output_tokens,
				'false_positives' => $false_positives,
				'issues_analyzed' => $issues_analyzed,
				'model_used'      => implode( ', ', array_unique( $models_used ) ),
				'provider_used'   => implode( ', ', array_unique( $providers_used ) ),
			),
		);
	}

	/**
	 * Collects issues eligible for AI review, grouped by prompt template.
	 *
	 * Only issues with severity below the configured threshold are included.
	 *
	 * @since 2.0.0
	 *
	 * @param array         $errors        Errors from Check_Result.
	 * @param array         $warnings      Warnings from Check_Result.
	 * @param Check_Context $check_context Check context instance.
	 * @return array Issues grouped by prompt filename. Each value is an associative
	 *               array keyed by case_id with issue metadata.
	 */
	protected function collect_issues_for_ai( array $errors, array $warnings, Check_Context $check_context ) {
		$error_threshold   = $this->get_ai_severity_threshold( 'error' );
		$warning_threshold = $this->get_ai_severity_threshold( 'warning' );

		$grouped = array();
		$counts  = array(); // Track count per prompt to enforce limit.

		// Process errors.
		$this->collect_issues_from_collection( $errors, 'error', $error_threshold, $check_context, $grouped, $counts );

		// Process warnings.
		$this->collect_issues_from_collection( $warnings, 'warning', $warning_threshold, $check_context, $grouped, $counts );

		return $grouped;
	}

	/**
	 * Collects issues from a single collection (errors or warnings).
	 *
	 * @since 2.0.0
	 *
	 * @param array         $collection    The errors or warnings collection.
	 * @param string        $type          'error' or 'warning'.
	 * @param int           $threshold     Severity threshold.
	 * @param Check_Context $check_context Check context instance.
	 * @param array         $grouped       Reference to grouped issues array.
	 * @param array         $counts        Reference to counts per prompt.
	 */
	protected function collect_issues_from_collection( array $collection, $type, $threshold, Check_Context $check_context, array &$grouped, array &$counts ) {
		foreach ( $collection as $file => $file_issues ) {
			foreach ( $file_issues as $line => $line_issues ) {
				foreach ( $line_issues as $column => $column_issues ) {
					foreach ( $column_issues as $issue ) {
						$severity = isset( $issue['severity'] ) ? (int) $issue['severity'] : 5;
						if ( $severity >= $threshold ) {
							continue;
						}

						$code        = isset( $issue['code'] ) ? $issue['code'] : '';
						$prompt_file = $this->get_prompt_for_code( $code );

						if ( ! isset( $counts[ $prompt_file ] ) ) {
							$counts[ $prompt_file ] = 0;
						}

						if ( $counts[ $prompt_file ] >= $this->get_ai_max_cases_per_check() ) {
							continue;
						}

						$case_id = $this->get_issue_key( $file, $line, $column, $code );

						if ( ! isset( $grouped[ $prompt_file ] ) ) {
							$grouped[ $prompt_file ] = array();
						}

						$grouped[ $prompt_file ][ $case_id ] = array(
							'file'    => $file,
							'line'    => $line,
							'column'  => $column,
							'code'    => $code,
							'message' => isset( $issue['message'] ) ? $issue['message'] : '',
							'type'    => $type,
						);

						++$counts[ $prompt_file ];
					}
				}
			}
		}
	}

	/**
	 * Analyzes a batch of issues with a specific prompt template.
	 *
	 * If the batch exceeds the configured batch size, it is split into sub-batches
	 * and each sub-batch is sent as a separate AI request.
	 *
	 * @since 2.0.0
	 *
	 * @param string        $prompt_file      Prompt template filename.
	 * @param array         $cases            Cases to analyze, keyed by case_id.
	 * @param Check_Context $check_context    Check context instance.
	 * @param string        $model_preference Optional model preference.
	 * @return array|WP_Error Array with 'cases' and 'token_usage' keys, or WP_Error.
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	protected function analyze_batch( $prompt_file, array $cases, Check_Context $check_context, $model_preference = '' ) {
		$issue_description = $this->load_prompt_template( $prompt_file );
		if ( is_wp_error( $issue_description ) ) {
			return $issue_description;
		}

		// Split into sub-batches if needed.
		$batches        = array_chunk( $cases, $this->get_ai_batch_size(), true );
		$all_cases      = array();
		$total_tokens   = 0;
		$input_tokens   = 0;
		$output_tokens  = 0;
		$models_used    = array();
		$providers_used = array();

		foreach ( $batches as $batch ) {
			$result = $this->execute_batch_ai_request( $issue_description, $batch, $check_context, $model_preference );

			if ( is_wp_error( $result ) ) {
				continue;
			}

			if ( isset( $result['cases'] ) && is_array( $result['cases'] ) ) {
				$all_cases = array_merge( $all_cases, $result['cases'] );
			}

			if ( isset( $result['token_usage']['total_tokens'] ) ) {
				$total_tokens += (int) $result['token_usage']['total_tokens'];
			}
			if ( isset( $result['token_usage']['prompt_tokens'] ) ) {
				$input_tokens += (int) $result['token_usage']['prompt_tokens'];
			}
			if ( isset( $result['token_usage']['completion_tokens'] ) ) {
				$output_tokens += (int) $result['token_usage']['completion_tokens'];
			}
			if ( ! empty( $result['model_used'] ) ) {
				$models_used[] = (string) $result['model_used'];
			}
			if ( ! empty( $result['provider_used'] ) ) {
				$providers_used[] = (string) $result['provider_used'];
			}
		}

		return array(
			'cases'         => $all_cases,
			'token_usage'   => array(
				'prompt_tokens'     => $input_tokens,
				'completion_tokens' => $output_tokens,
				'total_tokens'      => $total_tokens,
			),
			'model_used'    => implode( ', ', array_unique( $models_used ) ),
			'provider_used' => implode( ', ', array_unique( $providers_used ) ),
		);
	}

	/**
	 * Executes a single batched AI request for a group of cases.
	 *
	 * Builds a prompt following the internal scanner pattern:
	 * system instructions + issue description + cases list + output format.
	 *
	 * @since 2.0.0
	 *
	 * @param string        $issue_description Issue description from prompt template.
	 * @param array         $cases             Cases to analyze, keyed by case_id.
	 * @param Check_Context $check_context     Check context instance.
	 * @param string        $model_preference  Optional model preference.
	 * @return array|WP_Error Array with 'cases' and 'token_usage', or WP_Error.
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	protected function execute_batch_ai_request( $issue_description, array $cases, Check_Context $check_context, $model_preference = '' ) {
		$prompt = $this->build_batch_prompt( $issue_description, $cases, $check_context );

		if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
			return new WP_Error(
				'ai_client_not_available',
				__( 'AI client is not available. This feature requires WordPress 7.0 or newer.', 'plugin-check' )
			);
		}

		$builder = wp_ai_client_prompt( $prompt );
		if ( is_wp_error( $builder ) ) {
			return $builder;
		}

		// Apply model preference if provided.
		if ( ! empty( $model_preference ) ) {
			$builder = $this->apply_ai_model_preference( $builder, $model_preference );
			if ( is_wp_error( $builder ) ) {
				return $builder;
			}
		}

		try {
			// Try to generate a rich result first.
			$result = null;
			if ( is_callable( array( $builder, 'generate_text_result' ) ) ) {
				$result = $builder->generate_text_result();
			} elseif ( is_callable( array( $builder, 'generateTextResult' ) ) ) {
				$result = $builder->generateTextResult();
			}

			if ( ! $result || is_wp_error( $result ) ) {
				// Fallback to plain text generation.
				$text = $builder->generate_text();
				if ( is_wp_error( $text ) ) {
					return $text;
				}

				return array(
					'cases'         => $this->parse_batch_response( (string) $text ),
					'token_usage'   => array(),
					'model_used'    => $this->normalize_ai_model_used( $model_preference ),
					'provider_used' => $this->normalize_ai_provider_used( $model_preference ),
				);
			}

			$text     = method_exists( $result, 'to_text' ) ? $result->to_text() : ( method_exists( $result, 'toText' ) ? $result->toText() : '' );
			$usage    = $this->extract_ai_token_usage( $result );
			$model    = $this->extract_ai_model_used( $result );
			$provider = $this->extract_ai_provider_used( $result );

			return array(
				'cases'         => $this->parse_batch_response( $text ),
				'token_usage'   => $usage ? $usage : array(),
				'model_used'    => $model ? $model : $this->normalize_ai_model_used( $model_preference ),
				'provider_used' => $provider ? $provider : $this->normalize_ai_provider_used( $model_preference ),
			);
		} catch ( \Throwable $e ) {
			return new WP_Error(
				'ai_request_failed',
				sprintf(
					/* translators: %s: Error message. */
					__( 'AI analysis failed: %s', 'plugin-check' ),
					$e->getMessage()
				)
			);
		}
	}

	/**
	 * Builds the batched prompt following the internal scanner pattern.
	 *
	 * @since 2.0.0
	 *
	 * @param string        $issue_description Issue description from prompt template.
	 * @param array         $cases             Cases to analyze, keyed by case_id.
	 * @param Check_Context $check_context     Check context instance.
	 * @return string The complete prompt.
	 */
	protected function build_batch_prompt( $issue_description, array $cases, Check_Context $check_context ) {
		$prompt  = "You are an expert in WordPress security reviewing code for security, compatibility and performance.\n\n";
		$prompt .= "You are given several cases to analyze. Each case references code in a WordPress plugin.\n";
		$prompt .= "Do not trust on code comments to determine that something is not an issue.\n";
		$prompt .= "Look up the code, understand the context and determine if there is specifically an issue with the following:\n\n";

		$prompt .= $issue_description . "\n\n";

		$prompt .= "## Cases\n\n";

		foreach ( $cases as $case_id => $case ) {
			$location     = $case['file'] . ':' . $case['line'];
			$code_context = $this->get_code_context_for_case( $case, $check_context );

			$prompt .= '- Case ID ' . $case_id . ' : File and line "' . $location . '". ';
			$prompt .= 'Issue message: "' . $case['message'] . '"';

			if ( ! empty( $code_context ) ) {
				$prompt .= "\n  Code context:\n  ```\n" . $code_context . "\n  ```";
			}

			$prompt .= "\n\n";
		}

		$prompt .= "## Output\n\n";
		$prompt .= "Respond ONLY with valid JSON matching this structure:\n";
		$prompt .= "{\n";
		$prompt .= '  "cases": [' . "\n";
		$prompt .= "    {\n";
		$prompt .= '      "case_id": "the mentioned Case ID for each case",' . "\n";
		$prompt .= '      "issue": true if there is a genuine issue (false if it is a false positive),' . "\n";
		$prompt .= '      "short_explanation": "a very short explanation in one line"' . "\n";
		$prompt .= "    }\n";
		$prompt .= "  ]\n";
		$prompt .= "}\n";

		return $prompt;
	}

	/**
	 * Gets code context for a specific case.
	 *
	 * @since 2.0.0
	 *
	 * @param array         $issue_case    Case data with file, line, column.
	 * @param Check_Context $check_context Check context instance.
	 * @param int           $context_lines Number of lines before and after.
	 * @return string Code context or empty string.
	 */
	protected function get_code_context_for_case( array $issue_case, Check_Context $check_context, $context_lines = 10 ) {
		$file_path = $check_context->path( '/' ) . $issue_case['file'];

		if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
			return '';
		}

		$file_content = file_get_contents( $file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( empty( $file_content ) ) {
			return '';
		}

		return $this->get_code_context( $file_content, $issue_case['line'], $context_lines );
	}

	/**
	 * Gets code context around a specific line.
	 *
	 * @since 2.0.0
	 *
	 * @param string $file_content Full file content.
	 * @param int    $line         Line number (1-based).
	 * @param int    $context      Number of lines before and after.
	 * @return string Code context with line numbers.
	 */
	protected function get_code_context( $file_content, $line, $context = 10 ) {
		if ( empty( $file_content ) ) {
			return '';
		}

		$lines = explode( "\n", $file_content );
		$start = max( 0, $line - $context - 1 );
		$end   = min( count( $lines ), $line + $context );

		$context_lines = array();
		for ( $i = $start; $i < $end; $i++ ) {
			$line_num        = $i + 1;
			$marker          = ( $line_num === (int) $line ) ? ' >>>' : '    ';
			$context_lines[] = sprintf( '%s %4d | %s', $marker, $line_num, $lines[ $i ] );
		}

		return implode( "\n", $context_lines );
	}

	/**
	 * Parses the batched AI response into individual case results.
	 *
	 * @since 2.0.0
	 *
	 * @param string $response_text AI response text.
	 * @return array Array of case results.
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	protected function parse_batch_response( $response_text ) {
		if ( empty( $response_text ) ) {
			return array();
		}

		// Remove markdown code fences if present.
		$text = preg_replace( '/^```(?:json)?\s*\n?/m', '', $response_text );
		$text = preg_replace( '/\n?```\s*$/m', '', $text );
		$text = trim( $text );

		// Try to find JSON object in the response.
		$json_start = strpos( $text, '{' );
		$json_end   = strrpos( $text, '}' );

		if ( false === $json_start || false === $json_end || $json_end <= $json_start ) {
			return array();
		}

		$json_text = substr( $text, $json_start, $json_end - $json_start + 1 );
		$decoded   = json_decode( $json_text, true );

		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
			return array();
		}

		if ( ! isset( $decoded['cases'] ) || ! is_array( $decoded['cases'] ) ) {
			return array();
		}

		$results = array();
		foreach ( $decoded['cases'] as $case ) {
			if ( ! isset( $case['case_id'] ) ) {
				continue;
			}

			$results[] = array(
				'case_id'           => (string) $case['case_id'],
				'issue'             => isset( $case['issue'] ) ? (bool) $case['issue'] : true,
				'short_explanation' => isset( $case['short_explanation'] ) ? (string) $case['short_explanation'] : '',
			);
		}

		return $results;
	}

	/**
	 * Determines the prompt template filename for a given check code.
	 *
	 * @since 2.0.0
	 *
	 * @param string $code The check code (e.g., 'WordPress.Security.EscapeOutput.OutputNotEscaped').
	 * @return string Prompt template filename.
	 */
	protected function get_prompt_for_code( $code ) {
		foreach ( $this->get_ai_prompt_map() as $prefix => $prompt_file ) {
			if ( 0 === strpos( $code, $prefix ) ) {
				return $prompt_file;
			}
		}

		return 'ai-review-generic.md';
	}

	/**
	 * Loads a prompt template from the prompts/ directory.
	 *
	 * @since 2.0.0
	 *
	 * @param string $filename Prompt template filename.
	 * @return string|WP_Error Prompt content or WP_Error.
	 */
	protected function load_prompt_template( $filename ) {
		if ( ! defined( 'WP_PLUGIN_CHECK_PLUGIN_DIR_PATH' ) ) {
			return new WP_Error( 'plugin_constant_not_defined', __( 'Plugin constant not defined.', 'plugin-check' ) );
		}

		$path = WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'prompts/' . $filename;

		if ( ! file_exists( $path ) ) {
			return new WP_Error(
				'prompt_not_found',
				sprintf(
					/* translators: %s: Prompt filename. */
					__( 'AI prompt template not found: %s', 'plugin-check' ),
					$filename
				)
			);
		}

		$contents = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$contents = trim( $contents );

		if ( empty( $contents ) ) {
			return new WP_Error( 'prompt_empty', __( 'AI prompt template is empty.', 'plugin-check' ) );
		}

		return $contents;
	}

	/**
	 * Gets the AI severity threshold for a given type.
	 *
	 * @since 2.0.0
	 *
	 * @param string $type 'error' or 'warning'.
	 * @return int Severity threshold.
	 */
	protected function get_ai_severity_threshold( $type ) {
		if ( class_exists( Settings_Page::class ) ) {
			$default = 'error' === $type ? Settings_Page::get_severity_errors() : Settings_Page::get_severity_warnings();
		} else {
			$default = 'error' === $type ? 7 : 6;
		}

		/**
		 * Filters the AI severity threshold.
		 *
		 * @since 2.0.0
		 *
		 * @param int    $threshold Threshold from settings (7 for errors, 6 for warnings).
		 * @param string $type      'error' or 'warning'.
		 */
		return (int) apply_filters( 'wp_plugin_check_ai_severity_threshold', $default, $type );
	}

	/**
	 * Applies a model preference to the prompt builder.
	 *
	 * @since 2.0.0
	 *
	 * @param object $builder          Prompt builder instance.
	 * @param string $model_preference Model preference string.
	 * @return object|WP_Error Updated builder or WP_Error.
	 */
	protected function apply_ai_model_preference( $builder, $model_preference ) {
		if ( empty( $model_preference ) ) {
			return $builder;
		}

		$preference = trim( (string) $model_preference );

		// Parse provider::model format.
		foreach ( array( '::', '|', ':' ) as $separator ) {
			if ( false !== strpos( $preference, $separator ) ) {
				list( $provider, $model ) = array_map( 'trim', explode( $separator, $preference, 2 ) );
				if ( '' !== $provider && '' !== $model ) {
					$preference = array( $provider, $model );
					break;
				}
			}
		}

		try {
			$result = $builder->using_model_preference( $preference );
			return $result ? $result : $builder;
		} catch ( \Exception $e ) {
			return new WP_Error(
				'model_preference_error',
				sprintf(
					/* translators: %s: Exception message. */
					__( 'Failed to apply model preference: %s', 'plugin-check' ),
					$e->getMessage()
				)
			);
		}
	}

	/**
	 * Extracts token usage from a result object.
	 *
	 * @since 2.0.0
	 *
	 * @param object $result Result object.
	 * @return array|null Token usage array or null.
	 *
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	protected function extract_ai_token_usage( $result ) {
		$usage = null;

		if ( method_exists( $result, 'get_token_usage' ) ) {
			$usage = $result->get_token_usage();
		} elseif ( method_exists( $result, 'getTokenUsage' ) ) {
			$usage = $result->getTokenUsage();
		}

		if ( ! $usage || ! is_object( $usage ) ) {
			return null;
		}

		$prompt_tokens     = method_exists( $usage, 'get_prompt_tokens' ) ? $usage->get_prompt_tokens() : ( method_exists( $usage, 'getPromptTokens' ) ? $usage->getPromptTokens() : null );
		$prompt_tokens     = null === $prompt_tokens && method_exists( $usage, 'get_input_tokens' ) ? $usage->get_input_tokens() : $prompt_tokens;
		$prompt_tokens     = null === $prompt_tokens && method_exists( $usage, 'getInputTokens' ) ? $usage->getInputTokens() : $prompt_tokens;
		$completion_tokens = method_exists( $usage, 'get_completion_tokens' ) ? $usage->get_completion_tokens() : ( method_exists( $usage, 'getCompletionTokens' ) ? $usage->getCompletionTokens() : null );
		$completion_tokens = null === $completion_tokens && method_exists( $usage, 'get_output_tokens' ) ? $usage->get_output_tokens() : $completion_tokens;
		$completion_tokens = null === $completion_tokens && method_exists( $usage, 'getOutputTokens' ) ? $usage->getOutputTokens() : $completion_tokens;
		$total_tokens      = method_exists( $usage, 'get_total_tokens' ) ? $usage->get_total_tokens() : ( method_exists( $usage, 'getTotalTokens' ) ? $usage->getTotalTokens() : null );

		if ( null === $total_tokens && null !== $prompt_tokens && null !== $completion_tokens ) {
			$total_tokens = $prompt_tokens + $completion_tokens;
		}

		if ( null === $prompt_tokens && null === $completion_tokens && null === $total_tokens ) {
			return null;
		}

		return array_filter(
			array(
				'prompt_tokens'     => $prompt_tokens,
				'completion_tokens' => $completion_tokens,
				'total_tokens'      => $total_tokens,
			),
			static function ( $value ) {
				return null !== $value;
			}
		);
	}

	/**
	 * Extracts the model used from an AI result object.
	 *
	 * @since 2.0.0
	 *
	 * @param object $result Result object.
	 * @return string Model identifier or empty string.
	 */
	protected function extract_ai_model_used( $result ) {
		foreach ( array( 'get_model_metadata', 'getModelMetadata', 'get_model', 'getModel', 'get_model_id', 'getModelId', 'get_model_name', 'getModelName' ) as $method ) {
			if ( ! method_exists( $result, $method ) ) {
				continue;
			}

			$model = $result->$method();
			if ( is_string( $model ) && '' !== trim( $model ) ) {
				return trim( $model );
			}

			if ( is_object( $model ) ) {
				foreach ( array( 'get_id', 'getId', 'get_name', 'getName' ) as $model_method ) {
					if ( method_exists( $model, $model_method ) ) {
						$value = $model->$model_method();
						if ( is_string( $value ) && '' !== trim( $value ) ) {
							return trim( $value );
						}
					}
				}
			}
		}

		return '';
	}

	/**
	 * Extracts the provider used from an AI result object.
	 *
	 * @since 2.0.0
	 *
	 * @param object $result Result object.
	 * @return string Provider identifier or empty string.
	 */
	protected function extract_ai_provider_used( $result ) {
		foreach ( array( 'get_provider_metadata', 'getProviderMetadata', 'get_provider', 'getProvider', 'get_provider_id', 'getProviderId', 'get_provider_name', 'getProviderName' ) as $method ) {
			if ( ! method_exists( $result, $method ) ) {
				continue;
			}

			$provider = $result->$method();
			if ( is_string( $provider ) && '' !== trim( $provider ) ) {
				return trim( $provider );
			}

			if ( is_object( $provider ) ) {
				foreach ( array( 'get_id', 'getId', 'get_name', 'getName' ) as $provider_method ) {
					if ( method_exists( $provider, $provider_method ) ) {
						$value = $provider->$provider_method();
						if ( is_string( $value ) && '' !== trim( $value ) ) {
							return trim( $value );
						}
					}
				}
			}
		}

		return '';
	}

	/**
	 * Normalizes a configured model preference for display.
	 *
	 * @since 2.0.0
	 *
	 * @param string $model_preference Model preference.
	 * @return string Model identifier or empty string.
	 */
	protected function normalize_ai_model_used( $model_preference ) {
		$model_preference = trim( (string) $model_preference );
		if ( '' === $model_preference ) {
			return '';
		}

		foreach ( array( '::', '|', ':' ) as $separator ) {
			if ( false !== strpos( $model_preference, $separator ) ) {
				$parts = array_map( 'trim', explode( $separator, $model_preference, 2 ) );
				return isset( $parts[1] ) && '' !== $parts[1] ? $parts[1] : $model_preference;
			}
		}

		return $model_preference;
	}

	/**
	 * Normalizes a configured model preference provider for display.
	 *
	 * @since 2.0.0
	 *
	 * @param string $model_preference Model preference.
	 * @return string Provider identifier or empty string.
	 */
	protected function normalize_ai_provider_used( $model_preference ) {
		$model_preference = trim( (string) $model_preference );
		if ( '' === $model_preference ) {
			return '';
		}

		foreach ( array( '::', '|', ':' ) as $separator ) {
			if ( false !== strpos( $model_preference, $separator ) ) {
				$parts = array_map( 'trim', explode( $separator, $model_preference, 2 ) );
				return isset( $parts[0] ) && '' !== $parts[0] ? $parts[0] : '';
			}
		}

		return '';
	}

	/**
	 * Returns an empty AI result structure.
	 *
	 * @since 2.0.0
	 *
	 * @return array Empty result with zeroed stats.
	 */
	protected function empty_ai_result() {
		return array(
			'analysis' => array(),
			'stats'    => array(
				'tokens_spent'    => 0,
				'input_tokens'    => 0,
				'output_tokens'   => 0,
				'false_positives' => 0,
				'issues_analyzed' => 0,
				'model_used'      => '',
				'provider_used'   => '',
			),
		);
	}

	/**
	 * Generates a unique key for an issue.
	 *
	 * @since 2.0.0
	 *
	 * @param string $file   File path.
	 * @param int    $line   Line number.
	 * @param int    $column Column number.
	 * @param string $code   Issue code.
	 * @return string Unique key.
	 */
	protected function get_issue_key( $file, $line, $column, $code ) {
		return md5( $file . ':' . $line . ':' . $column . ':' . $code );
	}
}
