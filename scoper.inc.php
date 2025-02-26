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
	'prefix'   => 'WordPress\\Plugin_Check\\Vendor',

	// See: https://github.com/humbug/php-scoper#finders-and-paths.
	'finders'  => array(
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

		// VariableAnalysis is used by WPCS.
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
			->in( 'vendor/sirbrillig/phpcs-variable-analysis' ),

		// Plugin Check custom PHPCS sniffs.
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

	'patchers' => array(
		static function ( string $file_path, string $prefix, string $content ) {
			if ( str_ends_with( $file_path, 'vendor/squizlabs/php_codesniffer/autoload.php' ) ) {
				$content = str_replace(
					'substr($class, 0, 16) === \'PHP_CodeSniffer\\',
					'substr($class, 0, 46) === \'WordPress\Plugin_Check\Vendor\PHP_CodeSniffer\\',
					$content
				);

				$content = str_replace(
					'substr(str_replace(\'\\\\\', $ds, $class), 16)',
					'substr(str_replace(\'\\\\\', $ds, $class), 46)',
					$content
				);
			}

			if ( str_ends_with( $file_path, 'vendor/squizlabs/php_codesniffer/src/Files/File.php' ) ) {
				$content = str_replace(
					'PHP_CodeSniffer\Tokenizers\\',
					'WordPress\Plugin_Check\Vendor\PHP_CodeSniffer\Tokenizers\\',
					$content
				);
			}

			if ( str_ends_with( $file_path, 'vendor/squizlabs/php_codesniffer/src/Standards/PEAR/Sniffs/Commenting/FileCommentSniff.php' ) ) {
				$content = str_replace(
					'PHP_CodeSniffer\Standards\PEAR\Sniffs\Commenting\FileCommentSniff',
					'WordPress\Plugin_Check\Vendor\PHP_CodeSniffer\Standards\PEAR\Sniffs\Commenting\FileCommentSniff',
					$content
				);
			}

			return $content;
		},
	),
);
