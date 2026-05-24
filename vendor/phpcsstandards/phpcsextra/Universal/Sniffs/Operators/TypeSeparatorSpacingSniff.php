<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\Operators;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Fixers\SpacesFixer;
use PHPCSUtils\Tokens\Collections;

/**
 * Enforce spacing rules around union, intersection and DNF type separators.
 *
 * @since 1.0.0
 * @since 1.3.0 Support for DNF types.
 */
final class TypeSeparatorSpacingSniff implements Sniff
{

    /**
     * Tokens this sniff targets.
     *
     * @since 1.3.0
     *
     * @var array<int|string, int|string>
     */
    private $targetTokens = [
        \T_TYPE_UNION             => \T_TYPE_UNION,
        \T_TYPE_INTERSECTION      => \T_TYPE_INTERSECTION,
        \T_TYPE_OPEN_PARENTHESIS  => \T_TYPE_OPEN_PARENTHESIS,
        \T_TYPE_CLOSE_PARENTHESIS => \T_TYPE_CLOSE_PARENTHESIS,
    ];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.0.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return $this->targetTokens;
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $type = 'union';
        $code = 'UnionType';
        if ($tokens[$stackPtr]['code'] === \T_TYPE_INTERSECTION) {
            $type = 'intersection';
            $code = 'IntersectionType';
        } elseif ($tokens[$stackPtr]['code'] === \T_TYPE_OPEN_PARENTHESIS) {
            $type = 'DNF parenthesis open';
            $code = 'DNFOpen';
        } elseif ($tokens[$stackPtr]['code'] === \T_TYPE_CLOSE_PARENTHESIS) {
            $type = 'DNF parenthesis close';
            $code = 'DNFClose';
        }

        $expectedSpaces = 0;
        $prevNonEmpty   = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);
        if ($tokens[$stackPtr]['code'] === \T_TYPE_OPEN_PARENTHESIS) {
            if ($tokens[$prevNonEmpty]['code'] === \T_COLON
                || $tokens[$prevNonEmpty]['code'] === \T_CONST
                || isset(Collections::propertyModifierKeywords()[$tokens[$prevNonEmpty]['code']]) === true
            ) {
                // Start of return type or property/const type. Always demand 1 space.
                $expectedSpaces = 1;
            }

            if ($tokens[$prevNonEmpty]['code'] === \T_OPEN_PARENTHESIS
                || $tokens[$prevNonEmpty]['code'] === \T_COMMA
            ) {
                // Start of parameter type. Allow new line/indent before.
                if ($tokens[$prevNonEmpty]['line'] === $tokens[$stackPtr]['line']) {
                    $expectedSpaces = 1;
                } else {
                    $expectedSpaces = 'skip';
                }
            }
        }

        if (isset($this->targetTokens[$tokens[$prevNonEmpty]['code']]) === true) {
            // Prevent duplicate errors when there are two adjacent operators.
            $expectedSpaces = 'skip';
        }

        if ($expectedSpaces !== 'skip') {
            SpacesFixer::checkAndFix(
                $phpcsFile,
                $stackPtr,
                $prevNonEmpty,
                $expectedSpaces,
                'Expected %s before the ' . $type . ' type separator. Found: %s',
                $code . 'SpacesBefore',
                'error',
                0, // Severity.
                'Space before ' . $type . ' type separator'
            );
        }

        $expectedSpaces = 0;
        $nextNonEmpty   = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
        if ($tokens[$stackPtr]['code'] === \T_TYPE_CLOSE_PARENTHESIS) {
            if ($tokens[$nextNonEmpty]['code'] === \T_OPEN_CURLY_BRACKET
                || $tokens[$nextNonEmpty]['code'] === \T_VARIABLE
                || $tokens[$nextNonEmpty]['code'] === \T_STRING
            ) {
                // End of return type, parameter or property/const type. Always demand 1 space.
                $expectedSpaces = 1;
            }
        }

        SpacesFixer::checkAndFix(
            $phpcsFile,
            $stackPtr,
            $nextNonEmpty,
            $expectedSpaces,
            'Expected %s after the ' . $type . ' type separator. Found: %s',
            $code . 'SpacesAfter',
            'error',
            0, // Severity.
            'Space after ' . $type . ' type separator'
        );
    }
}
