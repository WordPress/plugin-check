<?php
/**
 * WriteFileSniff
 *
 * Based on code from {@link https://github.com/WordPress/WordPress-Coding-Standards}
 * which is licensed under {@link https://opensource.org/licenses/MIT}.
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Sniffs\CodeAnalysis;

use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\PassedParameters;
use WordPressCS\WordPress\AbstractFunctionParameterSniff;

/**
 * Verifies plugins don't save data in the plugin folder.
 *
 * Plugin folders are deleted when upgraded, so using them to store any data is problematic.
 * Plugins should use the uploads directory or the database instead.
 *
 * @link https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
 *
 * @since 2.0.0
 */
final class WriteFileSniff extends AbstractFunctionParameterSniff {

	/**
	 * The group name for this group of functions.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	protected $group_name = 'file_write';

	/**
	 * List of file write functions that need to be checked.
	 *
	 * @since 1.1.0
	 *
	 * @var array<string, true> Key is function name, value irrelevant.
	 */
	protected $target_functions = array(
		// File write functions - check first parameter (file path).
		'fwrite'            => true,
		'fputs'             => true,
		'file_put_contents' => true,
		'touch'             => true,
		// File copy/move functions - check second parameter (destination path).
		'copy'              => true,
		'rename'            => true,
		'copy_dir'          => true,
		'move_dir'          => true,
		'unzip_file'        => true,
	);

	/**
	 * Parameter positions for each function.
	 *
	 * @var array<string, int>
	 */
	private $param_positions = array(
		'fwrite'            => 1,
		'fputs'             => 1,
		'file_put_contents' => 1,
		'touch'             => 1,
		'copy'              => 2,
		'rename'            => 2,
		'copy_dir'          => 2,
		'move_dir'          => 2,
		'unzip_file'        => 2,
	);

	/**
	 * WordPress constants that indicate plugin directory usage.
	 *
	 * @var array
	 */
	private $plugin_constants = array(
		'WP_PLUGIN_DIR',
		'WP_PLUGIN_URL',
		'PLUGINDIR',
		'WPINC',
		'WP_CONTENT_DIR',
		'WP_CONTENT_URL',
	);

	/**
	 * WordPress functions that indicate plugin directory usage.
	 *
	 * @var array
	 */
	private $plugin_functions = array(
		'plugins_url',
		'plugin_dir_path',
		'plugin_dir_url',
	);

	/**
	 * WordPress functions that indicate safe directory usage (uploads, temp).
	 *
	 * @var array
	 */
	private $safe_functions = array(
		'wp_upload_dir',
		'wp_tempnam',
		'get_temp_dir',
	);

	/**
	 * Process the parameters of a matched function.
	 *
	 * @since 1.1.0
	 *
	 * @param int    $stackPtr        The position of the current token in the stack.
	 * @param string $group_name      The name of the group which was matched.
	 * @param string $matched_content The token content (function name) which was matched in lowercase.
	 * @param array  $parameters      Array with information about the parameters.
	 *
	 * @return void
	 */
	public function process_parameters( $stackPtr, $group_name, $matched_content, $parameters ) {
		$param_position = isset( $this->param_positions[ $matched_content ] ) ? $this->param_positions[ $matched_content ] : 1;
		$path_param     = PassedParameters::getParameterFromStack( $parameters, $param_position, array() );

		if ( false === $path_param ) {
			return;
		}

		$path_content = $this->get_path_content_with_resolved_variables( $path_param['start'], $path_param['end'], $stackPtr );

		// Check for plugin constants.
		foreach ( $this->plugin_constants as $constant ) {
			if ( stripos( $path_content, $constant ) !== false ) {
				if ( in_array( $constant, array( 'WP_CONTENT_DIR', 'WP_CONTENT_URL' ), true ) && $this->is_uploads_subpath( $path_content ) ) {
					return;
				}

				$this->add_error( $matched_content, $path_param['start'], 'constant ' . $constant );
				return;
			}
		}

		// Check for plugin functions.
		foreach ( $this->plugin_functions as $func ) {
			if ( stripos( $path_content, $func ) !== false ) {
				$this->add_error( $matched_content, $path_param['start'], 'function ' . $func . '()' );
				return;
			}
		}

		// Check for __FILE__ or __DIR__ magic constants.
		if ( stripos( $path_content, '__FILE__' ) !== false || stripos( $path_content, '__DIR__' ) !== false ) {
			$this->add_error( $matched_content, $path_param['start'], '__FILE__ or __DIR__ magic constant' );
			return;
		}

		// Check if the path uses safe functions (uploads directory or temp).
		foreach ( $this->safe_functions as $safe_func ) {
			if ( stripos( $path_content, $safe_func ) !== false ) {
				// Safe function detected, no error needed.
				return;
			}
		}

		// Check for ABSPATH usage (could be writing to WordPress root or plugin folder).
		if ( stripos( $path_content, 'ABSPATH' ) !== false ) {
			$this->phpcsFile->addWarning(
				'Writing files using ABSPATH may be problematic. Consider using wp_upload_dir() instead if storing user data or generated files.',
				$path_param['start'],
				'ABSPATHDetected'
			);
			return;
		}
	}

	/**
	 * Adds an error message for plugin directory write attempt.
	 *
	 * @param string $function_name   The name of the function being called.
	 * @param int    $error_ptr       The position to report the error.
	 * @param string $indicator       What indicated this is a plugin path.
	 *
	 * @return void
	 */
	private function add_error( $function_name, $error_ptr, $indicator ) {
		$this->phpcsFile->addError(
			'Plugin folders are deleted when upgraded. Do not save data to the plugin folder using %s(). Detected usage of %s. Use wp_upload_dir() to get the uploads directory path or save to the database instead.',
			$error_ptr,
			'PluginDirectoryWrite',
			array( $function_name, $indicator )
		);
	}

	/**
	 * Gets a normalized path expression and expands simple variable assignments.
	 *
	 * @param int $start_ptr Start pointer.
	 * @param int $end_ptr End pointer.
	 * @param int $stackPtr Function call pointer.
	 * @return string
	 */
	private function get_path_content_with_resolved_variables( $start_ptr, $end_ptr, $stackPtr ) {
		$path_content = '';
		for ( $i = $start_ptr; $i <= $end_ptr; $i++ ) {
			if ( isset( Tokens::$emptyTokens[ $this->tokens[ $i ]['code'] ] ) ) {
				continue;
			}
			$path_content .= $this->tokens[ $i ]['content'];
		}

		return $this->expand_expression_variables( $path_content, $stackPtr, array() );
	}

	/**
	 * Expands variables in an expression from assignments in the same scope.
	 *
	 * @param string $expression Path expression.
	 * @param int    $stackPtr Function call pointer.
	 * @param array  $visited_variables Visited variables map.
	 * @return string
	 */
	private function expand_expression_variables( $expression, $stackPtr, array $visited_variables ) {
		if ( false === strpos( $expression, '$' ) ) {
			return $expression;
		}

		preg_match_all( '/\$[A-Za-z_][A-Za-z0-9_]*/', $expression, $matches );
		if ( empty( $matches[0] ) ) {
			return $expression;
		}

		$expanded_expression = $expression;
		foreach ( array_unique( $matches[0] ) as $variable_name ) {
			if ( isset( $visited_variables[ $variable_name ] ) ) {
				continue;
			}

			$assignment_expression = $this->find_latest_assignment_expression( $variable_name, $stackPtr );
			if ( '' === $assignment_expression ) {
				continue;
			}

			$visited_variables[ $variable_name ] = true;
			$expanded_expression                .= ' ' . $this->expand_expression_variables( $assignment_expression, $stackPtr, $visited_variables );
		}

		return $expanded_expression;
	}

	/**
	 * Finds the latest assignment expression for a variable in the same function scope.
	 *
	 * @param string $variable_name Variable name.
	 * @param int    $stackPtr Function call pointer.
	 * @return string
	 */
	private function find_latest_assignment_expression( $variable_name, $stackPtr ) {
		$function_scope_ptr = $this->get_enclosing_function_scope_ptr( $stackPtr );

		for ( $i = $stackPtr - 1; $i >= 0; $i-- ) {
			if ( \T_VARIABLE !== $this->tokens[ $i ]['code'] || $variable_name !== $this->tokens[ $i ]['content'] ) {
				continue;
			}

			if ( $this->get_enclosing_function_scope_ptr( $i ) !== $function_scope_ptr ) {
				continue;
			}

			$next_non_empty = $this->phpcsFile->findNext( Tokens::$emptyTokens, ( $i + 1 ), null, true );
			if ( false === $next_non_empty || \T_EQUAL !== $this->tokens[ $next_non_empty ]['code'] ) {
				continue;
			}

			$assignment_end = $this->phpcsFile->findEndOfStatement( $next_non_empty );
			if ( false === $assignment_end || $assignment_end >= $stackPtr ) {
				continue;
			}

			$assignment_expression = '';
			for ( $j = $next_non_empty + 1; $j < $assignment_end; $j++ ) {
				if ( isset( Tokens::$emptyTokens[ $this->tokens[ $j ]['code'] ] ) ) {
					continue;
				}
				$assignment_expression .= $this->tokens[ $j ]['content'];
			}

			return $assignment_expression;
		}

		return '';
	}

	/**
	 * Gets the closest enclosing function/closure pointer.
	 *
	 * @param int $stackPtr Stack pointer.
	 * @return int|null
	 */
	private function get_enclosing_function_scope_ptr( $stackPtr ) {
		if ( empty( $this->tokens[ $stackPtr ]['conditions'] ) ) {
			return null;
		}

		$conditions = array_reverse( $this->tokens[ $stackPtr ]['conditions'], true );
		foreach ( $conditions as $condition_ptr => $condition_code ) {
			if ( in_array( $condition_code, array( \T_FUNCTION, \T_CLOSURE, \T_FN ), true ) ) {
				return $condition_ptr;
			}
		}

		return null;
	}

	/**
	 * Checks if the expression points to a likely uploads subpath.
	 *
	 * @param string $expression Path expression.
	 * @return bool
	 */
	private function is_uploads_subpath( $expression ) {
		return (bool) preg_match( '/(?:[\'"]|\/|\\\\)uploads(?:[\'"\/\\\\]|$)/i', $expression );
	}
}
