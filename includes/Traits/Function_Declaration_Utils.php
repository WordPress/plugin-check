<?php
/**
 * Trait WordPress\Plugin_Check\Traits\Function_Declaration_Utils
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Traits;

/**
 * Trait for finding the global functions a plugin declares.
 *
 * The using class provides get_next_significant_token_index() and get_previous_significant_token_index().
 *
 * @since 2.2.0
 */
trait Function_Declaration_Utils {

	/**
	 * Collects the global functions declared across the given PHP files.
	 *
	 * Methods and namespaced functions are skipped, as they cannot stand in for a WordPress function.
	 *
	 * @since 2.2.0
	 *
	 * @param array $php_files PHP file paths.
	 * @return array<string, string> Path of the file declaring each function, keyed by lowercase function name.
	 */
	private function get_declared_global_functions( array $php_files ): array {
		$declared = array();

		foreach ( $php_files as $file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$source = file_get_contents( $file );
			if ( false !== $source && '' !== $source ) {
				$names     = $this->get_tokens_declared_function_names( token_get_all( $source ) );
				$declared += array_fill_keys( array_keys( $names ), $file );
			}
		}

		return $declared;
	}

	/**
	 * Collects the global functions declared in a token stream.
	 *
	 * @since 2.2.0
	 *
	 * @param array $tokens Token stream.
	 * @return array<string, true> Map of declared function names, keyed by lowercase name.
	 */
	private function get_tokens_declared_function_names( array $tokens ): array {
		$state    = array(
			'in_namespace' => false,
			'depth'        => 0,
			'parens'       => 0,
			'class_depths' => array(),
			'class_parens' => null,
		);
		$declared = array();

		foreach ( $tokens as $index => $token ) {
			if ( $this->track_declaration_scope( $token, $state ) || ! is_array( $token ) ) {
				continue;
			}

			if ( $this->track_declaration_context( $tokens, $index, $state ) ) {
				continue;
			}

			$name = $this->get_declared_global_function_name( $tokens, $index, $state );
			if ( null !== $name ) {
				$declared[ $name ] = true;
			}
		}

		return $declared;
	}

	/**
	 * Tracks brace and parenthesis depth, and which braces open a class-like body.
	 *
	 * @since 2.2.0
	 *
	 * @param array|string $token Current token.
	 * @param array        $state Scan state, updated in place.
	 * @return bool Whether the token is a brace or a parenthesis.
	 */
	private function track_declaration_scope( $token, array &$state ): bool {
		if ( '(' === $token || ')' === $token ) {
			$state['parens'] += '(' === $token ? 1 : -1;
			return true;
		}

		if ( '}' === $token ) {
			if ( end( $state['class_depths'] ) === $state['depth'] ) {
				array_pop( $state['class_depths'] );
			}
			--$state['depth'];
			return true;
		}

		if ( '{' !== $token && ! ( is_array( $token ) && in_array( $token[0], array( T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES ), true ) ) ) {
			return false;
		}

		++$state['depth'];
		// Braces inside an anonymous class's arguments, such as a closure, are not its body.
		if ( $state['parens'] === $state['class_parens'] ) {
			$state['class_depths'][] = $state['depth'];
			$state['class_parens']   = null;
		}

		return true;
	}

	/**
	 * Tracks namespace declarations and the start of class-like declarations.
	 *
	 * @since 2.2.0
	 *
	 * @param array $tokens Token stream.
	 * @param int   $index  Current token index.
	 * @param array $state  Scan state, updated in place.
	 * @return bool Whether the token is a namespace or class-like keyword.
	 */
	private function track_declaration_context( array $tokens, int $index, array &$state ): bool {
		if ( T_NAMESPACE === $tokens[ $index ][0] ) {
			$next_token = $this->get_next_significant_token( $tokens, $index );
			// `namespace {` is the global namespace; `namespace\foo()` is a call.
			if ( '{' === $next_token ) {
				$state['in_namespace'] = false;
			} elseif ( is_array( $next_token ) && T_NS_SEPARATOR !== $next_token[0] ) {
				$state['in_namespace'] = true;
			}
			return true;
		}

		if ( ! $this->is_class_like_keyword( $tokens, $index ) ) {
			return false;
		}

		$state['class_parens'] = $state['parens'];
		return true;
	}

	/**
	 * Checks whether a token starts a class, interface, trait or enum declaration.
	 *
	 * @since 2.2.0
	 *
	 * @param array $tokens Token stream.
	 * @param int   $index  Current token index.
	 * @return bool
	 */
	private function is_class_like_keyword( array $tokens, int $index ): bool {
		$token = $tokens[ $index ];

		if ( in_array( $token[0], array( T_CLASS, T_INTERFACE, T_TRAIT ), true ) || ( defined( 'T_ENUM' ) && constant( 'T_ENUM' ) === $token[0] ) ) {
			// Skip `Foo::class`.
			$previous_token = $this->get_previous_significant_token( $tokens, $index );
			return ! is_array( $previous_token ) || T_DOUBLE_COLON !== $previous_token[0];
		}

		// Before PHP 8.1, `enum Foo` is tokenized as two plain strings.
		$next_token = $this->get_next_significant_token( $tokens, $index );
		return T_STRING === $token[0] && 'enum' === strtolower( $token[1] ) && is_array( $next_token ) && T_STRING === $next_token[0];
	}

	/**
	 * Gets the name of the global function declared at a token, if any.
	 *
	 * @since 2.2.0
	 *
	 * @param array $tokens Token stream.
	 * @param int   $index  Current token index.
	 * @param array $state  Scan state.
	 * @return string|null Lowercase function name, or null when the token does not declare a global function.
	 */
	private function get_declared_global_function_name( array $tokens, int $index, array $state ): ?string {
		if ( T_FUNCTION !== $tokens[ $index ][0] || $state['in_namespace'] || ! empty( $state['class_depths'] ) ) {
			return null;
		}

		// Skip `use function foo;`.
		$previous_token = $this->get_previous_significant_token( $tokens, $index );
		if ( is_array( $previous_token ) && T_USE === $previous_token[0] ) {
			return null;
		}

		// A closure has no name, so the next token is `(`.
		$name_token = $this->get_function_name_token( $tokens, $index );
		return is_array( $name_token ) && T_STRING === $name_token[0] ? strtolower( $name_token[1] ) : null;
	}

	/**
	 * Gets the token after a `function` keyword that would hold the function's name.
	 *
	 * @since 2.2.0
	 *
	 * @param array $tokens Token stream.
	 * @param int   $index  Index of the `function` keyword.
	 * @return array|string|null
	 */
	private function get_function_name_token( array $tokens, int $index ) {
		$next_index = $this->get_next_significant_token_index( $tokens, $index );
		$next_token = null === $next_index ? null : $tokens[ $next_index ];

		// Skip the `&` of a function returning by reference.
		if ( '&' === $next_token || ( is_array( $next_token ) && '&' === $next_token[1] ) ) {
			return $this->get_next_significant_token( $tokens, $next_index );
		}

		return $next_token;
	}

	/**
	 * Gets the next significant token.
	 *
	 * @since 2.2.0
	 *
	 * @param array $tokens Token stream.
	 * @param int   $index  Current token index.
	 * @return array|string|null
	 */
	private function get_next_significant_token( array $tokens, int $index ) {
		$next_index = $this->get_next_significant_token_index( $tokens, $index );
		return null === $next_index ? null : $tokens[ $next_index ];
	}

	/**
	 * Gets the previous significant token.
	 *
	 * @since 2.2.0
	 *
	 * @param array $tokens Token stream.
	 * @param int   $index  Current token index.
	 * @return array|string|null
	 */
	private function get_previous_significant_token( array $tokens, int $index ) {
		$previous_index = $this->get_previous_significant_token_index( $tokens, $index );
		return null === $previous_index ? null : $tokens[ $previous_index ];
	}
}
