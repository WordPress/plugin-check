<?php
namespace WordPressdotorg\Plugin_Check;

/**
 * Class PHP_CLI
 *
 * @since   0.2.0
 *
 * @package WordPressdotorg\Plugin_Check
 */
class PHP_CLI {
	/**
	 * Gets the path to the PHP cli.
	 *
	 * @since 0.2.0
	 *
	 * @return string
	 */
	protected function get_php_binary(): string {
		if ( defined( 'PLUGIN_CHECK_PHP_BIN' ) && ! empty( PLUGIN_CHECK_PHP_BIN ) ) {
			$php_binary = PLUGIN_CHECK_PHP_BIN;
		} else {
			$php_binary = 'php';
		}

		/**
		 * Allows overriding the PHP binary used to run phpcs and other php commands.
		 *
		 * @since 0.2.0
		 *
		 * @param string $php_binary The path to the PHP binary.
		 */
		return (string) apply_filters( 'plugin_check_get_php_binary', $php_binary );
	}

	/**
	 * Gets the PHP base command to run scripts.
	 *
	 * @since 0.2.0
	 *
	 * @return string
	 */
	protected function get_php_cmd(): string {
		$php_cmd_parts[] = '/usr/bin/env';

		$php_cmd_parts[] = $this->get_php_binary();

		$php_cmd_parts[] = '-dmemory_limit=1G';

		// Give the CLI process the same max_execution_time as the calling script.
		if ( ini_get( 'max_execution_time' ) ) {
			$php_cmd_parts[] = '-dmax_execution_time=' . ini_get( 'max_execution_time' );
		}

		return implode( ' ', $php_cmd_parts );
	}

	/**
	 * Gets the full command to run.
	 *
	 * @since 0.2.0
	 *
	 * @param string $append
	 *
	 * @return string
	 */
	public function get_cmd( string $append = '' ): string {
		return $this->get_php_cmd() . ' ' . $append;
	}

}