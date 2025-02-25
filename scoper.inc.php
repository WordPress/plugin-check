<?php
/**
 * PHP-Scoper configuration file.
 *
 * @package plugin-check
 * @since 1.5.0
 */

declare(strict_types = 1);

use Isolated\Symfony\Component\Finder\Finder;

return array(
	'prefix'  => 'WordPress\\Plugin_Check\\Vendor',

	// See: https://github.com/humbug/php-scoper#finders-and-paths.
	'finders' => array(
		// PHP_CodeSniffer
		Finder::create()
				->files()
				->ignoreVCS( true )
				->ignoreDotFiles( true )
				->name( '*.php' )
				->exclude(
					array(
						'bin',
						'tests',
					)
				)
				->in(
					array(
						'vendor/squizlabs/php_codesniffer',
					)
				)
				->append(
					array(
						'vendor/squizlabs/php_codesniffer/CodeSniffer.conf',
						'vendor/squizlabs/php_codesniffer/composer.json',
					)
				),

		// Plugin Check custom PHPCS sniffs.
		Finder::create()
				->files()
				->ignoreVCS( true )
				->ignoreDotFiles( true )
				->name( '*.php' )
				->exclude(
					array(
						'build',
						'Tests',
						'vendor',
					)
				)
				->in( 'vendor/plugin-check/phpcs-sniffs' )
				->append( array( 'vendor/plugin-check/phpcs-sniffs/composer.json' ) ),

		// WordPress plugin readme parser.
		Finder::create()
				->files()
				->ignoreVCS( true )
				->ignoreDotFiles( true )
				->name( '*.php' )
				->in( 'vendor/afragen/wordpress-plugin-readme-parser' )
				->append( array( 'vendor/afragen/wordpress-plugin-readme-parser/composer.json' ) ),

		// Main composer.json file so that we can build a classmap.
		Finder::create()
				->append( array( 'composer.json' ) ),
	),
);
