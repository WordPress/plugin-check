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
		// PHP_CodeSniffer.
		Finder::create()
				->files()
				->ignoreVCS( true )
				->ignoreDotFiles( true )
			->name(
				array(
					'*.php',
					'ruleset.xml',
					'CodeSniffer.conf',
					'composer.json',
				)
			)
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
				),

		// PHPCS Utils required by other sniffs.
		Finder::create()
			->files()
			->ignoreVCS( true )
			->ignoreDotFiles( true )
			->name(
				array(
					'*.php',
					'ruleset.xml',
					'composer.json',
				)
			)
			->exclude(
				array(
					'bin',
					'tests',
				)
			)
			->in(
				array(
					'vendor/phpcsstandards',
				)
			),

		// WordPress coding standards.
		Finder::create()
			->files()
			->ignoreVCS( true )
			->ignoreDotFiles( true )
			->name(
				array(
					'*.php',
					'ruleset.xml',
					'composer.json',
				)
			)
			->in( 'vendor/wp-coding-standards/wpcs' ),

		// VIP coding standards.
		Finder::create()
			->files()
			->ignoreVCS( true )
			->ignoreDotFiles( true )
			->name(
				array(
					'*.php',
					'ruleset.xml',
					'composer.json',
				)
			)
			->notName( '*-test.php' )
			->in( 'vendor/automattic/vipwpcs' ),

		// Plugin Check custom PHPCS sniffs.
		Finder::create()
				->files()
				->ignoreVCS( true )
				->ignoreDotFiles( true )
				->name(
					array(
						'*.php',
						'composer.json',
					)
				)
				->exclude(
					array(
						'build',
						'Tests',
						'vendor',
					)
				)
				->in( 'vendor/plugin-check/phpcs-sniffs' ),

		// WordPress plugin readme parser.
		Finder::create()
				->files()
				->ignoreVCS( true )
				->ignoreDotFiles( true )
			->name(
				array(
					'*.php',
					'composer.json',
				)
			)
				->in( 'vendor/afragen/wordpress-plugin-readme-parser' ),

		// Main composer.json file so that we can build a classmap.
		Finder::create()
				->append( array( 'composer.json' ) ),
	),
);
