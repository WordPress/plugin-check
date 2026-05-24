<?php
/**
 * OffloadingServicesTrait
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Helpers;

/**
 * Trait for sharing the list of known offloading services between sniffs.
 *
 * @since 1.7.0
 */
trait OffloadingServicesTrait {

	/**
	 * List of known offloading services.
	 *
	 * @since 1.7.0
	 *
	 * @return array Array of regex patterns for known offloading services.
	 */
	protected function get_known_offloading_services() {
		return array(
			'code\.jquery\.com',
			'(?<!api\.)cloudflare\.com',
			'cdn\.jsdelivr\.net',
			'cdn\.rawgit\.com',
			'code\.getmdl\.io',
			'bootstrapcdn',
			'cl\.ly',
			'cdn\.datatables\.net',
			'aspnetcdn\.com',
			'ajax\.googleapis\.com',
			'webfonts\.zoho\.com',
			'raw\.githubusercontent\.com',
			'github\.com\/.*\/raw',
			'unpkg\.com',
			'imgur\.com',
			'rawgit\.com',
			'amazonaws\.com',
			'cdn\.tiny\.cloud',
			'tiny\.cloud',
			'tailwindcss\.com',
			'herokuapp\.com',
			'(?<!fonts\.)gstatic\.com',
			'kit\.fontawesome',
			'use\.fontawesome',
			'googleusercontent\.com',
			'placeholder\.com',
			's\.w\.org',
		);
	}

	/**
	 * Get the regex pattern for matching known offloading services.
	 *
	 * @since 1.7.0
	 *
	 * @return string Regex pattern for matching known offloading services.
	 */
	protected function get_offloading_services_pattern() {
		return '/(' . implode( '|', $this->get_known_offloading_services() ) . ')/i';
	}
}
