<?php
/**
 * Trait WordPress\Plugin_Check\Traits\AI_Check_Names
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Traits;

use WP_Error;

/**
 * Trait for the Plugin Check Namer tool logic.
 *
 * @since 1.8.0
 */
trait AI_Check_Names {

	/**
	 * Runs the name analysis via AI (makes two queries like internal scanner).
	 *
	 * @since 1.8.0
	 *
	 * @param string $model_preference Model preference (optional).
	 * @param string $name             Plugin name to evaluate.
	 * @param string $author           Optional author/brand name.
	 * @return array|WP_Error Array with 'text' and 'token_usage' keys, or WP_Error.
	 */
	protected function run_name_analysis( $model_preference, $name, $author = '' ) {
		$directory_matches = $this->query_wordpress_org_directory( $name );

		// First query: Similar name search.
		$similar_name_result = $this->run_similar_name_query( $model_preference, $name );
		if ( is_wp_error( $similar_name_result ) ) {
			return $similar_name_result;
		}

		// Build additional context from similar name results and live directory matches.
		$additional_context = $this->build_similar_name_context( $similar_name_result['text'], $directory_matches );

		// Second query: Pre-review with similar name results as context.
		$prereview_result = $this->run_prereview_query( $model_preference, $name, $additional_context, $author );
		if ( is_wp_error( $prereview_result ) ) {
			return $prereview_result;
		}

		/*
		 * Workaround: Merge live directory matches from the WordPress.org Plugin Directory API with the AI
		 * similar_name output so that known existing plugins are reliably presented in the UI and pre-review context.
		 */
		$prereview_result['token_usage']['similar_name'] = $similar_name_result['token_usage'];

		$similar_data = $this->parse_json_response( $similar_name_result['text'] );
		$ai_plugins   = ! empty( $similar_data['confusion_existing_plugins'] ) && is_array( $similar_data['confusion_existing_plugins'] ) ? $similar_data['confusion_existing_plugins'] : array();
		$ai_others    = ! empty( $similar_data['confusion_existing_others'] ) && is_array( $similar_data['confusion_existing_others'] ) ? $similar_data['confusion_existing_others'] : array();

		$prereview_result['confusion_existing_plugins'] = array_merge( $directory_matches, $ai_plugins );
		$prereview_result['confusion_existing_others']  = $ai_others;

		return $prereview_result;
	}

	/**
	 * Programmatically queries the WordPress.org Plugin Directory API for existing plugins with similar or exact names and slugs.
	 *
	 * @since 1.10.0
	 *
	 * @param string $name Plugin name to check.
	 * @return array Array of matching existing plugins.
	 */
	protected function query_wordpress_org_directory( $name ) {
		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}

		$matches        = array();
		$candidate_slug = sanitize_title_with_dashes( $name );
		$slug_parts     = explode( '-', $candidate_slug );
		$slugs_to_check = array_unique(
			array_filter(
				array(
					$candidate_slug,
					implode( '-', array_slice( $slug_parts, 0, 1 ) ),
					implode( '-', array_slice( $slug_parts, 0, 2 ) ),
					implode( '-', array_slice( $slug_parts, 0, 3 ) ),
				)
			)
		);

		/*
		 * Workaround: Since AI models may not reliably detect exact or near-exact existing plugin names/slugs
		 * from training data alone, programmatically query the WordPress.org Plugin Directory API (`plugins_api`)
		 * for exact candidate slugs and top search matches.
		 */
		$this->check_directory_slug_matches( $name, $candidate_slug, $slugs_to_check, $matches );
		$this->check_directory_search_matches( $name, $candidate_slug, $matches );

		return array_values( $matches );
	}

	/**
	 * Checks candidate slugs against the WordPress.org Plugin Directory API (`plugins_api`).
	 *
	 * @since 1.10.0
	 *
	 * @param string $name           Plugin name to check.
	 * @param string $candidate_slug Base candidate slug.
	 * @param array  $slugs_to_check List of slug strings to query.
	 * @param array  $matches        Reference to matching plugins array.
	 */
	protected function check_directory_slug_matches( $name, $candidate_slug, $slugs_to_check, &$matches ) {
		foreach ( $slugs_to_check as $slug ) {
			$info = plugins_api( 'plugin_information', array( 'slug' => $slug ) );
			if ( is_wp_error( $info ) || empty( $info ) ) {
				continue;
			}

			$info_slug = $this->get_item_property( $info, 'slug' );
			$info_name = $this->get_item_property( $info, 'name' );

			if ( empty( $info_slug ) || empty( $info_name ) ) {
				continue;
			}

			$is_exact              = $this->is_directory_item_exact_match( $info_slug, $info_name, $candidate_slug, $name );
			$matches[ $info_slug ] = array(
				'name'                 => html_entity_decode( $info_name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
				'similarity_level'     => $is_exact ? 'Exact Match' : 'High',
				'explanation'          => __( 'Existing plugin found directly in the WordPress.org Plugin Directory.', 'plugin-check' ),
				'active_installations' => $this->get_item_property( $info, 'active_installs', '0' ),
				'link'                 => 'https://wordpress.org/plugins/' . $info_slug . '/',
				'is_exact_match'       => $is_exact,
			);
		}
	}

	/**
	 * Checks search results against the WordPress.org Plugin Directory API (`plugins_api`).
	 *
	 * @since 1.10.0
	 *
	 * @param string $name           Plugin name to check.
	 * @param string $candidate_slug Base candidate slug.
	 * @param array  $matches        Reference to matching plugins array.
	 */
	protected function check_directory_search_matches( $name, $candidate_slug, &$matches ) {
		$search_queries = array( $name );
		$slug_parts     = explode( '-', $candidate_slug );
		$generic_words  = array( 'wp', 'wordpress', 'simple', 'easy', 'custom', 'plugin', 'advanced', 'pro', 'woo', 'ultimate', 'best', 'free', 'new', 'all', 'super', 'smart', 'fast', 'quick', 'contact', 'form', 'forms', 'image', 'video', 'post', 'posts', 'page', 'pages', 'user', 'users' );

		// Cap additional word queries to avoid excessive HTTP requests for long plugin names.
		$max_word_queries = 3;
		$word_count       = 0;

		foreach ( $slug_parts as $part ) {
			if ( $word_count >= $max_word_queries ) {
				break;
			}
			if ( strlen( $part ) >= 4 && ! in_array( $part, $generic_words, true ) && $part !== $candidate_slug ) {
				$search_queries[] = $part;
				++$word_count;
			}
		}

		foreach ( array_unique( $search_queries ) as $search_query ) {
			$search_results = plugins_api(
				'query_plugins',
				array(
					'search'   => $search_query,
					'per_page' => 5,
				)
			);

			if ( is_wp_error( $search_results ) || empty( $search_results->plugins ) || ! is_array( $search_results->plugins ) ) {
				continue;
			}

			foreach ( $search_results->plugins as $plugin ) {
				$p_slug = $this->get_item_property( $plugin, 'slug' );
				$p_name = $this->get_item_property( $plugin, 'name' );

				if ( empty( $p_slug ) || isset( $matches[ $p_slug ] ) ) {
					continue;
				}

				$is_exact           = $this->is_directory_item_exact_match( $p_slug, $p_name, $candidate_slug, $name );
				$matches[ $p_slug ] = array(
					'name'                 => html_entity_decode( $p_name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
					'similarity_level'     => $is_exact ? 'Exact Match' : 'High',
					'explanation'          => __( 'Similar plugin detected via WordPress.org directory search.', 'plugin-check' ),
					'active_installations' => $this->get_item_property( $plugin, 'active_installs', '0' ),
					'link'                 => 'https://wordpress.org/plugins/' . $p_slug . '/',
					'is_exact_match'       => $is_exact,
				);
			}
		}
	}

	/**
	 * Determines if a directory plugin item is an exact or near-exact match for the evaluated name/slug.
	 *
	 * @since 1.10.0
	 *
	 * @param string $info_slug      Slug of the directory item.
	 * @param string $info_name      Name of the directory item.
	 * @param string $candidate_slug Evaluated candidate slug.
	 * @param string $name           Evaluated plugin name.
	 * @return bool True if exact or near-exact match, false otherwise.
	 */
	protected function is_directory_item_exact_match( $info_slug, $info_name, $candidate_slug, $name ) {
		if ( empty( $info_slug ) || empty( $candidate_slug ) ) {
			return false;
		}

		// Same slug or same name (case-insensitive).
		if ( $info_slug === $candidate_slug || 0 === strcasecmp( trim( $info_name ), trim( $name ) ) ) {
			return true;
		}

		// Name normalizes to same slug (handles special characters like em-dashes).
		if ( 0 === strcasecmp( sanitize_title_with_dashes( $info_name ), $candidate_slug ) ) {
			return true;
		}

		// Dash-stripped comparison (e.g. "less-flux" vs "lessflux").
		return str_replace( '-', '', $info_slug ) === str_replace( '-', '', $candidate_slug );
	}

	/**
	 * Safely retrieves a scalar property or array element from an item.
	 *
	 * @since 1.10.0
	 *
	 * @param object|array $item          The object or array to retrieve from.
	 * @param string       $key           Property name or array key.
	 * @param string       $default_value Optional default value. Default empty string.
	 * @return string The string value of the property or element, or default value.
	 */
	protected function get_item_property( $item, $key, $default_value = '' ) {
		if ( is_object( $item ) && isset( $item->$key ) && is_scalar( $item->$key ) ) {
			return (string) $item->$key;
		}
		if ( is_array( $item ) && isset( $item[ $key ] ) && is_scalar( $item[ $key ] ) ) {
			return (string) $item[ $key ];
		}
		return $default_value;
	}

	/**
	 * Runs the similar name query (first query).
	 *
	 * @since 1.8.0
	 *
	 * @param string $model_preference Model preference (optional).
	 * @param string $name             Plugin name to evaluate.
	 * @return array|WP_Error Array with 'text' and 'token_usage' keys, or WP_Error.
	 */
	protected function run_similar_name_query( $model_preference, $name ) {
		$prompt_template = $this->get_prompt_template( 'ai-check-similar-name.md' );
		if ( is_wp_error( $prompt_template ) ) {
			return $prompt_template;
		}

		$prompt = $prompt_template . "\n\nPlugin name: {$name}\nPlugin description: (not provided)\n";

		// Execute AI request with structured output configuration.
		return $this->execute_ai_request(
			$prompt,
			$model_preference,
			function ( $builder ) {
				$this->maybe_set_structured_output( $builder, 'similar_name' );
			}
		);
	}

	/**
	 * Runs the pre-review query (second query).
	 *
	 * @since 1.8.0
	 *
	 * @param string $model_preference   Model preference (optional).
	 * @param string $name               Plugin name to evaluate.
	 * @param string $additional_context Additional context from similar name query.
	 * @param string $author             Optional author/brand name.
	 * @return array|WP_Error Array with 'text' and 'token_usage' keys, or WP_Error.
	 */
	protected function run_prereview_query( $model_preference, $name, $additional_context = '', $author = '' ) {
		$prompt_template = $this->get_prompt_template( 'ai-check-prereview.md' );
		if ( is_wp_error( $prompt_template ) ) {
			return $prompt_template;
		}

		$output_template = $this->get_prompt_template( 'ai-check-prereview-output.md' );
		if ( is_wp_error( $output_template ) ) {
			return $output_template;
		}

		// Combine developer prompt (system instructions).
		$developer_prompt = $prompt_template . "\n\n" . $output_template;

		// Build user prompt with plugin information.
		$user_prompt  = "# Plugin basic information\n\n";
		$user_prompt .= "- Display name for the plugin: {$name}\n";

		// Add author/brand name if provided.
		if ( ! empty( $author ) ) {
			$user_prompt .= "- Author/Brand name: {$author}\n";
			$user_prompt .= "\nNote: The author/brand name provided indicates that the submitter owns or represents this brand. If the plugin name matches or is related to this brand, do not suggest changing the plugin name unless there are other significant conflicts.\n";
		}

		// Add additional context from similar name query if available.
		if ( ! empty( $additional_context ) ) {
			$user_prompt .= "\n\n" . $additional_context;
		}

		// Combine both prompts for the AI call.
		$full_prompt = $developer_prompt . "\n\n---\n\n" . $user_prompt;

		// Execute AI request with structured output configuration.
		return $this->execute_ai_request(
			$full_prompt,
			$model_preference,
			function ( $builder ) {
				$this->maybe_set_structured_output( $builder, 'prereview' );
			}
		);
	}

	/**
	 * Executes an AI request with the provided parameters.
	 *
	 * @since 1.9.0
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 *
	 * @param string        $prompt           The prompt to send to the AI.
	 * @param string        $model_preference Model preference (optional).
	 * @param callable|null $builder_config   Optional callback to configure the prompt builder before execution.
	 * @return array|WP_Error Array with 'text' and optional 'token_usage', or WP_Error on failure.
	 */
	protected function execute_ai_request( $prompt, $model_preference = '', $builder_config = null ) {
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

		$builder = $this->apply_model_preference( $builder, $model_preference );
		if ( is_wp_error( $builder ) ) {
			return $builder;
		}

		if ( is_callable( $builder_config ) ) {
			call_user_func( $builder_config, $builder );
		}

		// Try to generate a rich result first.
		// Use is_callable() instead of method_exists() to detect methods
		// provided via __call() magic (e.g. WP_AI_Client_Prompt_Builder).
		$result = null;
		if ( is_callable( array( $builder, 'generate_text_result' ) ) ) {
			$result = $builder->generate_text_result();
		} elseif ( is_callable( array( $builder, 'generateTextResult' ) ) ) {
			$result = $builder->generateTextResult();
		}

		if ( $result ) {
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$text  = method_exists( $result, 'to_text' ) ? $result->to_text() : ( method_exists( $result, 'toText' ) ? $result->toText() : '' );
			$usage = $this->extract_token_usage( $result );

			return array_filter(
				array(
					'text'        => $text,
					'token_usage' => $usage,
				)
			);
		}

		$text = $builder->generate_text();
		if ( is_wp_error( $text ) ) {
			return $text;
		}

		return array(
			'text' => (string) $text,
		);
	}

	/**
	 * Extracts token usage from a result object, if available.
	 *
	 * @since 1.9.0
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 *
	 * @param object $result Result object.
	 * @return array|null Token usage array or null.
	 */
	protected function extract_token_usage( $result ) {
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
		$completion_tokens = method_exists( $usage, 'get_completion_tokens' ) ? $usage->get_completion_tokens() : ( method_exists( $usage, 'getCompletionTokens' ) ? $usage->getCompletionTokens() : null );
		$total_tokens      = method_exists( $usage, 'get_total_tokens' ) ? $usage->get_total_tokens() : ( method_exists( $usage, 'getTotalTokens' ) ? $usage->getTotalTokens() : null );

		// Compute total from prompt + completion if not directly available.
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
	 * Builds additional context from similar name results.
	 *
	 * @since 1.8.0
	 *
	 * @param string $similar_name_result Similar name query result.
	 * @param array  $directory_matches   Optional directory matches from plugins_api.
	 * @return string Additional context text.
	 */
	protected function build_similar_name_context( $similar_name_result, $directory_matches = array() ) {
		if ( empty( $similar_name_result ) && empty( $directory_matches ) ) {
			return '';
		}

		$context = "# Possible similarity to other plugins, trademarks and project names.\n\n";

		if ( ! empty( $directory_matches ) ) {
			$context .= "We have confirmed via WordPress.org Plugin Directory API that the following existing plugins ALREADY EXIST on WordPress.org:\n";
			foreach ( $directory_matches as $match ) {
				$context .= sprintf(
					"- %1\$s (slug: %2\$s, active installations: %3\$s, link: %4\$s)\n",
					$match['name'],
					basename( rtrim( $match['link'], '/' ) ),
					$match['active_installations'],
					$match['link']
				);
			}
			$context .= "\nIf the evaluated plugin name exactly matches or is nearly identical/confusingly similar to any of the above existing plugins or their slugs, you MUST set possible_naming_issues to true and explain the conflict in naming_explanation.\n\n";
		}

		if ( ! empty( $similar_name_result ) ) {
			$context .= "We've detected the following possible similarities. Check them and determine if there is a high similarity. This is not an exhaustive list. It is only the result of an internet search, so you need to check its validity for this case. Do not mention them in your reply.\n\n";
			$context .= $similar_name_result;
		}

		return $context;
	}

	/**
	 * Loads the AI prompt template.
	 *
	 * @since 1.8.0
	 *
	 * @param string $filename Optional filename to load. Default 'ai-check-similar-name.md'.
	 * @return string|WP_Error Prompt template or error.
	 */
	protected function get_prompt_template( $filename = 'ai-check-similar-name.md' ) {
		if ( ! defined( 'WP_PLUGIN_CHECK_PLUGIN_DIR_PATH' ) ) {
			return new WP_Error( 'plugin_constant_not_defined', __( 'Plugin constant not defined.', 'plugin-check' ) );
		}

		$path = WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'prompts/' . $filename;
		if ( ! file_exists( $path ) ) {
			return new WP_Error( 'prompt_not_found', __( 'Prompt template not found.', 'plugin-check' ) );
		}

		$contents = (string) file_get_contents( $path );
		$contents = trim( $contents );

		if ( empty( $contents ) ) {
			return new WP_Error( 'prompt_empty', __( 'Prompt template is empty.', 'plugin-check' ) );
		}

		return $contents;
	}

	/**
	 * Parses the analysis into a verdict and explanation.
	 *
	 * @since 1.8.0
	 *
	 * @param array|string $analysis AI output (array with 'text' and 'token_usage', or string for backward compat).
	 * @return array
	 */
	protected function parse_analysis( $analysis ) {
		// Extract text from array format (new format with token usage).
		$analysis_text = is_array( $analysis ) && isset( $analysis['text'] ) ? $analysis['text'] : $analysis;

		if ( empty( $analysis_text ) ) {
			return array(
				'verdict'     => '❓ ' . __( 'Empty Response', 'plugin-check' ),
				'explanation' => __( 'The AI did not return any analysis. Please try again.', 'plugin-check' ),
			);
		}

		$analysis_trim = trim( (string) $analysis_text );

		// Try parsing as JSON first (structured output format).
		$parsed_data = $this->parse_json_response( $analysis_trim );

		// If JSON parsing failed, try markdown format.
		if ( empty( $parsed_data ) || ! isset( $parsed_data['possible_naming_issues'] ) ) {
			$parsed_data = $this->parse_markdown_format( $analysis_trim );
		}

		if ( ! empty( $parsed_data ) && isset( $parsed_data['possible_naming_issues'] ) ) {
			$this->enforce_directory_match_verdict( $parsed_data, $analysis );

			$result = $this->parse_prereview_response( $parsed_data );

			// Add token usage info if available.
			if ( is_array( $analysis ) && isset( $analysis['token_usage'] ) ) {
				$result['token_usage'] = $analysis['token_usage'];
			}

			$this->attach_existing_matches_to_result( $result, $analysis );

			return $result;
		}

		// Unable to parse format.
		return array(
			'verdict'     => '❓ ' . __( 'Unable to Parse', 'plugin-check' ),
			'explanation' => wp_kses_post( __( 'The AI response could not be parsed. Raw response:', 'plugin-check' ) . '<br><br>' . esc_html( substr( $analysis_trim, 0, 500 ) ) ),
			'raw'         => $analysis_trim,
		);
	}

	/**
	 * Enforces naming issues if an exact plugin match was detected in the directory query.
	 *
	 * @since 1.10.0
	 *
	 * @param array        $parsed_data Reference to parsed response data array.
	 * @param array|string $analysis    Raw analysis input data.
	 */
	protected function enforce_directory_match_verdict( &$parsed_data, $analysis ) {
		if ( ! is_array( $analysis ) || empty( $analysis['confusion_existing_plugins'] ) || ! is_array( $analysis['confusion_existing_plugins'] ) ) {
			return;
		}

		foreach ( $analysis['confusion_existing_plugins'] as $plugin ) {
			if ( empty( $plugin['is_exact_match'] ) ) {
				continue;
			}

			$parsed_data['possible_naming_issues'] = true;
			if ( empty( $parsed_data['naming_explanation'] ) || false === strpos( (string) $parsed_data['naming_explanation'], 'WordPress.org Plugin Directory' ) ) {
				$link                              = isset( $plugin['link'] ) ? (string) $plugin['link'] : '';
				$parsed_data['naming_explanation'] = sprintf(
					/* translators: %s: plugin directory link */
					__( 'An existing plugin with an exact or nearly identical name/slug exists in the WordPress.org Plugin Directory (%s).', 'plugin-check' ),
					$link
				);
			}
			break;
		}
	}

	/**
	 * Attaches existing plugin and other confusion match data to the result array.
	 *
	 * @since 1.10.0
	 *
	 * @param array        $result   Reference to the pre-review result array.
	 * @param array|string $analysis Raw analysis input data.
	 */
	protected function attach_existing_matches_to_result( &$result, $analysis ) {
		if ( ! is_array( $analysis ) ) {
			return;
		}
		if ( isset( $analysis['confusion_existing_plugins'] ) ) {
			$result['confusion_existing_plugins'] = $analysis['confusion_existing_plugins'];
		}
		if ( isset( $analysis['confusion_existing_others'] ) ) {
			$result['confusion_existing_others'] = $analysis['confusion_existing_others'];
		}
	}

	/**
	 * Parses JSON response from AI.
	 *
	 * @since 1.8.0
	 *
	 * @param string $text AI response text.
	 * @return array Parsed data array or empty array if not valid JSON.
	 */
	protected function parse_json_response( $text ) {
		if ( empty( $text ) ) {
			return array();
		}

		$trimmed = trim( $text );

		// Remove markdown code fences if present.
		$trimmed = preg_replace( '/^```(?:json)?\s*\n?/m', '', $trimmed );
		$trimmed = preg_replace( '/\n?```\s*$/m', '', $trimmed );
		$trimmed = trim( $trimmed );

		// Try to find JSON object boundaries.
		$first_brace = strpos( $trimmed, '{' );
		if ( false !== $first_brace ) {
			$last_brace = strrpos( $trimmed, '}' );
			if ( false !== $last_brace && $last_brace > $first_brace ) {
				$json_text = substr( $trimmed, $first_brace, $last_brace - $first_brace + 1 );
			} else {
				$json_text = $trimmed;
			}
		} else {
			$json_text = $trimmed;
		}

		// Try to decode as JSON.
		$decoded = json_decode( $json_text, true );

		if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
			return $decoded;
		}

		return array();
	}

	/**
	 * Parses markdown/YAML-like format from AI response.
	 *
	 * Format: - key: value
	 *
	 * @since 1.8.0
	 *
	 * @param string $text AI response text.
	 * @return array Parsed data array.
	 */
	protected function parse_markdown_format( $text ) {
		$result = array();
		$lines  = explode( "\n", $text );

		foreach ( $lines as $line ) {
			$parsed = $this->parse_markdown_line( trim( $line ) );
			if ( null !== $parsed ) {
				$result[ $parsed['key'] ] = $parsed['value'];
			}
		}

		return $result;
	}

	/**
	 * Parses a single markdown line into key-value pair.
	 *
	 * @since 1.8.0
	 *
	 * @param string $line Line to parse.
	 * @return array|null Array with 'key' and 'value', or null if line should be skipped.
	 */
	protected function parse_markdown_line( $line ) {
		if ( empty( $line ) ) {
			return null;
		}

		$line      = ltrim( $line, '- ' );
		$colon_pos = strpos( $line, ':' );

		if ( false === $colon_pos ) {
			return null;
		}

		$key   = trim( substr( $line, 0, $colon_pos ) );
		$value = trim( substr( $line, $colon_pos + 1 ) );

		if ( empty( $key ) ) {
			return null;
		}

		return array(
			'key'   => $key,
			'value' => $this->parse_markdown_value( $key, $value ),
		);
	}

	/**
	 * Parses markdown value based on format.
	 *
	 * @since 1.8.0
	 *
	 * @param string $key   Field key.
	 * @param string $value Field value.
	 * @return mixed Parsed value (string, bool, or array).
	 */
	protected function parse_markdown_value( $key, $value ) {
		// Try JSON array.
		if ( 0 === strpos( $value, '[' ) ) {
			$decoded = json_decode( $value, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		// Parse booleans.
		$lower = strtolower( $value );
		if ( 'true' === $lower ) {
			return true;
		}
		if ( 'false' === $lower ) {
			return false;
		}

		// Parse comma-separated for disallowed_type.
		if ( 'disallowed_type' === $key && false !== strpos( $value, ',' ) ) {
			return array_map( 'trim', explode( ',', $value ) );
		}

		return $value;
	}

	/**
	 * Parses pre-review response format into user-friendly output.
	 *
	 * @since 1.8.0
	 *
	 * @param array $decoded Decoded JSON response.
	 * @return array{verdict:string,explanation:string,processed_data:array} Parsed result.
	 */
	protected function parse_prereview_response( $decoded ) {
		$verdict           = $this->build_verdict( $decoded );
		$explanation_parts = $this->build_explanation_parts( $decoded );
		$explanation       = ! empty( $explanation_parts ) ? implode( '<br><br>', $explanation_parts ) : __( 'No detailed analysis available.', 'plugin-check' );

		return array(
			'verdict'        => $verdict,
			'explanation'    => wp_kses_post( $explanation ),
			'processed_data' => $decoded,
		);
	}

	/**
	 * Builds verdict from decoded data.
	 *
	 * @since 1.8.0
	 *
	 * @param array $decoded Decoded data.
	 * @return string Verdict string.
	 */
	protected function build_verdict( $decoded ) {
		$issues        = $this->collect_issues( $decoded );
		$is_disallowed = ! empty( $decoded['disallowed'] );

		if ( $is_disallowed ) {
			return '❌ ' . __( 'Disallowed', 'plugin-check' );
		}

		if ( ! empty( $issues ) ) {
			return '⚠️ ' . __( 'Issues Found', 'plugin-check' ) . ': ' . implode( ', ', $issues );
		}

		// Check for suggestions, trademarks, or other indicators that suggest it's not clearly OK.
		$has_suggestions = ! empty( $decoded['suggested_display_name'] ) || ! empty( $decoded['suggested_slug'] );
		$has_trademarks  = ! empty( $decoded['trademarks_or_project_names_array'] ) && is_array( $decoded['trademarks_or_project_names_array'] ) && count( $decoded['trademarks_or_project_names_array'] ) > 0;

		if ( $has_suggestions || $has_trademarks ) {
			return 'ℹ️ ' . __( 'Generally Allowable', 'plugin-check' );
		}

		return '✅ ' . __( 'No Issues Detected', 'plugin-check' );
	}

	/**
	 * Collects issues from decoded data.
	 *
	 * @since 1.8.0
	 *
	 * @param array $decoded Decoded data.
	 * @return array List of issues.
	 */
	protected function collect_issues( $decoded ) {
		$issues = array();

		if ( ! empty( $decoded['possible_naming_issues'] ) ) {
			$issues[] = __( 'Naming', 'plugin-check' );
		}
		if ( ! empty( $decoded['possible_owner_issues'] ) ) {
			$issues[] = __( 'Owner/Trademark', 'plugin-check' );
		}
		if ( ! empty( $decoded['possible_description_issues'] ) ) {
			$issues[] = __( 'Description', 'plugin-check' );
		}

		return $issues;
	}

	/**
	 * Builds explanation parts from decoded data.
	 *
	 * @since 1.8.0
	 *
	 * @param array $decoded Decoded data.
	 * @return array Explanation parts.
	 */
	protected function build_explanation_parts( $decoded ) {
		$parts = array();

		$this->add_disallowed_section( $parts, $decoded );
		$this->add_naming_section( $parts, $decoded );
		$this->add_owner_section( $parts, $decoded );
		$this->add_description_section( $parts, $decoded );
		$this->add_trademarks_section( $parts, $decoded );
		$this->add_suggestions_section( $parts, $decoded );
		$this->add_language_section( $parts, $decoded );

		return $parts;
	}

	/**
	 * Adds disallowed section to explanation parts.
	 *
	 * @since 1.8.0
	 *
	 * @param array $parts   Explanation parts array (passed by reference).
	 * @param array $decoded Decoded data.
	 * @return void
	 */
	protected function add_disallowed_section( &$parts, $decoded ) {
		if ( empty( $decoded['disallowed'] ) ) {
			return;
		}

		$text = '';
		if ( ! empty( $decoded['disallowed_explanation'] ) ) {
			$text .= $decoded['disallowed_explanation'];
		}
		if ( ! empty( $decoded['disallowed_type'] ) && is_array( $decoded['disallowed_type'] ) ) {
			$text .= ' (' . implode( ', ', $decoded['disallowed_type'] ) . ')';
		}
		if ( ! empty( $text ) ) {
			$parts[] = '<strong>🚫 ' . esc_html__( 'Disallowed:', 'plugin-check' ) . '</strong> ' . $text;
		}
	}

	/**
	 * Adds naming section to explanation parts.
	 *
	 * @since 1.8.0
	 *
	 * @param array $parts   Explanation parts array (passed by reference).
	 * @param array $decoded Decoded data.
	 * @return void
	 */
	protected function add_naming_section( &$parts, $decoded ) {
		if ( ! empty( $decoded['possible_naming_issues'] ) && ! empty( $decoded['naming_explanation'] ) ) {
			$parts[] = '<strong>📝 ' . esc_html__( 'Naming:', 'plugin-check' ) . '</strong> ' . $decoded['naming_explanation'];
		}
	}

	/**
	 * Adds owner/trademark section to explanation parts.
	 *
	 * @since 1.8.0
	 *
	 * @param array $parts   Explanation parts array (passed by reference).
	 * @param array $decoded Decoded data.
	 * @return void
	 */
	protected function add_owner_section( &$parts, $decoded ) {
		if ( ! empty( $decoded['possible_owner_issues'] ) && ! empty( $decoded['owner_explanation'] ) ) {
			$parts[] = '<strong>©️ ' . esc_html__( 'Owner/Trademark:', 'plugin-check' ) . '</strong> ' . $decoded['owner_explanation'];
		}
	}

	/**
	 * Adds description section to explanation parts.
	 *
	 * @since 1.8.0
	 *
	 * @param array $parts   Explanation parts array (passed by reference).
	 * @param array $decoded Decoded data.
	 * @return void
	 */
	protected function add_description_section( &$parts, $decoded ) {
		if ( ! empty( $decoded['possible_description_issues'] ) && ! empty( $decoded['description_explanation'] ) ) {
			$parts[] = '<strong>📄 ' . esc_html__( 'Description:', 'plugin-check' ) . '</strong> ' . $decoded['description_explanation'];
		}
	}

	/**
	 * Adds trademarks section to explanation parts.
	 *
	 * @since 1.8.0
	 *
	 * @param array $parts   Explanation parts array (passed by reference).
	 * @param array $decoded Decoded data.
	 * @return void
	 */
	protected function add_trademarks_section( &$parts, $decoded ) {
		if ( ! empty( $decoded['trademarks_or_project_names_array'] ) && is_array( $decoded['trademarks_or_project_names_array'] ) ) {
			$trademarks = implode( ', ', array_map( 'esc_html', $decoded['trademarks_or_project_names_array'] ) );
			$parts[]    = '<strong>™️ ' . esc_html__( 'Trademarks Detected:', 'plugin-check' ) . '</strong> ' . $trademarks;
		}
	}

	/**
	 * Adds suggestions section to explanation parts.
	 *
	 * @since 1.8.0
	 *
	 * @param array $parts   Explanation parts array (passed by reference).
	 * @param array $decoded Decoded data.
	 * @return void
	 */
	protected function add_suggestions_section( &$parts, $decoded ) {
		$suggestions = array();

		if ( ! empty( $decoded['suggested_display_name'] ) ) {
			$suggestions[] = '<strong>' . __( 'Display Name:', 'plugin-check' ) . '</strong> ' . esc_html( $decoded['suggested_display_name'] );
		}
		if ( ! empty( $decoded['suggested_slug'] ) ) {
			$suggestions[] = '<strong>' . __( 'Slug:', 'plugin-check' ) . '</strong> ' . esc_html( $decoded['suggested_slug'] );
		}
		if ( ! empty( $decoded['short_description'] ) ) {
			$suggestions[] = '<strong>' . __( 'Description:', 'plugin-check' ) . '</strong> ' . esc_html( $decoded['short_description'] );
		}
		if ( ! empty( $decoded['plugin_category'] ) ) {
			$suggestions[] = '<strong>' . __( 'Category:', 'plugin-check' ) . '</strong> ' . esc_html( $decoded['plugin_category'] );
		}

		if ( ! empty( $suggestions ) ) {
			$parts[] = '<br><strong>💡 ' . esc_html__( 'Suggestions:', 'plugin-check' ) . '</strong><br>' . implode( '<br>', $suggestions );
		}
	}

	/**
	 * Adds language section to explanation parts.
	 *
	 * @since 1.8.0
	 *
	 * @param array $parts   Explanation parts array (passed by reference).
	 * @param array $decoded Decoded data.
	 * @return void
	 */
	protected function add_language_section( &$parts, $decoded ) {
		if ( isset( $decoded['description_language_is_in_english'] ) && false === $decoded['description_language_is_in_english'] ) {
			if ( ! empty( $decoded['description_what_is_not_in_english'] ) ) {
				$parts[] = '<strong>🌐 ' . esc_html__( 'Language:', 'plugin-check' ) . '</strong> ' . $decoded['description_what_is_not_in_english'];
			}
		}
	}

	/**
	 * Attempts to set structured output on the builder if supported.
	 *
	 * @since 1.8.0
	 *
	 * @param object $builder The prompt builder instance.
	 * @param string $query_type Type of query: 'similar_name' or 'prereview'.
	 * @return void
	 */
	protected function maybe_set_structured_output( $builder, $query_type = 'similar_name' ) {
		// Define the JSON schema based on query type.
		if ( 'prereview' === $query_type ) {
			$json_schema = $this->get_prereview_schema();
		} else {
			$json_schema = $this->get_similar_name_schema();
		}

		// Try different method names that might be used for structured output.
		$methods = array(
			'withStructuredOutput',
			'setResponseFormat',
			'usingResponseFormat',
			'withJsonSchema',
			'with_structured_output',
			'set_response_format',
			'using_response_format',
			'with_json_schema',
		);

		foreach ( $methods as $method ) {
			if ( method_exists( $builder, $method ) ) {
				call_user_func( array( $builder, $method ), $json_schema );
				break;
			}
		}

		// Try setting response format as a property if it exists.
		// Note: Using reflection to set property as it may not be public.
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( property_exists( $builder, 'responseFormat' ) || property_exists( $builder, 'response_format' ) ) {
			$prop_name = property_exists( $builder, 'responseFormat' ) ? 'responseFormat' : 'response_format';
			try {
				$reflection = new \ReflectionClass( $builder );
				$property   = $reflection->getProperty( $prop_name );
				$property->setAccessible( true );
				$property->setValue(
					$builder,
					array(
						'type'   => 'json_schema',
						'schema' => $json_schema,
					)
				);
			} catch ( \Exception $e ) {
				// If reflection fails, try direct assignment.
				if ( property_exists( $builder, $prop_name ) ) {
					$builder->$prop_name = array(
						'type'   => 'json_schema',
						'schema' => $json_schema,
					);
				}
			}
		}
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * Gets the JSON schema for similar name query.
	 *
	 * @since 1.8.0
	 *
	 * @return array JSON schema array.
	 */
	protected function get_similar_name_schema() {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'name_similarity_percentage' => array( 'type' => 'number' ),
				'similarity_explanation'     => array( 'type' => 'string' ),
				'confusion_existing_plugins' => array(
					'type'  => 'array',
					'items' => array(
						'type'                 => 'object',
						'properties'           => array(
							'name'                 => array( 'type' => 'string' ),
							'similarity_level'     => array( 'type' => 'string' ),
							'explanation'          => array( 'type' => 'string' ),
							'active_installations' => array( 'type' => 'string' ),
							'link'                 => array( 'type' => 'string' ),
						),
						'required'             => array( 'name', 'similarity_level', 'explanation', 'active_installations', 'link' ),
						'additionalProperties' => false,
					),
				),
				'confusion_existing_others'  => array(
					'type'  => 'array',
					'items' => array(
						'type'                 => 'object',
						'properties'           => array(
							'name'             => array( 'type' => 'string' ),
							'similarity_level' => array( 'type' => 'string' ),
							'explanation'      => array( 'type' => 'string' ),
							'link'             => array( 'type' => 'string' ),
						),
						'required'             => array( 'name', 'similarity_level', 'explanation', 'link' ),
						'additionalProperties' => false,
					),
				),
			),
			'required'             => array(
				'name_similarity_percentage',
				'similarity_explanation',
				'confusion_existing_plugins',
				'confusion_existing_others',
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Gets the JSON schema for pre-review query.
	 *
	 * @since 1.8.0
	 *
	 * @return array JSON schema array.
	 */
	protected function get_prereview_schema() {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'possible_naming_issues'            => array( 'type' => 'boolean' ),
				'naming_explanation'                => array( 'type' => 'string' ),
				'disallowed'                        => array( 'type' => 'boolean' ),
				'disallowed_explanation'            => array( 'type' => 'string' ),
				'disallowed_type'                   => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'string',
					),
				),
				'trademarks_or_project_names_array' => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'string',
					),
				),
				'suggested_display_name'            => array( 'type' => 'string' ),
				'suggested_slug'                    => array( 'type' => 'string' ),
			),
			'required'             => array(
				'possible_naming_issues',
				'naming_explanation',
				'disallowed',
				'disallowed_explanation',
				'disallowed_type',
				'trademarks_or_project_names_array',
				'suggested_display_name',
				'suggested_slug',
				'short_description',
				'plugin_category',
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Stores a transient result.
	 *
	 * @since 1.8.0
	 *
	 * @param int   $user_id User ID.
	 * @param array $data    Result data.
	 */
	protected function store_result( $user_id, $data ) {
		set_transient( $this->get_result_transient_key( $user_id ), $data, 10 * MINUTE_IN_SECONDS );
	}

	/**
	 * Gets the transient key.
	 *
	 * @since 1.8.0
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	protected function get_result_transient_key( $user_id ) {
		return 'plugin_check_namer_result_' . (int) $user_id;
	}
}
