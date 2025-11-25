<?php
/**
 * Class WordPress\Plugin_Check\Admin\Report_Exporter
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Admin;

use Exception;
use WordPress\Plugin_Check\Checker\AJAX_Runner;
use WP_Error;

/**
 * Handles exporting Plugin Check results as CSV or PDF.
 *
 * @since 1.7.0
 */
final class Report_Exporter {

	/**
	 * Nonce action for report exports.
	 *
	 * @since 1.7.0
	 * @var string
	 */
	const NONCE_ACTION = 'pcp-export-report';

	/**
	 * CSV export action slug.
	 *
	 * @since 1.7.0
	 * @var string
	 */
	const ACTION_EXPORT_CSV = 'pcp_export_csv';

	/**
	 * PDF export action slug.
	 *
	 * @since 1.7.0
	 * @var string
	 */
	const ACTION_EXPORT_PDF = 'pcp_export_pdf';

	/**
	 * Register admin-post hooks for exports.
	 *
	 * @since 1.7.0
	 */
	public function add_hooks() {
		add_action( 'admin_post_' . self::ACTION_EXPORT_CSV, array( __CLASS__, 'export_csv' ) );
		add_action( 'admin_post_' . self::ACTION_EXPORT_PDF, array( __CLASS__, 'export_pdf' ) );
	}

	/**
	 * Export the report as CSV.
	 *
	 * @since 1.7.0
	 */
	public static function export_csv() {
		self::guard_request();

		list( $plugin, $categories, $include_experimental ) = self::read_request_params();

		try {
			$data = self::prepare_report_data( $plugin, $categories, $include_experimental );
		} catch ( Exception $e ) {
			wp_die( esc_html( $e->getMessage() ), esc_html__( 'Export failed', 'plugin-check' ), array( 'response' => 500 ) );
		}

		$filename = sprintf( 'plugin-check-report-%s.csv', wp_date( 'Y-m-d-His' ) );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: public' );

		// UTF-8 BOM for Excel compatibility.
		echo "\xEF\xBB\xBF";

		$fh = fopen( 'php://output', 'w' );

		// Header.
		fputcsv( $fh, array( 'Severity', 'File', 'Line', 'Message' ) );

		foreach ( $data['rows'] as $row ) {
			fputcsv(
				$fh,
				array(
					$row['severity'],
					$row['file'],
					$row['line'],
					$row['message'],
				)
			);
		}

		fclose( $fh );
		exit;
	}

	/**
	 * Export the report as PDF.
	 *
	 * Uses DOMPDF if available. If DOMPDF is not installed, a helpful message is shown.
	 *
	 * @since 1.7.0
	 */
	public static function export_pdf() {
		self::guard_request();

		list( $plugin, $categories, $include_experimental ) = self::read_request_params();

		try {
			$data = self::prepare_report_data( $plugin, $categories, $include_experimental );
		} catch ( Exception $e ) {
			wp_die( esc_html( $e->getMessage() ), esc_html__( 'Export failed', 'plugin-check' ), array( 'response' => 500 ) );
		}

		if ( ! class_exists( '\Dompdf\Dompdf' ) ) {
			$message  = esc_html__( 'PDF export requires DOMPDF. Please install the dependency:', 'plugin-check' );
			$message .= '<br><code>composer require dompdf/dompdf</code>';
			wp_die( $message, esc_html__( 'Dependency missing', 'plugin-check' ), 500 );
		}

		// Build minimal HTML for the PDF.
		$title     = esc_html__( 'Plugin Check Report', 'plugin-check' );
		$timestamp = esc_html( wp_date( 'Y-m-d H:i:s' ) );
		$plugin_h  = esc_html( $data['plugin'] );

		$rows_html = '';
		foreach ( $data['rows'] as $row ) {
			$rows_html .= sprintf(
				'<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
				esc_html( $row['severity'] ),
				esc_html( $row['file'] ),
				esc_html( (string) $row['line'] ),
				esc_html( $row['message'] )
			);
		}

		$html = '
			<html>
			<head>
				<meta charset="utf-8">
				<style>
					body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
					h1 { font-size: 20px; margin-bottom: 8px; }
					p.meta { margin: 0 0 12px 0; color: #444; }
					table { width: 100%; border-collapse: collapse; }
					th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
					th { background: #f3f4f5; }
				</style>
			</head>
			<body>
				<h1>' . $title . '</h1>
				<p class="meta">' . esc_html__( 'Plugin:', 'plugin-check' ) . ' ' . $plugin_h . '</p>
				<p class="meta">' . esc_html__( 'Generated:', 'plugin-check' ) . ' ' . $timestamp . '</p>
				<table>
					<thead>
						<tr>
							<th>' . esc_html__( 'Severity', 'plugin-check' ) . '</th>
							<th>' . esc_html__( 'File', 'plugin-check' ) . '</th>
							<th>' . esc_html__( 'Line', 'plugin-check' ) . '</th>
							<th>' . esc_html__( 'Message', 'plugin-check' ) . '</th>
						</tr>
					</thead>
					<tbody>' . $rows_html . '</tbody>
				</table>
			</body>
			</html>
		';

		$dompdf = new \Dompdf\Dompdf();
		$dompdf->loadHtml( $html, 'UTF-8' );
		$dompdf->setPaper( 'A4', 'portrait' );
		$dompdf->render();

		$filename = sprintf( 'plugin-check-report-%s.pdf', wp_date( 'Y-m-d-His' ) );
		$dompdf->stream( $filename, array( 'Attachment' => 1 ) );
		exit;
	}

	/**
	 * Prepare normalized report data by running checks.
	 *
	 * This regenerates results based on request parameters.
	 *
	 * @since 1.7.0
	 *
	 * @param string $plugin               Plugin slug or basename.
	 * @param array  $categories           Selected categories.
	 * @param bool   $include_experimental Whether to include experimental checks.
	 * @return array {
	 *     @type string $plugin Plugin basename.
	 *     @type array  $rows   List of normalized rows with severity, file, line, message.
	 * }
	 *
	 * @throws Exception When the run fails.
	 */
	public static function prepare_report_data( $plugin, array $categories, $include_experimental ) {
		$runner = new AJAX_Runner();

		// Set parameters.
		$runner->set_plugin( $plugin );
		$runner->set_categories( $categories );
		$runner->set_experimental_flag( (bool) $include_experimental );

		// Determine checks, then run.
		$checks_to_run = $runner->get_checks_to_run();
		$runner->set_check_slugs( array_keys( $checks_to_run ) );

		$results = $runner->run();

		$errors   = $results->get_errors();
		$warnings = $results->get_warnings();

		$rows = array();

		// Normalize errors.
		foreach ( $errors as $file => $lines ) {
			foreach ( $lines as $line => $columns ) {
				foreach ( $columns as $messages ) {
					foreach ( $messages as $message_data ) {
						$rows[] = array(
							'severity' => 'ERROR',
							'file'     => (string) $file,
							'line'     => (int) $line,
							'message'  => (string) $message_data['message'],
						);
					}
				}
			}
		}

		// Normalize warnings.
		foreach ( $warnings as $file => $lines ) {
			foreach ( $lines as $line => $columns ) {
				foreach ( $columns as $messages ) {
					foreach ( $messages as $message_data ) {
						$rows[] = array(
							'severity' => 'WARNING',
							'file'     => (string) $file,
							'line'     => (int) $line,
							'message'  => (string) $message_data['message'],
						);
					}
				}
			}
		}

		return array(
			'plugin' => $runner->get_plugin_basename(),
			'rows'   => $rows,
		);
	}

	/**
	 * Validate nonce and capability for export requests.
	 *
	 * @since 1.7.0
	 */
	private static function guard_request() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Invalid request nonce.', 'plugin-check' ), esc_html__( 'Unauthorized', 'plugin-check' ), 403 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export reports.', 'plugin-check' ), esc_html__( 'Forbidden', 'plugin-check' ), 403 );
		}
	}

	/**
	 * Read and sanitize request parameters.
	 *
	 * @since 1.7.0
	 *
	 * @return array Array with [ plugin, categories, include_experimental ].
	 */
	private static function read_request_params() {
		$plugin               = filter_input( INPUT_GET, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$include_experimental = 1 === filter_input( INPUT_GET, 'include-experimental', FILTER_VALIDATE_INT );

		$categories = filter_input( INPUT_GET, 'categories', FILTER_DEFAULT, FILTER_FORCE_ARRAY );
		$categories = is_null( $categories ) ? array() : array_map( 'sanitize_text_field', $categories );

		if ( empty( $plugin ) ) {
			wp_die( esc_html__( 'Missing plugin parameter.', 'plugin-check' ), esc_html__( 'Bad request', 'plugin-check' ), 400 );
		}

		return array( $plugin, $categories, $include_experimental );
	}
}


