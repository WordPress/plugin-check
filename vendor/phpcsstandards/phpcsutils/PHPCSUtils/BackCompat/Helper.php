<?php
/**
 * PHPCSUtils, utility functions and classes for PHP_CodeSniffer sniff developers.
 *
 * @package   PHPCSUtils
 * @copyright 2019-2020 PHPCSUtils Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSUtils
 */

namespace PHPCSUtils\BackCompat;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\File;
use PHPCSUtils\Exceptions\MissingArgumentError;

/**
 * Utility methods to retrieve (configuration) information from PHP_CodeSniffer.
 *
 * PHP_CodeSniffer cross-version compatibility helper.
 *
 * @since 1.0.0 The initial methods in this class have been ported over from
 *              the external PHPCompatibility & WPCS standards.
 */
final class Helper
{

    /**
     * The default tab width used by PHP_CodeSniffer.
     *
     * @since 1.0.0
     *
     * @var int
     */
    const DEFAULT_TABWIDTH = 4;

    /**
     * Get the PHP_CodeSniffer version number.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public static function getVersion()
    {
        return Config::VERSION;
    }

    /**
     * Pass config data to PHP_CodeSniffer.
     *
     * @since 1.0.0
     *
     * @param string                  $key    The name of the config value.
     * @param mixed                   $value  The value to set. If `null`, the config entry
     *                                        is deleted, reverting it to the default value.
     * @param bool                    $temp   Set this config data temporarily for this script run.
     *                                        This will not write the config data to the config file.
     * @param \PHP_CodeSniffer\Config $config The PHPCS config object.
     *                                        This parameter is required for PHPCS 4.x, optional
     *                                        for PHPCS 3.x and not possible to pass for PHPCS 2.x.
     *                                        Passing the `$phpcsFile->config` property should work
     *                                        in PHPCS 3.x and higher.
     *
     * @return bool Whether the setting of the data was successfull.
     *
     * @throws \PHPCSUtils\Exceptions\MissingArgumentError When using PHPCS 4.x and not passing the $config parameter.
     */
    public static function setConfigData($key, $value, $temp = false, $config = null)
    {
        if (isset($config) === true) {
            // PHPCS 3.x and 4.x.
            return $config->setConfigData($key, $value, $temp);
        }

        if (\version_compare(self::getVersion(), '3.99.99', '>') === true) {
            throw MissingArgumentError::create(4, '$config', 'when running on PHPCS 4.x');
        }

        // PHPCS 3.x.
        return Config::setConfigData($key, $value, $temp);
    }

    /**
     * Get the value of a single PHP_CodeSniffer config key.
     *
     * @see Helper::getCommandLineData() Alternative for the same which is more reliable
     *                                   if the `$phpcsFile` object is available.
     *
     * @since 1.0.0
     *
     * @param string $key The name of the config value.
     *
     * @return string|null
     */
    public static function getConfigData($key)
    {
        return Config::getConfigData($key);
    }

    /**
     * Get the value of a CLI overrulable single PHP_CodeSniffer config key.
     *
     * Use this for config keys which can be set in the `CodeSniffer.conf` file,
     * on the command-line or in a ruleset.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being processed.
     * @param string                      $key       The name of the config value.
     *
     * @return string|null
     */
    public static function getCommandLineData(File $phpcsFile, $key)
    {
        if (isset($phpcsFile->config->{$key})) {
            return $phpcsFile->config->{$key};
        }

        return null;
    }

    /**
     * Get the applicable tab width as passed to PHP_CodeSniffer from the
     * command-line or the ruleset.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being processed.
     *
     * @return int Tab width. Defaults to the PHPCS native default of 4.
     */
    public static function getTabWidth(File $phpcsFile)
    {
        $tabWidth = self::getCommandLineData($phpcsFile, 'tabWidth');
        if ($tabWidth > 0) {
            return (int) $tabWidth;
        }

        return self::DEFAULT_TABWIDTH;
    }

    /**
     * Get the applicable (file) encoding as passed to PHP_CodeSniffer from the
     * command-line or the ruleset.
     *
     * @since 1.0.0
     * @since 1.0.10 The `File` type declaration has been removed from the parameter declaration.
     *
     * @param \PHP_CodeSniffer\Files\File|null $phpcsFile Optional. The current file being processed.
     *
     * @return string Encoding. Defaults to the PHPCS native default, which is 'utf-8'
     *                for PHPCS 3.x.
     */
    public static function getEncoding($phpcsFile = null)
    {
        $default = 'utf-8';

        if ($phpcsFile instanceof File) {
            // Most reliable.
            $encoding = self::getCommandLineData($phpcsFile, 'encoding');
            if ($encoding === null) {
                $encoding = $default;
            }

            return $encoding;
        }

        // Less reliable.
        $encoding = self::getConfigData('encoding');
        if ($encoding === null) {
            $encoding = $default;
        }

        return $encoding;
    }

    /**
     * Check whether the "--ignore-annotations" option is in effect.
     *
     * @since 1.0.0
     * @since 1.0.10 The `File` type declaration has been removed from the parameter declaration.
     *
     * @param \PHP_CodeSniffer\Files\File|null $phpcsFile Optional. The current file being processed.
     *
     * @return bool `TRUE` if annotations should be ignored, `FALSE` otherwise.
     */
    public static function ignoreAnnotations($phpcsFile = null)
    {
        if (isset($phpcsFile)
            && $phpcsFile instanceof File
            && isset($phpcsFile->config->annotations)
        ) {
            return ! $phpcsFile->config->annotations;
        }

        $annotations = Config::getConfigData('annotations');
        if (isset($annotations)) {
            return ! $annotations;
        }

        return false;
    }
}
