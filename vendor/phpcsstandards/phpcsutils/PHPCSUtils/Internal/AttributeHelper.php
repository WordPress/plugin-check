<?php
/**
 * PHPCSUtils, utility functions and classes for PHP_CodeSniffer sniff developers.
 *
 * @package   PHPCSUtils
 * @copyright 2025 PHPCSUtils Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSUtils
 */

namespace PHPCSUtils\Internal;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Exceptions\OutOfBoundsStackPtr;
use PHPCSUtils\Exceptions\TypeError;
use PHPCSUtils\Exceptions\UnexpectedTokenType;
use PHPCSUtils\Exceptions\ValueError;
use PHPCSUtils\Internal\Cache;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\FunctionDeclarations;
use PHPCSUtils\Utils\Parentheses;
use PHPCSUtils\Utils\Scopes;

/**
 * Helper methods for PHP attributes.
 *
 * ---------------------------------------------------------------------------------------------
 * This class is only intended for internal use by PHPCSUtils and is not part of the public API.
 * This also means that it has no promise of backward compatibility.
 *
 * End-users should use the {@see \PHPCSUtils\Utils\Constants::getAttributeOpeners()},
 * {@see \PHPCSUtils\Utils\FunctionDeclarations::getAttributeOpeners()},
 * {@see \PHPCSUtils\Utils\ObjectDeclarations::getAttributeOpeners()},
 * or the {@see \PHPCSUtils\Utils\Variables::getAttributeOpeners()} methods instead.
 * ---------------------------------------------------------------------------------------------
 *
 * @internal
 *
 * @since 1.2.0
 */
final class AttributeHelper
{

    /**
     * Retrieve a list of stack pointers to the attribute openers for any attributes
     * which apply to the current stack pointer.
     *
     * @since 1.2.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the token for a construct which can take an attribute.
     *                                               Currently, this means:
     *                                               - All OO declaration tokens;
     *                                               - All function declaration tokens;
     *                                               - T_VARIABLE tokens for function parameters and OO properties;
     *                                               - T_CONST tokens.
     * @param string                      $type      The expected type of construct.
     *                                               Should be one of the following values:
     *                                               'constant', 'function', 'OO', 'variable'.
     *
     * @return array<int>
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $type parameter is not a string.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not of a token type accepted for $type.
     * @throws \PHPCSUtils\Exceptions\ValueError          For T_VARIABLE tokens: if the token passed does not point
     *                                                    to an OO property token or a parameter in a function declaration.
     */
    public static function getOpeners(File $phpcsFile, $stackPtr, $type)
    {
        $tokens = $phpcsFile->getTokens();

        if (\is_int($stackPtr) === false) {
            throw TypeError::create(2, '$stackPtr', 'integer', $stackPtr);
        }

        if (isset($tokens[$stackPtr]) === false) {
            throw OutOfBoundsStackPtr::create(2, '$stackPtr', $stackPtr);
        }

        if (\is_string($type) === false) {
            throw TypeError::create(3, '$type', 'string', $type);
        }

        $isOOProperty    = false;
        $isFunctionParam = false;
        switch ($type) {
            case 'constant':
                if ($tokens[$stackPtr]['code'] !== \T_CONST) {
                    throw UnexpectedTokenType::create(2, '$stackPtr', 'T_CONST', $tokens[$stackPtr]['type']);
                }
                break;

            case 'function':
                if (isset(Collections::functionDeclarationTokens()[$tokens[$stackPtr]['code']]) === false) {
                    $acceptedTokens = 'T_FUNCTION, T_CLOSURE or T_FN';
                    throw UnexpectedTokenType::create(2, '$stackPtr', $acceptedTokens, $tokens[$stackPtr]['type']);
                }
                break;

            case 'OO':
                if (isset(Tokens::$ooScopeTokens[$tokens[$stackPtr]['code']]) === false) {
                    $acceptedTokens = 'T_CLASS, T_ANON_CLASS, T_INTERFACE, T_TRAIT or T_ENUM';
                    throw UnexpectedTokenType::create(2, '$stackPtr', $acceptedTokens, $tokens[$stackPtr]['type']);
                }
                break;

            case 'variable':
                if ($tokens[$stackPtr]['code'] !== \T_VARIABLE) {
                    throw UnexpectedTokenType::create(2, '$stackPtr', 'T_VARIABLE', $tokens[$stackPtr]['type']);
                }

                $isOOProperty    = Scopes::isOOProperty($phpcsFile, $stackPtr);
                $isFunctionParam = Parentheses::lastOwnerIn($phpcsFile, $stackPtr, Collections::functionDeclarationTokens());

                if ($isOOProperty === false && $isFunctionParam === false) {
                    $message = 'must be the pointer to an OO property or a parameter in a function declaration';
                    throw ValueError::create(2, '$stackPtr', $message);
                }

                // Allow for multi-property declarations.
                if ($isOOProperty === true) {
                    do {
                        $prevNonEmpty = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);
                        if ($tokens[$prevNonEmpty]['code'] !== \T_COMMA) {
                            break;
                        }

                        $stackPtr = $phpcsFile->findPrevious(T_VARIABLE, ($prevNonEmpty - 1), null, false, null, true);
                    } while ($stackPtr !== false);

                    if ($stackPtr === false) {
                        $message = 'must be the pointer to an OO property or a parameter in a function declaration';
                        throw ValueError::create(2, '$stackPtr', $message);
                    }
                }
                break;

            default:
                throw ValueError::create(3, '$type', 'must be one of the following: constant, function, OO, variable');
        }

        if (Cache::isCached($phpcsFile, __METHOD__, "$stackPtr-$type") === true) {
            return Cache::get($phpcsFile, __METHOD__, "$stackPtr-$type");
        }

        $allowedBetween = Tokens::$emptyTokens;
        switch ($type) {
            case 'constant':
                if (Scopes::isOOConstant($phpcsFile, $stackPtr) === true) {
                    $allowedBetween += Collections::constantModifierKeywords();
                }
                break;

            case 'function':
                $allowedBetween += [\T_STATIC => \T_STATIC];
                if (Scopes::isOOMethod($phpcsFile, $stackPtr) === true) {
                    $allowedBetween += Tokens::$methodPrefixes;
                }
                break;

            case 'OO':
                if ($tokens[$stackPtr]['code'] === \T_CLASS) {
                    $allowedBetween += Collections::classModifierKeywords();
                } elseif ($tokens[$stackPtr]['code'] === \T_ANON_CLASS) {
                    $allowedBetween[\T_READONLY] = \T_READONLY;
                }
                break;

            case 'variable':
                $allowedBetween += [\T_NULLABLE => \T_NULLABLE];
                if ($isOOProperty === true) {
                    $allowedBetween += Collections::propertyModifierKeywords();
                    $allowedBetween += Collections::propertyTypeTokens();
                } elseif ($isFunctionParam !== false) {
                    $allowedBetween += Collections::parameterTypeTokens();
                    $allowedBetween += [
                        \T_BITWISE_AND => \T_BITWISE_AND,
                        \T_ELLIPSIS    => \T_ELLIPSIS,
                    ];

                    if ($tokens[$isFunctionParam]['code'] === \T_FUNCTION
                        && Scopes::isOOMethod($phpcsFile, $isFunctionParam) === true
                    ) {
                        $functionName = FunctionDeclarations::getName($phpcsFile, $isFunctionParam);
                        if (empty($functionName) === false && \strtolower($functionName) === '__construct') {
                            $allowedBetween += Collections::propertyModifierKeywords();
                        }
                    }
                }

                break;
        }

        $seenAttributes = [];

        for ($i = ($stackPtr - 1); $i >= 0; $i--) {
            if (isset($tokens[$i]['comment_opener'])) {
                // Skip over docblocks.
                $i = $tokens[$i]['comment_opener'];
                continue;
            }

            if (isset($allowedBetween[$tokens[$i]['code']])) {
                continue;
            }

            if (isset($tokens[$i]['attribute_opener'])) {
                $seenAttributes[] = $tokens[$i]['attribute_opener'];
                $i                = $tokens[$i]['attribute_opener'];
                continue;
            }

            // In all other cases, we've reached the end of our search.
            break;
        }

        if ($seenAttributes !== []) {
            $seenAttributes = \array_reverse($seenAttributes);
        }

        Cache::set($phpcsFile, __METHOD__, "$stackPtr-$type", $seenAttributes);
        return $seenAttributes;
    }
}
