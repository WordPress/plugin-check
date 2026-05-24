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

use PHPCSUtils\Exceptions\TypeError;

/**
 * Utility functions for use when examining type strings.
 *
 * The methods in this class are intended to be used with type strings as returned by:
 * - [BC]File::getMethodParameters()['type_hint']
 * - [BC]File::getMethodProperties()['return_type']
 * - [BC]File::getMemberProperties()['type']
 * - Constants::getProperties()['type']
 * - FunctionDeclarations::getParameters()['type_hint']
 * - FunctionDeclarations::getProperties()['return_type']
 * - Variables::getMemberProperties()['type']
 *
 * Notes:
 * - Type strings as retrieved from the above listed sources will not contain any whitespace,
 *   so the methods in this class have limited or no handling for surrounding or internal whitespace.
 * - The behaviour with type strings retrieved by other means, or non-type strings, is undefined.
 *   :warning: Using these methods with type strings retrieved from docblocks is **strongly discouraged**.
 * - The `is*()` methods will **not** check if the type string provided is **_valid_**, as doing so would inhibit
 *   what sniffs can flag.
 *   The `is*()` methods will only look at the _form_ of the type string to determine if it _could_ be valid
 *   for a certain type.
 *   - Use the {@see \PHPCSUtils\Utils\NamingConventions::isValidIdentifierName()} method if additional validity
 *     checks are needed on the individual "types" seen in a type string.
 *   - And, if needed, use token walking on the tokens of the type to determine whether a type string actually
 *     complies with the type rules as set by PHP.
 *
 * @see \PHPCSUtils\BackCompat\BCFile::getMethodParameters()
 * @see \PHPCSUtils\BackCompat\BCFile::getMethodProperties()
 * @see \PHPCSUtils\BackCompat\BCFile::getMemberProperties()
 * @see \PHPCSUtils\Utils\Constants::getProperties()
 * @see \PHPCSUtils\Utils\FunctionDeclarations::getParameters()
 * @see \PHPCSUtils\Utils\FunctionDeclarations::getProperties()
 * @see \PHPCSUtils\Utils\Variables::getMemberProperties()
 *
 * @since 1.1.0
 */
final class TypeString
{

    /**
     * Regex to filter out some matches for invalid "DNF-lookalike" types.
     *
     * A type string should **not** match against this regex to be considered potentially valid.
     *
     * @internal
     *
     * @since 1.1.0
     *
     * @var string
     */
    const INVALID_DNF_REGEX = '`(?:
        [()|]\s*&          # Make sure that a "&" is always preceeded by something different than "(", ")" or "|".
        |
        &\s*[()|]          # Make sure that a "&" is always followed by something different than "(", ")" or "|".
        |
        \S+\s*[^|\s]\s*\(  # Make sure that a "(" not at the start of the type string is always preceeded by something + "|".
        |
        \)\s*[^|\s]\s*\S+  # Make sure that a ")" not at the end of the type string is always followed by "|" + something.
    )`x';

    /**
     * A list of all keyword based types in PHP.
     *
     * The names are listed in lowercase as type names in PHP are case-insensitive
     * and comparisons against this list should therefore always be done in a case-insensitive manner.
     *
     * @since 1.1.0
     *
     * @var array<string, string>
     */
    private static $keywordTypes = [
        'array'    => 'array',
        'bool'     => 'bool',
        'callable' => 'callable',
        'false'    => 'false',
        'float'    => 'float',
        'int'      => 'int',
        'iterable' => 'iterable',
        'mixed'    => 'mixed',
        'never'    => 'never',
        'null'     => 'null',
        'object'   => 'object',
        'parent'   => 'parent',
        'self'     => 'self',
        'static'   => 'static',
        'string'   => 'string',
        'true'     => 'true',
        'void'     => 'void',
    ];

    /**
     * Retrieve a list of all PHP native keyword types.
     *
     * @since 1.1.0
     *
     * @return array<string, string> Key and value both contain the type name in lowercase.
     */
    public static function getKeywordTypes()
    {
        return self::$keywordTypes;
    }

    /**
     * Check if a singular type is a PHP native keyword based type.
     *
     * @since 1.1.0
     *
     * @param string $type The singular type.
     *
     * @return bool
     */
    public static function isKeyword($type)
    {
        if (\is_string($type) === false) {
            return false;
        }

        $typeLC = \strtolower(\ltrim(\trim($type), '\\'));
        return isset(self::$keywordTypes[$typeLC]);
    }

    /**
     * Normalize the case for a single type.
     *
     * - Types which are recognized PHP "keyword" types will be returned in lowercase.
     * - Types which are recognized PHP "keyword" types and are incorrectly provided as fully qualified
     *   (typically: true/false/null) will be returned as unqualified.
     * - Class/Interface/Enum names will be returned in their original case.
     *
     * @since 1.1.0
     * @since 1.1.2 Will now also normalize (illegal) FQN true/false/null to unqualified.
     *
     * @param string $type Type to normalize the case for.
     *
     * @return string The case-normalized type or an empty string if the input was invalid.
     */
    public static function normalizeCase($type)
    {
        if (\is_string($type) === false) {
            return '';
        }

        if (self::isKeyword($type)) {
            return \strtolower(\ltrim($type, '\\'));
        }

        return $type;
    }

    /**
     * Check if a type string represents a plain, singular type.
     *
     * Note: Nullable types are not considered plain, singular types for the purposes of this method.
     *
     * @since 1.1.0
     *
     * @param string $typeString Type string.
     *
     * @return bool
     */
    public static function isSingular($typeString)
    {
        if (\is_string($typeString) === false) {
            return false;
        }

        $typeString = \trim($typeString);

        return empty($typeString) === false
            && \strpos($typeString, '?') === false
            && \strpos($typeString, '|') === false
            && \strpos($typeString, '&') === false
            && \strpos($typeString, '(') === false
            && \strpos($typeString, ')') === false;
    }

    /**
     * Check if a type string represents a nullable type.
     *
     * A nullable type in the context of this method is a type which
     * - starts with the nullable operator and has something after it which is being made nullable;
     * - or contains `null` as part of a union or DNF type.
     *
     * A stand-alone `null` type is not considered a nullable type, but a singular type.
     *
     * @since 1.1.0
     *
     * @param string $typeString Type string.
     *
     * @return bool
     */
    public static function isNullable($typeString)
    {
        if (\is_string($typeString) === false) {
            return false;
        }

        $typeString = \trim($typeString);
        if (empty($typeString) === true) {
            return false;
        }

        // Check for plain nullable type with something which is being made nullable.
        if (\preg_match('`^\?\s*[^|&()?\s]+`', $typeString) === 1) {
            return true;
        }

        // Check for nullable union type.
        $matched = \preg_match(
            '`(?<before>^|[^|&(?\s]+\s*\|)\s*[\\\\]?null\s*(?<after>\|\s*[^|&)?\s]+|$)`i',
            $typeString,
            $matches
        );
        return ($matched === 1
            && (empty($matches['before']) === false || empty($matches['after']) === false));
    }

    /**
     * Check if a type string represents a pure union type.
     *
     * Note: DNF types are not considered union types for the purpose of this method.
     *
     * @since 1.1.0
     *
     * @param string $typeString Type string.
     *
     * @return bool
     */
    public static function isUnion($typeString)
    {
        return \is_string($typeString)
            && \strpos($typeString, '?') === false
            && \strpos($typeString, '|') !== false
            && \strpos($typeString, '&') === false
            && \strpos($typeString, '(') === false
            && \strpos($typeString, ')') === false
            // Make sure there is always something before and after each |.
            && \preg_match('`^[^|&()?\s]+(\s*\|\s*[^|&()?\s]+)+$`', $typeString) === 1;
    }

    /**
     * Check if a type string represents a pure intersection type.
     *
     * Note: DNF types are not considered intersection types for the purpose of this method.
     *
     * @since 1.1.0
     *
     * @param string $typeString Type string.
     *
     * @return bool
     */
    public static function isIntersection($typeString)
    {
        return \is_string($typeString)
            && \strpos($typeString, '?') === false
            && \strpos($typeString, '|') === false
            && \strpos($typeString, '&') !== false
            && \strpos($typeString, '(') === false
            && \strpos($typeString, ')') === false
            // Make sure there is always something before and after each &.
            && \preg_match('`^[^|&()?\s]+(\s*&\s*[^|&()?\s]+)+$`', $typeString) === 1;
    }

    /**
     * Check if a type string represents a disjunctive normal form (DNF) type.
     *
     * This check for a strict
     *
     * @since 1.1.0
     *
     * @param string $typeString Type string.
     *
     * @return bool
     */
    public static function isDNF($typeString)
    {
        return \is_string($typeString)
            && \strpos($typeString, '?') === false
            && \strpos($typeString, '|') !== false
            && \strpos($typeString, '&') !== false
            && \strpos($typeString, '(') !== false
            && \strpos($typeString, ')') !== false
            // Now make sure that it is not a definitely invalid format.
            && \preg_match(self::INVALID_DNF_REGEX, $typeString) !== 1;
    }

    /**
     * Split a type string to its individual types and optionally normalize the case of the types.
     *
     * @since 1.1.0
     *
     * @param string $typeString Type to split.
     * @param bool   $normalize  Whether or not to normalize the case of types.
     *                           Defaults to true.
     *
     * @return array<string> List containing all seen types in the order they were encountered.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError If passed $typeString is not a string.
     */
    public static function toArray($typeString, $normalize = true)
    {
        if (\is_string($typeString) === false) {
            throw TypeError::create(1, '$typeString', 'string', $typeString);
        }

        if (\trim($typeString) === '') {
            return [];
        }

        $addNull = false;
        if ($typeString[0] === '?') {
            $addNull    = true;
            $typeString = \substr($typeString, 1);
        }

        $typeString = \preg_replace('`\s+`', '', $typeString);
        $types      = \preg_split('`[|&()]+`', $typeString, -1, \PREG_SPLIT_NO_EMPTY);

        // Normalize the types.
        if ($normalize === true) {
            $types = \array_map([__CLASS__, 'normalizeCase'], $types);
        }

        if ($addNull === true) {
            \array_unshift($types, 'null');
        }

        return $types;
    }

    /**
     * Split a type string to the unique types included and optionally normalize the case of the types.
     *
     * @since 1.1.0
     *
     * @param string $typeString Type to split.
     * @param bool   $normalize  Whether or not to normalize the case of types.
     *                           Defaults to true.
     *
     * @return array<string, string> Associative array with the unique types as both the key as well as the value.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError If passed $typeString is not a string.
     */
    public static function toArrayUnique($typeString, $normalize = true)
    {
        $types = self::toArray($typeString, $normalize);
        return  \array_combine($types, $types);
    }

    /**
     * Filter a list of types down to only the keyword based types.
     *
     * @since 1.1.0
     *
     * @param array<int|string, string> $types Array of types.
     *                                         Typically, this is an array as retrieved from the
     *                                         {@see TypeString::toArray()} method or the
     *                                         {@see TypeString::toArrayUnique()} method.
     *
     * @return array<int|string, string> Array with only the PHP native keyword based types.
     *                                   The result may be an empty array if the input array didn't contain
     *                                   any keyword based types or if the input was invalid.
     */
    public static function filterKeywordTypes(array $types)
    {
        return \array_filter($types, [__CLASS__, 'isKeyword']);
    }

    /**
     * Filter a list of types down to only the OO name based types.
     *
     * @since 1.1.0
     *
     * @param array<int|string, string> $types Array of types.
     *                                         Typically, this is an array as retrieved from the
     *                                         {@see TypeString::toArray()} method or the
     *                                         {@see TypeString::toArrayUnique()} method.
     *
     * @return array<int|string, string> Array with only the OO name based types.
     *                                   The result may be an empty array if the input array didn't contain
     *                                   any OO name based types or if the input was invalid.
     */
    public static function filterOOTypes(array $types)
    {
        return \array_filter(
            $types,
            static function ($type) {
                return \is_string($type) === true && self::isKeyword($type) === false;
            }
        );
    }
}
