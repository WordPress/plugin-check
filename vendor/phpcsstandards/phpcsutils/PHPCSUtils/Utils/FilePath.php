<?php
/**
 * PHPCSUtils, utility functions and classes for PHP_CodeSniffer sniff developers.
 *
 * @package   PHPCSUtils
 * @copyright 2019-2024 PHPCSUtils Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSUtils
 */

namespace PHPCSUtils\Utils;

use PHP_CodeSniffer\Files\File;
use PHPCSUtils\Utils\TextStrings;

/**
 * Helper functions for working with arbitrary file/directory paths.
 *
 * Typically, these methods are useful for sniffs which examine the name of the file
 * under scan and need to act differently depending on the path in which the file
 * under scan is found.
 *
 * @see \PHP_CodeSniffer\Files\getFilename        Retrieves the absolute path to the file under scan.
 * @see \PHPCSUtils\BackCompat\getCommandLineData Can be used to retrieve "basepath" setting.
 *
 * @since 1.1.0
 */
final class FilePath
{

    /**
     * Get the file name of the current file under scan.
     *
     * In contrast to the PHPCS native {@see \PHP_CodeSniffer\Files\getFilename()} method,
     * the name returned by this method will have been normalized.
     *
     * @since 1.1.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     *
     * @return string The file name without surrounding quotes and with forward slashes
     *                as directory separators.
     */
    public static function getName(File $phpcsFile)
    {
        // Usage of `stripQuotes` is to ensure `stdin_path` passed by IDEs does not include quotes.
        $fileName = TextStrings::stripQuotes($phpcsFile->getFileName());
        if ($fileName !== 'STDIN') {
            $fileName = self::normalizeAbsolutePath($fileName);
        }

        return \trim($fileName);
    }

    /**
     * Check whether the input was received via STDIN.
     *
     * @since 1.1.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     *
     * @return bool
     */
    public static function isStdin(File $phpcsFile)
    {
        return (self::getName($phpcsFile) === 'STDIN');
    }

    /**
     * Normalize an absolute path to forward slashes and to include a trailing slash for directories.
     *
     * @since 1.1.0
     *
     * @param string $path Absolute file or directory path.
     *
     * @return string
     */
    public static function normalizeAbsolutePath($path)
    {
        return self::trailingSlashIt(self::normalizeDirectorySeparators($path));
    }

    /**
     * Normalize all directory separators to be a forward slash.
     *
     * {@internal We cannot rely on the OS on which PHPCS is being run to determine the
     * the expected slashes, as the file name could also come from a text string in a
     * tokenized file or have been set by an IDE...}
     *
     * @since 1.1.0
     *
     * @param string $path File or directory path.
     *
     * @return string
     */
    public static function normalizeDirectorySeparators($path)
    {
        return \strtr((string) $path, '\\', '/');
    }

    /**
     * Ensure that a directory path ends on a trailing slash.
     *
     * Includes safeguard against adding a trailing slash to path ending on a file name.
     *
     * @since 1.1.0
     *
     * @param string $path File or directory path.
     *
     * @return string
     */
    public static function trailingSlashIt($path)
    {
        if (\is_string($path) === false || $path === '') {
            return '';
        }

        $extension = '';
        $lastChar  = \substr($path, -1);
        if ($lastChar !== '/' && $lastChar !== '\\') {
            // This may be a file, check if it has a file extension.
            $extension = \pathinfo($path, \PATHINFO_EXTENSION);
        }

        if ($extension !== '') {
            return $path;
        }

        return \rtrim((string) $path, '/\\') . '/';
    }

    /**
     * Check whether one file/directory path starts with another path.
     *
     * Recommended to be used only when both paths are absolute.
     *
     * Note: this function does not normalize paths prior to comparing them.
     * If this is needed, normalization should be done prior to passing
     * the `$haystack` and `$needle` parameters to this function.
     *
     * Also note that this function does a case-sensitive comparison as most OS-es are case-sensitive.
     *
     * @since 1.1.0
     *
     * @param string $haystack Path to examine.
     * @param string $needle   Partial path which the haystack path should start with.
     *
     * @return bool
     */
    public static function startsWith($haystack, $needle)
    {
        return (\strncmp($haystack, $needle, \strlen($needle)) === 0);
    }
}
