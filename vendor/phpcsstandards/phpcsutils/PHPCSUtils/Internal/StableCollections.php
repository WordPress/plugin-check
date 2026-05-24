<?php
/**
 * PHPCSUtils, utility functions and classes for PHP_CodeSniffer sniff developers.
 *
 * @package   PHPCSUtils
 * @copyright 2019-2020 PHPCSUtils Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSUtils
 */

namespace PHPCSUtils\Internal;

/**
 * Stable collections of related tokens.
 *
 * The {@see \PHPCSUtils\Tokens\Collections} class contains a range of token collections for use by sniffs,
 * some of which may change depending on the availability of a token in PHP/PHPCS.
 * The contents of token collections may also vary based on the PHPCS version to allow for optimized sniffing.
 *
 * The functionality _within_ PHPCSUtils, however, should be stable and reliably testable, so for internal
 * use, the most volatile token arrays are replicated here in stable versions to be used by the PHPCSUtils
 * functions (and tests) internally.
 *
 * ---------------------------------------------------------------------------------------------
 * This class is only intended for internal use by PHPCSUtils and is not part of the public API.
 * This also means that it has no promise of backward compatibility.
 *
 * End-users should use the methods in the {@see \PHPCSUtils\Tokens\Collections} class instead.
 * ---------------------------------------------------------------------------------------------
 *
 * @internal
 *
 * @since 1.0.0
 */
final class StableCollections
{

    /**
     * Tokens which can open a short array or short list (PHPCS cross-version compatible).
     *
     * This array will ALWAYS include the `T_OPEN_SQUARE_BRACKET` token to allow for handling
     * intermittent tokenizer issues related to the retokenization to `T_OPEN_SHORT_ARRAY`.
     *
     * @internal
     * @ignore   This array is only for internal use by PHPCSUtils and is not part of the public API.
     *
     * @since 1.0.2
     *
     * @var array<int|string, int|string>
     */
    public static $shortArrayListOpenTokensBC = [
        \T_OPEN_SHORT_ARRAY    => \T_OPEN_SHORT_ARRAY,
        \T_OPEN_SQUARE_BRACKET => \T_OPEN_SQUARE_BRACKET,
    ];

    /**
     * Tokens which are used for short lists.
     *
     * This array will ALWAYS include the `T_OPEN_SQUARE_BRACKET` and `T_CLOSE_SQUARE_BRACKET` tokens
     * to allow for handling intermittent tokenizer issues related to the retokenization to `T_OPEN_SHORT_ARRAY`.
     *
     * @internal
     * @ignore   This array is only for internal use by PHPCSUtils and is not part of the public API.
     *
     * @since 1.0.2
     *
     * @var array<int|string, int|string>
     */
    public static $shortArrayListTokensBC = [
        \T_OPEN_SHORT_ARRAY     => \T_OPEN_SHORT_ARRAY,
        \T_CLOSE_SHORT_ARRAY    => \T_CLOSE_SHORT_ARRAY,
        \T_OPEN_SQUARE_BRACKET  => \T_OPEN_SQUARE_BRACKET,
        \T_CLOSE_SQUARE_BRACKET => \T_CLOSE_SQUARE_BRACKET,
    ];
}
