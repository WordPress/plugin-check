<?php
/**
 * Class WordPress\Plugin_Check\Utilities\Results_Exporter
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Utilities;

use InvalidArgumentException;

/**
 * Utility helpers to normalise and export check results.
 *
 * @since 1.8.0
 */
final class Results_Exporter {

	/**
	 * CSV export format slug.
	 *
	 * @since 1.8.0
	 * @var string
	 */
	public const FORMAT_CSV = 'csv';

	/**
	 * JSON export format slug.
	 *
	 * @since 1.8.0
	 * @var string
	 */
	public const FORMAT_JSON = 'json';

	/**
	 * Markdown export format slug.
	 *
	 * @since 1.8.0
	 * @var string
	 */
	public const FORMAT_MARKDOWN = 'markdown';

	/**
	 * Normalises the errors and warnings arrays into grouped results by file.
	 *
	 * @since 1.8.0
	 *
	 * @param array $errors   Check errors grouped by file, line and column.
	 * @param array $warnings Check warnings grouped by file, line and column.
	 * @return array Results grouped by file.
	 */
	public static function get_grouped_results( array $errors, array $warnings ) {
		$grouped            = array();
		$remaining_warnings = $warnings;

		foreach ( $errors as $file => $file_errors ) {
			$file_warnings = array();
			if ( isset( $remaining_warnings[ $file ] ) ) {
				$file_warnings = $remaining_warnings[ $file ];
				unset( $remaining_warnings[ $file ] );
			}

			$grouped[ $file ] = self::flatten_file_results( $file_errors, $file_warnings );
		}

		foreach ( $remaining_warnings as $file => $file_warnings ) {
			$grouped[ $file ] = self::flatten_file_results( array(), $file_warnings );
		}

		return $grouped;
	}

	/**
	 * Returns a flat array of all results including the file name per item.
	 *
	 * @since 1.8.0
	 *
	 * @param array $grouped_results Results grouped by file.
	 * @return array Flat list of results.
	 */
	public static function get_flat_results( array $grouped_results ) {
		$flat_results = array();

		foreach ( $grouped_results as $file => $items ) {
			foreach ( $items as $item ) {
				$flat_results[] = array_merge(
					$item,
					array(
						'file' => $file,
					)
				);
			}
		}

		return $flat_results;
	}

	/**
	 * Creates an export payload for the requested format.
	 *
	 * @since 1.8.0
	 *
	 * @param array  $errors   Check errors grouped by file, line and column.
	 * @param array  $warnings Check warnings grouped by file, line and column.
	 * @param string $format   Target export format.
	 * @param array  $args     Additional arguments.
	 * @return array Export payload.
	 *
	 * @throws InvalidArgumentException When the format is invalid or there are no results.
	 */
	public static function export( array $errors, array $warnings, $format, array $args = array() ) {
		$format = strtolower( (string) $format );

		if ( ! in_array( $format, array( self::FORMAT_CSV, self::FORMAT_JSON, self::FORMAT_MARKDOWN ), true ) ) {
			throw new InvalidArgumentException( __( 'Unsupported export format.', 'plugin-check' ) );
		}

		$grouped = self::get_grouped_results( $errors, $warnings );
		$flat    = self::get_flat_results( $grouped );

		$timestamp = isset( $args['timestamp'] ) ? $args['timestamp'] : current_time( 'Ymd-His' );
		$plugin    = isset( $args['plugin'] ) ? $args['plugin'] : 'plugin-check-report';
		$slug      = isset( $args['slug'] ) ? $args['slug'] : $plugin;
		$filename  = self::build_filename( $slug, $timestamp, $format );

		switch ( $format ) {
			case self::FORMAT_JSON:
				$content   = self::to_json( $grouped, $args );
				$mime_type = 'application/json';
				break;
			case self::FORMAT_MARKDOWN:
				$content   = self::to_markdown( $grouped, $args );
				$mime_type = 'text/markdown';
				break;
			case self::FORMAT_CSV:
			default:
				$content   = self::to_csv( $flat );
				$mime_type = 'text/csv';
				break;
		}

		return array(
			'content'   => $content,
			'filename'  => $filename,
			'mime_type' => $mime_type,
		);
	}

	/**
	 * Builds a filename for the export.
	 *
	 * @since 1.8.0
	 *
	 * @param string $slug      Plugin slug.
	 * @param string $timestamp Export timestamp.
	 * @param string $format    Target format.
	 * @return string Export filename.
	 */
	private static function build_filename( $slug, $timestamp, $format ) {
		$normalized_slug = sanitize_title( $slug );
		if ( empty( $normalized_slug ) ) {
			$normalized_slug = 'plugin-check-report';
		}

		$extension = $format;
		if ( self::FORMAT_MARKDOWN === $format ) {
			$extension = 'md';
		}

		return sprintf( '%1$s-%2$s.%3$s', $normalized_slug, $timestamp, $extension );
	}

	/**
	 * Generates JSON content for the results.
	 *
	 * @since 1.8.0
	 *
	 * @param array $grouped_results Grouped results indexed by file name.
	 * @param array $args            Additional arguments.
	 * @return string JSON export content.
	 */
	private static function to_json( array $grouped_results, array $args ) {
		$payload = array(
			'generated_at' => isset( $args['timestamp_human'] ) ? $args['timestamp_human'] : current_time( 'mysql' ),
			'plugin'       => isset( $args['plugin'] ) ? $args['plugin'] : '',
			'results'      => $grouped_results,
		);

		return wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Generates CSV content for the results.
	 *
	 * @since 1.8.0
	 *
	 * @param array $flat_results Flat list of results including the file name.
	 * @return string CSV export content.
	 */
	private static function to_csv( array $flat_results ) {
		$handle = fopen( 'php://temp', 'r+' );

		fputcsv(
			$handle,
			array( 'file', 'line', 'column', 'type', 'code', 'message', 'docs', 'severity', 'link' ),
			',',
			'"',
			'\\'
		);

		foreach ( $flat_results as $row ) {
			fputcsv(
				$handle,
				array(
					$row['file'],
					sprintf( '%d', $row['line'] ),
					sprintf( '%d', $row['column'] ),
					$row['type'],
					$row['code'],
					$row['message'],
					isset( $row['docs'] ) ? $row['docs'] : '',
					isset( $row['severity'] ) ? $row['severity'] : '',
					isset( $row['link'] ) ? $row['link'] : '',
				),
				',',
				'"',
				'\\'
			);
		}

		rewind( $handle );
		$content = stream_get_contents( $handle );
		fclose( $handle );

		return (string) $content;
	}

	/**
	 * Generates Markdown content for the results.
	 *
	 * @since 1.8.0
	 *
	 * @param array $grouped_results Grouped results indexed by file name.
	 * @param array $args            Additional arguments.
	 * @return string Markdown export content.
	 */
	private static function to_markdown( array $grouped_results, array $args ) {
		$plugin_name    = isset( $args['plugin'] ) ? $args['plugin'] : '';
		$generated_at   = isset( $args['timestamp_human'] ) ? $args['timestamp_human'] : current_time( 'mysql' );
		$markdown_lines = array(
			'# Plugin Check Report',
			'',
			'**Plugin:** ' . ( $plugin_name ? $plugin_name : __( 'Unknown plugin', 'plugin-check' ) ),
			'**Generated at:** ' . $generated_at,
			'',
		);

		$markdown_lines[] = '';
		foreach ( $grouped_results as $file => $items ) {
			$markdown_lines[] = '## `' . $file . '`';
			$markdown_lines[] = '';
			$markdown_lines[] = '| Line | Column | Type | Code | Message | Docs |';
			$markdown_lines[] = '| --- | --- | --- | --- | --- | --- |';

			foreach ( $items as $item ) {
				$markdown_lines[] = sprintf(
					'| %1$d | %2$d | %3$s | %4$s | %5$s | %6$s |',
					$item['line'],
					$item['column'],
					sanitize_text_field( $item['type'] ),
					sanitize_text_field( $item['code'] ),
					self::escape_markdown( $item['message'] ),
					isset( $item['docs'] ) && $item['docs'] ? '[' . __( 'Docs', 'plugin-check' ) . '](' . esc_url_raw( $item['docs'] ) . ')' : ''
				);
			}

			$markdown_lines[] = '';
		}

		return implode( "\n", $markdown_lines );
	}

	/**
	 * Escapes Markdown table cells.
	 *
	 * @since 1.8.0
	 *
	 * @param string $value Cell value.
	 * @return string Escaped value.
	 */
	private static function escape_markdown( $value ) {
		$value = wp_strip_all_tags( (string) $value );
		$value = preg_replace( '/\s+/', ' ', $value );
		$value = str_replace( array( '|', '`' ), array( '\\|', '\\`' ), $value );

		return trim( $value );
	}

	/**
	 * Flattens errors and warnings for a specific file into a list of rows.
	 *
	 * @since 1.8.0
	 *
	 * @param array $file_errors   Errors from a Check_Result, for a specific file.
	 * @param array $file_warnings Warnings from a Check_Result, for a specific file.
	 * @return array Combined file results.
	 */
	public static function flatten_file_results( array $file_errors, array $file_warnings ) {
		$file_results = array_merge(
			self::flatten_collection( $file_errors, 'ERROR' ),
			self::flatten_collection( $file_warnings, 'WARNING' )
		);

		usort(
			$file_results,
			static function ( $a, $b ) {
				if ( $a['line'] < $b['line'] ) {
					return -1;
				}
				if ( $a['line'] > $b['line'] ) {
					return 1;
				}
				if ( $a['column'] < $b['column'] ) {
					return -1;
				}
				if ( $a['column'] > $b['column'] ) {
					return 1;
				}
				return 0;
			}
		);

		return $file_results;
	}

	/**
	 * Flattens a collection of check results for a single message type.
	 *
	 * @since 1.8.0
	 *
	 * @param array  $collection Collection grouped by line and column.
	 * @param string $type       Message type, either ERROR or WARNING.
	 * @return array Flattened collection.
	 */
	private static function flatten_collection( array $collection, $type ) {
		$flattened = array();

		foreach ( $collection as $line => $line_entries ) {
			foreach ( $line_entries as $column => $column_entries ) {
				foreach ( $column_entries as $entry ) {
					$flattened[] = self::normalize_entry( $entry, $type, $line, $column );
				}
			}
		}

		return $flattened;
	}

	/**
	 * Normalizes a single check result entry into the export structure.
	 *
	 * @since 1.8.0
	 *
	 * @param array  $entry  Entry data.
	 * @param string $type   Message type.
	 * @param int    $line   Line number.
	 * @param int    $column Column number.
	 * @return array Normalized entry.
	 */
	private static function normalize_entry( array $entry, $type, $line, $column ) {
		$entry['message'] = str_replace(
			array( '<br>', '<strong>', '</strong>', '<code>', '</code>' ),
			array( ' ', '', '', '`', '`' ),
			$entry['message']
		);

		if ( 'ERROR' === $type ) {
			$entry['message'] = html_entity_decode( $entry['message'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 );
		}

		return array_merge(
			$entry,
			array(
				'type'   => $type,
				'line'   => (int) $line,
				'column' => (int) $column,
			)
		);
	}
}
