<?php
/**
 * Trait WordPress\Plugin_Check\Traits\AI_Utils
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Traits;

use WordPress\AiClient\AiClient;
use WP_Error;

/**
 * Trait for shared AI utilities (config, raw output, JSON formatting).
 *
 * @since 1.9.0
 */
trait AI_Utils {

	/**
	 * Checks AI prerequisites: feature flag, function availability, and version.
	 *
	 * @since 2.0.0
	 *
	 * @return true|WP_Error True if all prerequisites are met, WP_Error otherwise.
	 */
	protected function check_ai_prerequisites() {

		if ( function_exists( 'wp_supports_ai' ) && ! wp_supports_ai() ) {
			return new WP_Error(
				'ai_disabled',
				__( 'The Plugin Check Namer feature requires AI support to be enabled on this site. Please enable AI functionality to use this feature.', 'plugin-check' )
			);
		}

		if ( ! is_wp_version_compatible( '7.0' ) ) {
			return new WP_Error(
				'ai_client_not_available',
				sprintf(
					/* translators: %s: WordPress version. */
					__( 'The Plugin Check Namer Tool requires WordPress version 7.0 or higher. You are running WordPress version %s.', 'plugin-check' ),
					get_bloginfo( 'version' )
				)
			);
		}

		return true;
	}

	/**
	 * Checks that at least one AI connector is configured and active.
	 *
	 * @since 2.0.0
	 *
	 * @return true|WP_Error True if a connector is available, WP_Error otherwise.
	 */
	protected function check_ai_connectors() {
		if ( $this->has_no_active_ai_connectors() ) {
			return new WP_Error(
				'ai_not_configured',
				__( 'AI connectors are not configured. Please connect and enable an AI provider in WordPress 7.0+ settings.', 'plugin-check' )
			);
		}

		return true;
	}

	/**
	 * Gets AI configuration from core AI connectors.
	 *
	 * @since 1.9.0
	 *
	 * @param string $model_preference Selected model preference (optional).
	 * @return array|WP_Error AI config array or error.
	 */
	protected function get_ai_config( $model_preference = '' ) {

		$prerequisites = $this->check_ai_prerequisites();
		if ( is_wp_error( $prerequisites ) ) {
			return $prerequisites;
		}

		$connectors = $this->check_ai_connectors();
		if ( is_wp_error( $connectors ) ) {
			return $connectors;
		}

		if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
			return new WP_Error(
				'ai_client_not_available',
				sprintf(
					/* translators: %s: WordPress version. */
					__( 'The Plugin Check Namer Tool requires WordPress version 7.0 or newer. You are running WordPress version %s.', 'plugin-check' ),
					get_bloginfo( 'version' )
				)
			);
		}

		$builder = wp_ai_client_prompt( 'Plugin Check AI availability test.' );
		if ( is_wp_error( $builder ) ) {
			return $builder;
		}

		$builder = $this->apply_model_preference( $builder, $model_preference );
		if ( is_wp_error( $builder ) ) {
			return $builder;
		}

		if ( method_exists( $builder, 'is_supported_for_text_generation' ) ) {
			$supported = $builder->is_supported_for_text_generation();
			if ( is_wp_error( $supported ) ) {
				return $supported;
			}
			if ( ! $supported ) {
				return new WP_Error(
					'ai_not_configured',
					__( 'AI connectors are not configured. Please connect an AI provider in WordPress 7.0+ settings.', 'plugin-check' )
				);
			}
		}

		return array(
			'model_preference' => (string) $model_preference,
		);
	}

	/**
	 * Applies a model preference to the prompt builder if supported.
	 *
	 * @since 2.0.0
	 *
	 * @param object $builder          Prompt builder instance.
	 * @param string $model_preference Model preference.
	 * @return object|WP_Error Updated builder or WP_Error.
	 */
	protected function apply_model_preference( $builder, $model_preference ) {
		if ( empty( $model_preference ) ) {
			return $builder;
		}

		$preference = $this->normalize_model_preference( $model_preference );

		try {
			$result = $builder->using_model_preference( $preference );
			return $result ? $result : $builder;
		} catch ( \Exception $e ) {
			return new WP_Error(
				'model_preference_error',
				sprintf(
					/* translators: %s: Exception message */
					__( 'Failed to apply model preference: %s', 'plugin-check' ),
					$e->getMessage()
				)
			);
		}
	}

	/**
	 * Normalizes a model preference string into a supported preference format.
	 *
	 * @since 2.0.0
	 *
	 * @param string $model_preference Model preference string.
	 * @return string|array Normalized preference.
	 */
	protected function normalize_model_preference( $model_preference ) {
		$trimmed = trim( (string) $model_preference );
		if ( '' === $trimmed ) {
			return '';
		}

		foreach ( array( '::', '|', ':' ) as $separator ) {
			if ( false !== strpos( $trimmed, $separator ) ) {
				list( $provider, $model ) = array_map( 'trim', explode( $separator, $trimmed, 2 ) );
				if ( '' !== $provider && '' !== $model ) {
					return array( $provider, $model );
				}
			}
		}

		return $trimmed;
	}

	/**
	 * Gets raw output string from parsed result or analysis.
	 *
	 * @since 1.8.0
	 *
	 * @param array        $parsed   Parsed analysis.
	 * @param string|array $analysis Raw analysis.
	 * @return string Raw output.
	 */
	protected function get_raw_output( $parsed, $analysis ) {
		if ( ! empty( $parsed['raw'] ) ) {
			return $parsed['raw'];
		}

		if ( is_array( $analysis ) && isset( $analysis['text'] ) ) {
			return $analysis['text'];
		}

		if ( is_string( $analysis ) ) {
			return $analysis;
		}

		return '';
	}

	/**
	 * Formats JSON output with proper indentation if the text is valid JSON.
	 *
	 * @since 1.8.0
	 *
	 * @param string $text Text that might be JSON.
	 * @return string Formatted JSON or original text.
	 */
	protected function format_json_output( $text ) {
		if ( empty( $text ) || ! is_string( $text ) ) {
			return $text;
		}

		$trimmed = $this->remove_markdown_fences( trim( $text ) );

		if ( ! $this->looks_like_json( $trimmed ) ) {
			return $text;
		}

		$json_text = $this->extract_json_text( $trimmed );
		$decoded   = json_decode( $json_text, true );

		if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
			return wp_json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		}

		return $text;
	}

	/**
	 * Removes markdown code fences from text.
	 *
	 * @since 1.8.0
	 *
	 * @param string $text Text with possible markdown fences.
	 * @return string Text without markdown fences.
	 */
	protected function remove_markdown_fences( $text ) {
		$text = preg_replace( '/^```(?:json)?\s*\n?/m', '', $text );
		$text = preg_replace( '/\n?```\s*$/m', '', $text );
		return trim( $text );
	}

	/**
	 * Checks if text looks like JSON.
	 *
	 * @since 1.8.0
	 *
	 * @param string $text Text to check.
	 * @return bool True if looks like JSON.
	 */
	protected function looks_like_json( $text ) {
		return ! empty( $text ) && ( '{' === $text[0] || '[' === $text[0] );
	}

	/**
	 * Extracts JSON text from mixed content.
	 *
	 * @since 1.8.0
	 *
	 * @param string $text Text containing JSON.
	 * @return string Extracted JSON text.
	 */
	protected function extract_json_text( $text ) {
		$bounds = $this->find_json_bounds( $text );

		if ( -1 !== $bounds['start'] && -1 !== $bounds['end'] && $bounds['end'] > $bounds['start'] ) {
			return substr( $text, $bounds['start'], $bounds['end'] - $bounds['start'] + 1 );
		}

		return $text;
	}

	/**
	 * Finds JSON boundaries in text.
	 *
	 * @since 1.8.0
	 *
	 * @param string $text Text to search.
	 * @return array Array with 'start' and 'end' positions.
	 */
	protected function find_json_bounds( $text ) {
		$first_brace   = strpos( $text, '{' );
		$first_bracket = strpos( $text, '[' );

		if ( false !== $first_brace && ( false === $first_bracket || $first_brace < $first_bracket ) ) {
			return array(
				'start' => $first_brace,
				'end'   => strrpos( $text, '}' ),
			);
		}

		if ( false !== $first_bracket ) {
			return array(
				'start' => $first_bracket,
				'end'   => strrpos( $text, ']' ),
			);
		}

		return array(
			'start' => -1,
			'end'   => -1,
		);
	}

	/**
	 * Returns whether a model metadata object supports text for both input and output.
	 *
	 * Checks supported capabilities for text_generation, then inspects input/output
	 * modality options. Falls back to name-based inference when metadata is insufficient.
	 *
	 * @since 2.0.0
	 *
	 * @param object $model_meta Model metadata object.
	 * @return bool True when the model supports text input and text output.
	 */
	protected function supports_text_io_from_metadata( $model_meta ) {
		if ( ! is_object( $model_meta ) ) {
			return false;
		}

		// If capabilities metadata is present, require text_generation capability.
		if ( method_exists( $model_meta, 'getSupportedCapabilities' ) ) {
			$supported = $model_meta->getSupportedCapabilities();
			if ( is_array( $supported ) && ! $this->meta_has_text_generation_cap( $supported ) ) {
				return false;
			}
		}

		// Check input/output modality options for text support.
		if ( method_exists( $model_meta, 'getSupportedOptions' ) ) {
			$options = $model_meta->getSupportedOptions();
			if ( is_array( $options ) ) {
				$modality = $this->meta_get_modality_text_support( $options );
				if ( null !== $modality['input'] || null !== $modality['output'] ) {
					return (bool) $modality['input'] && (bool) $modality['output'];
				}
			}
		}

		return ! $this->meta_is_audio_model_by_name( $model_meta );
	}

	/**
	 * Returns whether a supported-values matrix contains a text modality.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $values Supported values from a SupportedOption (array of combinations).
	 * @return bool True when at least one item resolves to text.
	 */
	protected function modality_values_include_text( $values ) {
		if ( ! is_array( $values ) ) {
			return false;
		}

		foreach ( $values as $combination ) {
			if ( ! is_array( $combination ) ) {
				continue;
			}
			foreach ( $combination as $modality ) {
				$text = '';
				if ( is_string( $modality ) ) {
					$text = strtolower( $modality );
				} elseif ( is_object( $modality ) && 'text' === strtolower( (string) $modality ) ) {
					return true;
				}
				if ( 'text' === $text ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Returns whether a capabilities array contains text_generation.
	 *
	 * @since 2.0.0
	 *
	 * @param array $supported Capabilities from getSupportedCapabilities().
	 * @return bool
	 */
	private function meta_has_text_generation_cap( array $supported ): bool {
		foreach ( $supported as $cap ) {
			if ( is_object( $cap ) && 'text_generation' === strtolower( (string) $cap ) ) {
				return true;
			}
			if ( is_string( $cap ) && 'text_generation' === strtolower( $cap ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns input/output text-modality support derived from getSupportedOptions().
	 *
	 * @since 2.0.0
	 *
	 * @param array $options Options from getSupportedOptions().
	 * @return array
	 */
	private function meta_get_modality_text_support( array $options ): array {
		$input_has_text  = null;
		$output_has_text = null;

		foreach ( $options as $option ) {
			if ( ! is_object( $option ) || ! method_exists( $option, 'getName' ) ) {
				continue;
			}
			$name      = $option->getName();
			$is_input  = false;
			$is_output = false;
			if ( is_object( $name ) ) {
				$raw       = strtolower( (string) $name );
				$is_input  = 'input_modalities' === $raw;
				$is_output = 'output_modalities' === $raw;
			} elseif ( is_string( $name ) ) {
				$raw       = strtolower( $name );
				$is_input  = 'inputmodalities' === $raw || 'input_modalities' === $raw;
				$is_output = 'outputmodalities' === $raw || 'output_modalities' === $raw;
			}
			if ( ( ! $is_input && ! $is_output ) || ! method_exists( $option, 'getSupportedValues' ) ) {
				continue;
			}
			$has_text = $this->modality_values_include_text( $option->getSupportedValues() );
			if ( $is_input ) {
				$input_has_text = $has_text;
			}
			if ( $is_output ) {
				$output_has_text = $has_text;
			}
		}

		return array(
			'input'  => $input_has_text,
			'output' => $output_has_text,
		);
	}

	/**
	 * Returns true when model name suggests audio-only (transcription / TTS / realtime).
	 *
	 * @since 2.0.0
	 *
	 * @param object $model_meta Model metadata object.
	 * @return bool
	 */
	private function meta_is_audio_model_by_name( $model_meta ): bool {
		if ( ! method_exists( $model_meta, 'getId' ) ) {
			return false;
		}
		$model = strtolower( (string) $model_meta->getId() );
		return false !== strpos( $model, 'transcribe' )
			|| false !== strpos( $model, 'tts' )
			|| false !== strpos( $model, 'realtime' );
	}

	/**
	 * Returns models from active/configured providers using the official AI Client registry flow.
	 *
	 * @since 1.9.0
	 *
	 * @return array
	 */
	protected function get_filtered_ai_models() {
		if ( ! class_exists( AiClient::class ) ) {
			return array();
		}

		$models = array();

		try {
			$registry = AiClient::defaultRegistry();

			foreach ( $registry->getRegisteredProviderIds() as $provider_id ) {
				if ( ! $registry->isProviderConfigured( $provider_id ) ) {
					continue;
				}

				$class_name    = $registry->getProviderClassName( $provider_id );
				$provider_meta = $class_name::metadata();

				foreach ( $class_name::modelMetadataDirectory()->listModelMetadata() as $model_meta ) {
					if ( ! $this->supports_text_io_from_metadata( $model_meta ) ) {
						continue;
					}

					$models[] = array(
						'provider'       => (string) $provider_id,
						'provider_label' => (string) $provider_meta->getName(),
						'id'             => (string) $model_meta->getId(),
						'label'          => (string) $model_meta->getName(),
					);
				}
			}
		} catch ( \Throwable $e ) {
			return array();
		}

		$models = apply_filters( 'plugin_check_ai_model_preferences', $models );
		return is_array( $models ) ? $models : array();
	}

	/**
	 * Returns whether there are no active AI connectors.
	 *
	 * @since 1.9.0
	 *
	 * @return bool
	 */
	protected function has_no_active_ai_connectors() {
		$models = $this->get_filtered_ai_models();
		return empty( $models );
	}

	/**
	 * Gets model preference from the current request.
	 *
	 * @since 1.9.0
	 *
	 * @return string Model preference.
	 */
	protected function get_model_preference_from_request() {
		$model_preference = isset( $_POST['model_preference'] ) ? sanitize_text_field( wp_unslash( $_POST['model_preference'] ) ) : '';
		return trim( (string) $model_preference );
	}

	/**
	 * Gets available model preferences from AI connectors.
	 *
	 * @since 1.9.0
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 *
	 * @return array Map of provider label => list of model options.
	 */
	protected function get_available_model_preferences() {
		$grouped = array();
		$models  = $this->get_filtered_ai_models();

		if ( is_array( $models ) ) {
			foreach ( $models as $key => $model ) {
				if ( is_array( $model ) ) {
					$provider       = isset( $model['provider'] ) ? (string) $model['provider'] : '';
					$provider_label = isset( $model['provider_label'] ) ? (string) $model['provider_label'] : '';
					$id             = isset( $model['id'] ) ? (string) $model['id'] : ( isset( $model['model'] ) ? (string) $model['model'] : '' );
					$label          = isset( $model['label'] ) ? (string) $model['label'] : '';

					$value = '';
					if ( '' !== $provider && '' !== $id ) {
						$value = $provider . '::' . $id;
					} elseif ( '' !== $id ) {
						$value = $id;
					}

					if ( '' === $label ) {
						$label = '' !== $provider ? $provider . ' / ' . $id : $id;
					}

					if ( '' !== $value ) {
						$group_label = '' !== $provider_label ? $provider_label : ( '' !== $provider ? $provider : __( 'Other', 'plugin-check' ) );
						if ( ! isset( $grouped[ $group_label ] ) ) {
							$grouped[ $group_label ] = array();
						}
						$grouped[ $group_label ][] = array(
							'value' => $value,
							'label' => $label,
						);
					}

					continue;
				}

				$model_id = is_string( $model ) ? $model : ( is_string( $key ) ? $key : '' );
				if ( '' === $model_id ) {
					continue;
				}

				$group_label = __( 'Other', 'plugin-check' );
				if ( ! isset( $grouped[ $group_label ] ) ) {
					$grouped[ $group_label ] = array();
				}
				$grouped[ $group_label ][] = array(
					'value' => $model_id,
					'label' => $model_id,
				);
			}
		}

		return $grouped;
	}
}
