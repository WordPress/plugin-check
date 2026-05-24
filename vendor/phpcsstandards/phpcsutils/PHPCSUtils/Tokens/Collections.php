<?php
/**
 * PHPCSUtils, utility functions and classes for PHP_CodeSniffer sniff developers.
 *
 * @package   PHPCSUtils
 * @copyright 2019-2020 PHPCSUtils Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSUtils
 */

namespace PHPCSUtils\Tokens;

use PHPCSUtils\Exceptions\InvalidTokenArray;

/**
 * Collections of related tokens as often used and needed for sniffs.
 *
 * These are additional "token groups" to compliment the ones available through the PHPCS
 * native {@see \PHP_CodeSniffer\Util\Tokens} class.
 *
 * @see \PHP_CodeSniffer\Util\Tokens    PHPCS native token groups.
 * @see \PHPCSUtils\BackCompat\BCTokens Backward compatible version of the PHPCS native token groups.
 *
 * @since 1.0.0
 *
 * @method static array<int|string, int|string> alternativeControlStructureSyntaxes()      Tokens for control structures
 *                                                                                         which can use the alternative
 *                                                                                         control structure syntax.
 * @method static array<int|string, int|string> alternativeControlStructureSyntaxClosers() Tokens representing alternative
 *                                                                                         control structure syntax closer
 *                                                                                         keywords.
 * @method static array<int|string, int|string> arrayTokens()                              Tokens which are used to create
 *                                                                                         arrays.
 * @method static array<int|string, int|string> classModifierKeywords()                    Modifier keywords which can be
 *                                                                                         used for a class declaration.
 * @method static array<int|string, int|string> closedScopes()                             List of tokens which represent
 *                                                                                         "closed" scopes.
 * @method static array<int|string, int|string> constantModifierKeywords()                 Tokens which can be used as
 *                                                                                         modifiers for a constant
 *                                                                                         declaration (in OO structures).
 * @method static array<int|string, int|string> controlStructureTokens()                   Control structure tokens.
 * @method static array<int|string, int|string> functionDeclarationTokens()                Tokens which represent a keyword
 *                                                                                         which starts a function
 *                                                                                         declaration.
 * @method static array<int|string, int|string> incrementDecrementOperators()              Increment/decrement operator
 *                                                                                         tokens.
 * @method static array<int|string, int|string> listTokens()                               Tokens which are used to create
 *                                                                                         lists.
 * @method static array<int|string, int|string> namespaceDeclarationClosers()              List of tokens which can end a
 *                                                                                         namespace declaration statement.
 * @method static array<int|string, int|string> nameTokens()                               Tokens used for "names", be it
 *                                                                                         namespace, OO, function or
 *                                                                                         constant names.
 * @method static array<int|string, int|string> objectOperators()                          Object operator tokens.
 * @method static array<int|string, int|string> ooCanExtend()                              OO structures which can use the
 *                                                                                         "extends" keyword.
 * @method static array<int|string, int|string> ooCanImplement()                           OO structures which can use the
 *                                                                                         "implements" keyword.
 * @method static array<int|string, int|string> ooConstantScopes()                         OO scopes in which constants can
 *                                                                                         be declared.
 * @method static array<int|string, int|string> ooHierarchyKeywords()                      Tokens types used for "forwarding"
 *                                                                                         calls within OO structures.
 * @method static array<int|string, int|string> ooPropertyScopes()                         OO scopes in which properties can
 *                                                                                         be declared.
 * @method static array<int|string, int|string> phpOpenTags()                              Tokens which open PHP.
 * @method static array<int|string, int|string> propertyModifierKeywords()                 Modifier keywords which can be
 *                                                                                         used for a property declaration.
 * @method static array<int|string, int|string> shortArrayTokens()                         Tokens which are used for
 *                                                                                         short arrays.
 * @method static array<int|string, int|string> shortListTokens()                          Tokens which are used for
 *                                                                                         short lists.
 * @method static array<int|string, int|string> ternaryOperators()                         Tokens which represent ternary
 *                                                                                         operators.
 * @method static array<int|string, int|string> textStringStartTokens()                    Tokens which can start a
 *                                                                                         - potentially multi-line -
 *                                                                                         text string.
 */
final class Collections
{

    /**
     * Tokens for control structures which can use the alternative control structure syntax.
     *
     * @since 1.0.0 Use the {@see Collections::alternativeControlStructureSyntaxes()} method for access.
     *
     * @var array<int, int>
     */
    private static $alternativeControlStructureSyntaxes = [
        \T_IF      => \T_IF,
        \T_ELSEIF  => \T_ELSEIF,
        \T_ELSE    => \T_ELSE,
        \T_FOR     => \T_FOR,
        \T_FOREACH => \T_FOREACH,
        \T_SWITCH  => \T_SWITCH,
        \T_WHILE   => \T_WHILE,
        \T_DECLARE => \T_DECLARE,
    ];

    /**
     * Tokens representing alternative control structure syntax closer keywords.
     *
     * @since 1.0.0 Use the {@see Collections::alternativeControlStructureSyntaxClosers()} method for access.
     *
     * @var array<int, int>
     */
    private static $alternativeControlStructureSyntaxClosers = [
        \T_ENDIF      => \T_ENDIF,
        \T_ENDFOR     => \T_ENDFOR,
        \T_ENDFOREACH => \T_ENDFOREACH,
        \T_ENDWHILE   => \T_ENDWHILE,
        \T_ENDSWITCH  => \T_ENDSWITCH,
        \T_ENDDECLARE => \T_ENDDECLARE,
    ];

    /**
     * Tokens which can open an array (PHPCS cross-version compatible).
     *
     * Should only be used selectively.
     * Depending on the PHPCS version, the token array will be expanded in the associated method.
     *
     * @see \PHPCSUtils\Tokens\Collections::arrayTokensBC()      Related method to retrieve tokens used
     *                                                           for arrays (PHPCS cross-version).
     * @see \PHPCSUtils\Tokens\Collections::shortArrayTokensBC() Related method to retrieve only tokens used
     *                                                           for short arrays (PHPCS cross-version).
     *
     * @since 1.0.0 Use the {@see Collections::arrayOpenTokensBC()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $arrayOpenTokensBC = [
        \T_ARRAY            => \T_ARRAY,
        \T_OPEN_SHORT_ARRAY => \T_OPEN_SHORT_ARRAY,
    ];

    /**
     * Tokens which are used to create arrays.
     *
     * @see \PHPCSUtils\Tokens\Collections::arrayOpenTokensBC() Related method to retrieve only the "open" tokens
     *                                                          used for arrays (PHPCS cross-version compatible).
     * @see \PHPCSUtils\Tokens\Collections::arrayTokensBC()     Related method to retrieve tokens used
     *                                                          for arrays (PHPCS cross-version compatible).
     * @see \PHPCSUtils\Tokens\Collections::shortArrayTokens()  Related method to retrieve only tokens used
     *                                                          for short arrays.
     *
     * @since 1.0.0 Use the {@see Collections::arrayTokens()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $arrayTokens = [
        \T_ARRAY             => \T_ARRAY,
        \T_OPEN_SHORT_ARRAY  => \T_OPEN_SHORT_ARRAY,
        \T_CLOSE_SHORT_ARRAY => \T_CLOSE_SHORT_ARRAY,
    ];

    /**
     * Modifier keywords which can be used for a class declaration.
     *
     * @since 1.0.0 Use the {@see Collections::classModifierKeywords()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $classModifierKeywords = [
        \T_FINAL    => \T_FINAL,
        \T_ABSTRACT => \T_ABSTRACT,
        \T_READONLY => \T_READONLY,
    ];

    /**
     * List of tokens which represent "closed" scopes.
     *
     * I.e. anything declared within that scope - except for other closed scopes - is
     * outside of the global namespace.
     *
     * This list doesn't contain the `T_NAMESPACE` token on purpose as variables declared
     * within a namespace scope are still global and not limited to that namespace.
     *
     * @since 1.0.0 Use the {@see Collections::closedScopes()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $closedScopes = [
        \T_CLASS      => \T_CLASS,
        \T_ANON_CLASS => \T_ANON_CLASS,
        \T_INTERFACE  => \T_INTERFACE,
        \T_TRAIT      => \T_TRAIT,
        \T_ENUM       => \T_ENUM,
        \T_FUNCTION   => \T_FUNCTION,
        \T_CLOSURE    => \T_CLOSURE,
    ];

    /**
     * Modifier keywords which can be used for constant declarations (in OO structures).
     *
     * - PHP 7.1 added class constants visibility support.
     * - PHP 8.1 added support for final class constants.
     *
     * @since 1.0.0 Use the {@see Collections::constantModifierKeywords()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $constantModifierKeywords = [
        \T_PUBLIC    => \T_PUBLIC,
        \T_PRIVATE   => \T_PRIVATE,
        \T_PROTECTED => \T_PROTECTED,
        \T_FINAL     => \T_FINAL,
    ];

    /**
     * Token types which can be encountered in an OO constant type declaration.
     *
     * @since 1.1.0 Use the {@see Collections::constantTypeTokens()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $constantTypeTokens = [
        \T_CALLABLE               => \T_CALLABLE, // Not allowed in PHP, but in this list to allow for flagging code errors.
        \T_FALSE                  => \T_FALSE,
        \T_TRUE                   => \T_TRUE,
        \T_NULL                   => \T_NULL,
        \T_TYPE_UNION             => \T_TYPE_UNION,
        \T_TYPE_INTERSECTION      => \T_TYPE_INTERSECTION,
        \T_TYPE_OPEN_PARENTHESIS  => \T_TYPE_OPEN_PARENTHESIS,
        \T_TYPE_CLOSE_PARENTHESIS => \T_TYPE_CLOSE_PARENTHESIS,
    ];

    /**
     * Control structure tokens.
     *
     * @since 1.0.0 Use the {@see Collections::controlStructureTokens()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $controlStructureTokens = [
        \T_IF      => \T_IF,
        \T_ELSEIF  => \T_ELSEIF,
        \T_ELSE    => \T_ELSE,
        \T_FOR     => \T_FOR,
        \T_FOREACH => \T_FOREACH,
        \T_SWITCH  => \T_SWITCH,
        \T_DO      => \T_DO,
        \T_WHILE   => \T_WHILE,
        \T_DECLARE => \T_DECLARE,
        \T_MATCH   => \T_MATCH,
    ];

    /**
     * Tokens which represent a keyword which starts a function declaration.
     *
     * @since 1.0.0 Use the {@see Collections::functionDeclarationTokens()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $functionDeclarationTokens = [
        \T_FUNCTION => \T_FUNCTION,
        \T_CLOSURE  => \T_CLOSURE,
        \T_FN       => \T_FN,
    ];

    /**
     * Increment/decrement operator tokens.
     *
     * @since 1.0.0 Use the {@see Collections::incrementDecrementOperators()} method for access.
     *
     * @var array<int, int>
     */
    private static $incrementDecrementOperators = [
        \T_DEC => \T_DEC,
        \T_INC => \T_INC,
    ];

    /**
     * Tokens which can open a list construct (PHPCS cross-version compatible).
     *
     * Should only be used selectively.
     * Depending on the PHPCS version, the token array will be expanded in the associated method.
     *
     * @see \PHPCSUtils\Tokens\Collections::listTokensBC()      Related method to retrieve tokens used
     *                                                          for lists (PHPCS cross-version).
     * @see \PHPCSUtils\Tokens\Collections::shortListTokensBC() Related method to retrieve only tokens used
     *                                                          for short lists (PHPCS cross-version).
     *
     * @since 1.0.0 Use the {@see Collections::listOpenTokensBC()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $listOpenTokensBC = [
        \T_LIST             => \T_LIST,
        \T_OPEN_SHORT_ARRAY => \T_OPEN_SHORT_ARRAY,
    ];

    /**
     * Tokens which are used to create lists.
     *
     * @see \PHPCSUtils\Tokens\Collections::listTokensBC()    Related method to retrieve tokens used
     *                                                        for lists (PHPCS cross-version).
     * @see \PHPCSUtils\Tokens\Collections::shortListTokens() Related method to retrieve only tokens used
     *                                                        for short lists.
     *
     * @since 1.0.0 Use the {@see Collections::listTokens()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $listTokens = [
        \T_LIST              => \T_LIST,
        \T_OPEN_SHORT_ARRAY  => \T_OPEN_SHORT_ARRAY,
        \T_CLOSE_SHORT_ARRAY => \T_CLOSE_SHORT_ARRAY,
    ];

    /**
     * List of tokens which can end a namespace declaration statement.
     *
     * @since 1.0.0 Use the {@see Collections::namespaceDeclarationClosers()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $namespaceDeclarationClosers = [
        \T_SEMICOLON          => \T_SEMICOLON,
        \T_OPEN_CURLY_BRACKET => \T_OPEN_CURLY_BRACKET,
        \T_CLOSE_TAG          => \T_CLOSE_TAG,
    ];

    /**
     * Tokens used for "names", be it namespace, OO, function or constant names.
     *
     * Includes the tokens introduced in PHP 8.0 for "Namespaced names as single token".
     *
     * Note: the PHP 8.0 namespaced name tokens are backfilled in PHPCS since PHPCS 3.5.7,
     * but are not used yet (the PHP 8.0 tokenization is "undone" in PHPCS).
     * As of PHPCS 4.0.0, these tokens _will_ be used and the PHP 8.0 tokenization is respected.
     *
     * @link https://wiki.php.net/rfc/namespaced_names_as_token PHP RFC on namespaced names as single token
     *
     * @since 1.0.0 Use the {@see Collections::nameTokens()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $nameTokens = [
        \T_STRING               => \T_STRING,
        \T_NAME_QUALIFIED       => \T_NAME_QUALIFIED,
        \T_NAME_FULLY_QUALIFIED => \T_NAME_FULLY_QUALIFIED,
        \T_NAME_RELATIVE        => \T_NAME_RELATIVE,
    ];

    /**
     * Object operator tokens.
     *
     * @since 1.0.0 Use the {@see Collections::objectOperators()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $objectOperators = [
        \T_DOUBLE_COLON             => \T_DOUBLE_COLON,
        \T_OBJECT_OPERATOR          => \T_OBJECT_OPERATOR,
        \T_NULLSAFE_OBJECT_OPERATOR => \T_NULLSAFE_OBJECT_OPERATOR,
    ];

    /**
     * OO structures which can use the "extends" keyword.
     *
     * @since 1.0.0 Use the {@see Collections::ooCanExtend()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $ooCanExtend = [
        \T_CLASS      => \T_CLASS,
        \T_ANON_CLASS => \T_ANON_CLASS,
        \T_INTERFACE  => \T_INTERFACE,
    ];

    /**
     * OO structures which can use the "implements" keyword.
     *
     * @since 1.0.0 Use the {@see Collections::ooCanImplement()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $ooCanImplement = [
        \T_CLASS      => \T_CLASS,
        \T_ANON_CLASS => \T_ANON_CLASS,
        \T_ENUM       => \T_ENUM,
    ];

    /**
     * OO scopes in which constants can be declared.
     *
     * - PHP 8.2 added support for constants in traits.
     *
     * @since 1.0.0 Use the {@see Collections::ooConstantScopes()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $ooConstantScopes = [
        \T_CLASS      => \T_CLASS,
        \T_ANON_CLASS => \T_ANON_CLASS,
        \T_INTERFACE  => \T_INTERFACE,
        \T_ENUM       => \T_ENUM,
        \T_TRAIT      => \T_TRAIT,
    ];

    /**
     * Tokens types used for "forwarding" calls within OO structures.
     *
     * @link https://www.php.net/language.oop5.paamayim-nekudotayim PHP Manual on OO forwarding calls
     *
     * @since 1.0.0 Use the {@see Collections::ooHierarchyKeywords()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $ooHierarchyKeywords = [
        \T_PARENT => \T_PARENT,
        \T_SELF   => \T_SELF,
        \T_STATIC => \T_STATIC,
    ];

    /**
     * OO scopes in which properties can be declared.
     *
     * - PHP 8.4 added support for properties in interfaces.
     *
     * @since 1.0.0 Use the {@see Collections::ooPropertyScopes()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $ooPropertyScopes = [
        \T_CLASS      => \T_CLASS,
        \T_ANON_CLASS => \T_ANON_CLASS,
        \T_INTERFACE  => \T_INTERFACE,
        \T_TRAIT      => \T_TRAIT,
    ];

    /**
     * Token types which can be encountered in a parameter type declaration.
     *
     * @since 1.0.0 Use the {@see Collections::parameterTypeTokens()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $parameterTypeTokens = [
        \T_CALLABLE               => \T_CALLABLE,
        \T_SELF                   => \T_SELF,
        \T_PARENT                 => \T_PARENT,
        \T_FALSE                  => \T_FALSE,
        \T_TRUE                   => \T_TRUE,
        \T_NULL                   => \T_NULL,
        \T_TYPE_UNION             => \T_TYPE_UNION,
        \T_TYPE_INTERSECTION      => \T_TYPE_INTERSECTION,
        \T_TYPE_OPEN_PARENTHESIS  => \T_TYPE_OPEN_PARENTHESIS,
        \T_TYPE_CLOSE_PARENTHESIS => \T_TYPE_CLOSE_PARENTHESIS,
    ];

    /**
     * Tokens which open PHP.
     *
     * @since 1.0.0 Use the {@see Collections::phpOpenTags()} method for access.
     *
     * @var array<int, int>
     */
    private static $phpOpenTags = [
        \T_OPEN_TAG           => \T_OPEN_TAG,
        \T_OPEN_TAG_WITH_ECHO => \T_OPEN_TAG_WITH_ECHO,
    ];

    /**
     * Modifier keywords which can be used for a property declaration.
     *
     * @since 1.0.0 Use the {@see Collections::propertyModifierKeywords()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $propertyModifierKeywords = [
        \T_PUBLIC        => \T_PUBLIC,
        \T_PUBLIC_SET    => \T_PUBLIC_SET,
        \T_PROTECTED     => \T_PROTECTED,
        \T_PROTECTED_SET => \T_PROTECTED_SET,
        \T_PRIVATE       => \T_PRIVATE,
        \T_PRIVATE_SET   => \T_PRIVATE_SET,
        \T_STATIC        => \T_STATIC,
        \T_VAR           => \T_VAR,
        \T_READONLY      => \T_READONLY,
        \T_FINAL         => \T_FINAL,
        \T_ABSTRACT      => \T_ABSTRACT,
    ];

    /**
     * Token types which can be encountered in a property type declaration.
     *
     * @since 1.0.0 Use the {@see Collections::propertyTypeTokens()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $propertyTypeTokens = [
        \T_CALLABLE               => \T_CALLABLE, // Not allowed in PHP, but in this list to allow for flagging code errors.
        \T_SELF                   => \T_SELF,
        \T_PARENT                 => \T_PARENT,
        \T_FALSE                  => \T_FALSE,
        \T_TRUE                   => \T_TRUE,
        \T_NULL                   => \T_NULL,
        \T_TYPE_UNION             => \T_TYPE_UNION,
        \T_TYPE_INTERSECTION      => \T_TYPE_INTERSECTION,
        \T_TYPE_OPEN_PARENTHESIS  => \T_TYPE_OPEN_PARENTHESIS,
        \T_TYPE_CLOSE_PARENTHESIS => \T_TYPE_CLOSE_PARENTHESIS,
    ];

    /**
     * Token types which can be encountered in a return type declaration.
     *
     * @since 1.0.0 Use the {@see Collections::returnTypeTokens()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $returnTypeTokens = [
        \T_CALLABLE               => \T_CALLABLE,
        \T_FALSE                  => \T_FALSE,
        \T_TRUE                   => \T_TRUE,
        \T_NULL                   => \T_NULL,
        \T_TYPE_UNION             => \T_TYPE_UNION,
        \T_TYPE_INTERSECTION      => \T_TYPE_INTERSECTION,
        \T_TYPE_OPEN_PARENTHESIS  => \T_TYPE_OPEN_PARENTHESIS,
        \T_TYPE_CLOSE_PARENTHESIS => \T_TYPE_CLOSE_PARENTHESIS,
    ];

    /**
     * Tokens which can open a short array or short list (PHPCS cross-version compatible).
     *
     * Should only be used selectively.
     * Depending on the PHPCS version, the token array will be expanded in the associated method.
     *
     * @since 1.0.0 Use the {@see Collections::shortArrayListOpenTokensBC()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $shortArrayListOpenTokensBC = [
        \T_OPEN_SHORT_ARRAY => \T_OPEN_SHORT_ARRAY,
    ];

    /**
     * Tokens which are used for short arrays.
     *
     * @see \PHPCSUtils\Tokens\Collections::arrayTokens() Related method to retrieve all tokens used for arrays.
     *
     * @since 1.0.0 Use the {@see Collections::shortArrayTokens()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $shortArrayTokens = [
        \T_OPEN_SHORT_ARRAY  => \T_OPEN_SHORT_ARRAY,
        \T_CLOSE_SHORT_ARRAY => \T_CLOSE_SHORT_ARRAY,
    ];

    /**
     * Tokens which are used for short lists.
     *
     * @see \PHPCSUtils\Tokens\Collections::listTokens() Related method to retrieve all tokens used for lists.
     *
     * @since 1.0.0 Use the {@see Collections::shortListTokens()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $shortListTokens = [
        \T_OPEN_SHORT_ARRAY  => \T_OPEN_SHORT_ARRAY,
        \T_CLOSE_SHORT_ARRAY => \T_CLOSE_SHORT_ARRAY,
    ];

    /**
     * Tokens which represent ternary operators.
     *
     * @since 1.1.0 Use the {@see Collections::ternaryOperators()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $ternaryOperators = [
        \T_INLINE_THEN => \T_INLINE_THEN,
        \T_INLINE_ELSE => \T_INLINE_ELSE,
    ];

    /**
     * Tokens which can start a - potentially multi-line - text string.
     *
     * @since 1.0.0 Use the {@see Collections::textStringStartTokens()} method for access.
     *
     * @var array<int|string, int|string>
     */
    private static $textStringStartTokens = [
        \T_START_HEREDOC            => \T_START_HEREDOC,
        \T_START_NOWDOC             => \T_START_NOWDOC,
        \T_CONSTANT_ENCAPSED_STRING => \T_CONSTANT_ENCAPSED_STRING,
        \T_DOUBLE_QUOTED_STRING     => \T_DOUBLE_QUOTED_STRING,
    ];

    /**
     * Handle calls to (undeclared) methods for token arrays which don't need special handling.
     *
     * @since 1.0.0
     *
     * @param string       $name The name of the method which has been called.
     * @param array<mixed> $args Any arguments passed to the method.
     *                           Unused as none of the methods take arguments.
     *
     * @return array<int|string, int|string> Token array
     *
     * @throws \PHPCSUtils\Exceptions\InvalidTokenArray When an invalid token array is requested.
     */
    public static function __callStatic($name, $args)
    {
        if (isset(self::${$name})) {
            return self::${$name};
        }

        // Unknown token array requested.
        throw InvalidTokenArray::create($name);
    }

    /**
     * Throw a deprecation notice with a standardized deprecation message.
     *
     * @since 1.0.0
     *
     * @param string $method      The name of the method which is deprecated.
     * @param string $version     The version since which the method is deprecated.
     * @param string $replacement What to use instead.
     *
     * @return void
     */
    private static function triggerDeprecation($method, $version, $replacement)
    {
        \trigger_error(
            \sprintf(
                'The %1$s::%2$s() method is deprecated since PHPCSUtils %3$s.'
                . ' Use %4$s instead.',
                __CLASS__,
                $method,
                $version,
                $replacement
            ),
            \E_USER_DEPRECATED
        );
    }

    /**
     * Tokens which can open an array (PHPCS cross-version compatible).
     *
     * For those PHPCS versions which need it, includes `T_OPEN_SQUARE_BRACKET` to allow for
     * handling tokenizer issues related to the retokenization to `T_OPEN_SHORT_ARRAY`.
     * Should only be used selectively.
     *
     * @see \PHPCSUtils\Tokens\Collections::arrayTokensBC()      Related method to retrieve tokens used
     *                                                           for arrays (PHPCS cross-version).
     * @see \PHPCSUtils\Tokens\Collections::shortArrayTokensBC() Related method to retrieve only tokens used
     *                                                           for short arrays (PHPCS cross-version).
     *
     * @since 1.0.0
     *
     * @return array<int|string, int|string>
     */
    public static function arrayOpenTokensBC()
    {
        return self::$arrayOpenTokensBC;
    }

    /**
     * Tokens which are used to create arrays (PHPCS cross-version compatible).
     *
     * For those PHPCS versions which need it, includes `T_OPEN_SQUARE_BRACKET` and `T_CLOSE_SQUARE_BRACKET`
     * to allow for handling tokenizer issues related to the retokenization to `T_OPEN_SHORT_ARRAY`.
     * Should only be used selectively.
     *
     * @see \PHPCSUtils\Tokens\Collections::arrayOpenTokensBC()  Related method to retrieve only the "open" tokens
     *                                                           used for arrays (PHPCS cross-version compatible).
     * @see \PHPCSUtils\Tokens\Collections::shortArrayTokensBC() Related method to retrieve only tokens used
     *                                                           for short arrays (PHPCS cross-version compatible).
     *
     * @since 1.0.0
     *
     * @return array<int|string, int|string>
     */
    public static function arrayTokensBC()
    {
        return self::$arrayTokens;
    }

    /**
     * Token types which can be encountered in OO constant type declaration.
     *
     * @since 1.1.0
     *
     * @return array<int|string, int|string>
     */
    public static function constantTypeTokens()
    {
        $tokens = self::$constantTypeTokens;
        // Self and static are only allowed in enums, but that's not the concern of this method.
        $tokens += self::$ooHierarchyKeywords;
        $tokens += self::namespacedNameTokens();

        return $tokens;
    }

    /**
     * Tokens which can represent function calls and function-call-like language constructs.
     *
     * @see \PHPCSUtils\Tokens\Collections::parameterPassingTokens() Related method.
     *
     * @since 1.0.0
     *
     * @return array<int|string, int|string>
     */
    public static function functionCallTokens()
    {
        // Function calls and class instantiation.
        $tokens              = self::$nameTokens;
        $tokens[\T_VARIABLE] = \T_VARIABLE;

        // Class instantiation only.
        $tokens[\T_ANON_CLASS] = \T_ANON_CLASS;
        $tokens               += self::$ooHierarchyKeywords;

        // As of PHP 8.4, exit()/die() should be treated as function call tokens.
        // Sniffs using this collection should safeguard against use as a constant.
        $tokens[\T_EXIT] = \T_EXIT;

        return $tokens;
    }

    /**
     * Tokens which can open a list construct (PHPCS cross-version compatible).
     *
     * For those PHPCS versions which need it, includes `T_OPEN_SQUARE_BRACKET` to allow for
     * handling tokenizer issues related to the retokenization to `T_OPEN_SHORT_ARRAY`.
     * Should only be used selectively.
     *
     * @see \PHPCSUtils\Tokens\Collections::listTokensBC()      Related method to retrieve tokens used
     *                                                          for lists (PHPCS cross-version).
     * @see \PHPCSUtils\Tokens\Collections::shortListTokensBC() Related method to retrieve only tokens used
     *                                                          for short lists (PHPCS cross-version).
     *
     * @since 1.0.0
     *
     * @return array<int|string, int|string>
     */
    public static function listOpenTokensBC()
    {
        return self::$listOpenTokensBC;
    }

    /**
     * Tokens which are used to create lists (PHPCS cross-version compatible).
     *
     * For those PHPCS versions which need it, includes `T_OPEN_SQUARE_BRACKET` and `T_CLOSE_SQUARE_BRACKET`
     * to allow for handling tokenizer issues related to the retokenization to `T_OPEN_SHORT_ARRAY`.
     * Should only be used selectively.
     *
     * @see \PHPCSUtils\Tokens\Collections::shortListTokensBC() Related method to retrieve only tokens used
     *                                                          for short lists (cross-version).
     *
     * @since 1.0.0
     *
     * @return array<int|string, int|string>
     */
    public static function listTokensBC()
    {
        return self::$listTokens;
    }

    /**
     * Tokens types which can be encountered in a fully, partially or unqualified name.
     *
     * Example:
     * ```php
     * echo namespace\Sub\ClassName::method();
     * ```
     *
     * @since 1.0.0
     *
     * @return array<int|string, int|string>
     */
    public static function namespacedNameTokens()
    {
        $tokens = [
            \T_NS_SEPARATOR => \T_NS_SEPARATOR,
            \T_NAMESPACE    => \T_NAMESPACE,
        ];

        $tokens += self::$nameTokens;

        return $tokens;
    }

    /**
     * Tokens which can be passed to the methods in the PassedParameter class.
     *
     * @see \PHPCSUtils\Utils\PassedParameters
     *
     * @since 1.0.0
     *
     * @return array<int|string, int|string>
     */
    public static function parameterPassingTokens()
    {
        // Function call and class instantiation tokens.
        $tokens = self::functionCallTokens();

        // Function-look-a-like language constructs which can take multiple "parameters".
        $tokens[\T_ISSET] = \T_ISSET;
        $tokens[\T_UNSET] = \T_UNSET;

        // Array tokens.
        $tokens += self::arrayOpenTokensBC();

        return $tokens;
    }

    /**
     * Token types which can be encountered in a parameter type declaration.
     *
     * @since 1.0.0
     *
     * @return array<int|string, int|string>
     */
    public static function parameterTypeTokens()
    {
        $tokens  = self::$parameterTypeTokens;
        $tokens += self::namespacedNameTokens();

        return $tokens;
    }

    /**
     * Token types which can be encountered in a property type declaration.
     *
     * @since 1.0.0
     *
     * @return array<int|string, int|string>
     */
    public static function propertyTypeTokens()
    {
        $tokens  = self::$propertyTypeTokens;
        $tokens += self::namespacedNameTokens();

        return $tokens;
    }

    /**
     * Token types which can be encountered in a return type declaration.
     *
     * @since 1.0.0
     *
     * @return array<int|string, int|string>
     */
    public static function returnTypeTokens()
    {
        $tokens  = self::$returnTypeTokens;
        $tokens += self::$ooHierarchyKeywords;
        $tokens += self::namespacedNameTokens();

        return $tokens;
    }

    /**
     * Tokens which can open a short array or short list (PHPCS cross-version compatible).
     *
     * For those PHPCS versions which need it, includes `T_OPEN_SQUARE_BRACKET` to allow for
     * handling tokenizer issues related to the retokenization to `T_OPEN_SHORT_ARRAY`.
     * Should only be used selectively.
     *
     * @since 1.0.0
     *
     * @return array<int|string, int|string>
     */
    public static function shortArrayListOpenTokensBC()
    {
        return self::$shortArrayListOpenTokensBC;
    }

    /**
     * Tokens which are used for short arrays (PHPCS cross-version compatible).
     *
     * For those PHPCS versions which need it, includes `T_OPEN_SQUARE_BRACKET` and `T_CLOSE_SQUARE_BRACKET`
     * to allow for handling tokenizer issues related to the retokenization to `T_OPEN_SHORT_ARRAY`.
     * Should only be used selectively.
     *
     * @see \PHPCSUtils\Tokens\Collections::arrayTokensBC() Related method to retrieve all tokens used for arrays
     *                                                      (cross-version).
     *
     * @since 1.0.0
     *
     * @return array<int|string, int|string>
     */
    public static function shortArrayTokensBC()
    {
        return self::$shortArrayTokens;
    }

    /**
     * Tokens which are used for short lists (PHPCS cross-version compatible).
     *
     * For those PHPCS versions which need it, includes `T_OPEN_SQUARE_BRACKET` and `T_CLOSE_SQUARE_BRACKET`
     * to allow for handling tokenizer issues related to the retokenization to `T_OPEN_SHORT_ARRAY`.
     * Should only be used selectively.
     *
     * @see \PHPCSUtils\Tokens\Collections::listTokensBC() Related method to retrieve all tokens used for lists
     *                                                     (cross-version).
     *
     * @since 1.0.0
     *
     * @return array<int|string, int|string>
     */
    public static function shortListTokensBC()
    {
        return self::$shortListTokens;
    }
}
