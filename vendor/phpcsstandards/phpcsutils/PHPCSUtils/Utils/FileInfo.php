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

/**
 * Utility functions to retrieve information about the file under scan.
 *
 * @since 1.1.0
 */
final class FileInfo
{

    /**
     * List of supported BOM definitions.
     *
     * Use encoding names as keys and hex BOM representations as values.
     *
     * @since 1.1.0
     *
     * @var array<string, string>
     */
    private static $bomDefinitions = [
        'UTF-8'       => 'efbbbf',
        'UTF-16 (BE)' => 'feff',
        'UTF-16 (LE)' => 'fffe',
    ];

    /**
     * Determine whether the file under scan has a byte-order mark at the start.
     *
     * Inspired by similar code being used in a couple of PHPCS native sniffs.
     *
     * @since 1.1.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     *
     * @return string|false Name of the type of BOM found or FALSE when the file does not start with a BOM.
     */
    public static function hasByteOrderMark(File $phpcsFile)
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[0]['code'] !== \T_INLINE_HTML) {
            return false;
        }

        foreach (self::$bomDefinitions as $bomName => $expectedBomHex) {
            $bomByteLength = (int) (\strlen($expectedBomHex) / 2);
            $htmlBomHex    = \bin2hex(\substr($tokens[0]['content'], 0, $bomByteLength));
            if ($htmlBomHex === $expectedBomHex) {
                return $bomName;
            }
        }

        return false;
    }

    /**
     * Determine whether the file under scan has a shebang line at the start.
     *
     * @since 1.1.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     *
     * @return bool
     */
    public static function hasSheBang(File $phpcsFile)
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[0]['code'] !== \T_INLINE_HTML) {
            return false;
        }

        $start            = 0;
        $hasByteOrderMark = self::hasByteOrderMark($phpcsFile);
        if ($hasByteOrderMark !== false) {
            $start = (int) (\strlen(self::$bomDefinitions[$hasByteOrderMark]) / 2);
        }

        return (\substr($tokens[0]['content'], $start, 2) === '#!');
    }
}
