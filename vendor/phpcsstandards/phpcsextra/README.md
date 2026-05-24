# PHPCSExtra

<div aria-hidden="true">

[![Latest Stable Version](https://img.shields.io/packagist/v/phpcsstandards/phpcsextra?label=stable)][phpcsextra-packagist]
[![Release Date of the Latest Version](https://img.shields.io/github/release-date/PHPCSStandards/PHPCSExtra.svg?maxAge=1800)](https://github.com/PHPCSStandards/PHPCSExtra/releases)
:construction:
[![Latest Unstable Version](https://img.shields.io/badge/unstable-dev--develop-e68718.svg?maxAge=2419200)](https://packagist.org/packages/phpcsstandards/phpcsextra#dev-develop)
[![Last Commit to Unstable](https://img.shields.io/github/last-commit/PHPCSStandards/PHPCSExtra/develop.svg)](https://github.com/PHPCSStandards/PHPCSExtra/commits/develop)

[![CS Build Status](https://github.com/PHPCSStandards/PHPCSExtra/actions/workflows/basics.yml/badge.svg?branch=develop)][gha-qa-results]
[![Test Build Status](https://github.com/PHPCSStandards/PHPCSExtra/actions/workflows/test.yml/badge.svg?branch=develop)][gha-test-results]
[![Coverage Status](https://coveralls.io/repos/github/PHPCSStandards/PHPCSExtra/badge.svg)](https://coveralls.io/github/PHPCSStandards/PHPCSExtra)

[![Minimum PHP Version](https://img.shields.io/packagist/dependency-v/phpcsstandards/phpcsextra/php.svg)][phpcsextra-packagist]
[![Tested on PHP 5.4 to 8.4](https://img.shields.io/badge/tested%20on-PHP%205.4%20|%205.5%20|%205.6%20|%207.0%20|%207.1%20|%207.2%20|%207.3%20|%207.4%20|%208.0%20|%208.1%20|%208.2%20|%208.3%20|%208.4-brightgreen.svg?maxAge=2419200)][gha-test-results]

[![License: LGPLv3](https://img.shields.io/github/license/PHPCSStandards/PHPCSExtra)](https://github.com/PHPCSStandards/PHPCSExtra/blob/stable/LICENSE)
![Awesome](https://img.shields.io/badge/awesome%3F-yes!-brightgreen.svg)

</div>

* [Introduction](#introduction)
* [Minimum Requirements](#minimum-requirements)
* [Installation](#installation)
    - [Composer Project-based Installation](#composer-project-based-installation)
    - [Composer Global Installation](#composer-global-installation)
    - [Updating to a newer version](#updating-to-a-newer-version)
* [Features](#features)
* [Sniffs](#sniffs)
    - [Modernize](#modernize)
    - [NormalizedArrays](#normalizedarrays)
    - [Universal](#universal)
* [Contributing](#contributing)
* [License](#license)


## Introduction

PHPCSExtra is a collection of sniffs and standards for use with [PHP_CodeSniffer][phpcs-gh].


## Minimum Requirements

* PHP 5.4 or higher.
* [PHP_CodeSniffer][phpcs-gh] version **3.13.5**/**4.0.1** or higher.
* [PHPCSUtils][phpcsutils-gh] version **1.2.0** or higher.


## Installation

Installing via Composer is highly recommended.

[Composer](https://getcomposer.org/) will automatically install the project dependencies and register the rulesets from PHPCSExtra and other external standards with PHP_CodeSniffer using the [Composer PHPCS plugin][composer-installer-gh].

### Composer Project-based Installation

Run the following from the root of your project:
```bash
composer config allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
composer require --dev phpcsstandards/phpcsextra:"^1.3.0"
```

### Composer Global Installation

Alternatively, you may want to install this standard globally:
```bash
composer global config allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
composer global require --dev phpcsstandards/phpcsextra:"^1.3.0"
```

### Updating to a newer version

If you installed PHPCSExtra using either of the above commands, you can update to a newer version as follows:
```bash
# Project local install
composer update phpcsstandards/phpcsextra --with-dependencies

# Global install
composer global update phpcsstandards/phpcsextra --with-dependencies
```

> If your project includes `require[-dev]`s for the `squizlabs/php_codesniffer`, `phpcsstandards/phpcsutils` or
> `dealerdirect/phpcodesniffer-composer-installer` packages in its `composer.json` file, you may need to use
> `--with-all-dependencies` instead of `--with-dependencies`.
>
> :bulb: **Pro-tip**: Unless your project is a PHPCS standard which actually uses any of these packages directly,
> it is recommended to remove these packages from your own `composer.json` file, in favour of letting PHPCSExtra
> (and potential other external PHPCS standards you use), manage the version requirements for these packages.


## Features

Once this project is installed, you will see three new rulesets in the list of installed standards when you run `vendor/bin/phpcs -i`: `Modernize`, `NormalizedArrays` and `Universal`.

* The `Modernize` ruleset is a standard which checks code for modernization opportunaties.
* The `NormalizedArrays` ruleset is a standard to check the formatting of array declarations.
* The `Universal` ruleset is **NOT** a standard, but a sniff collection.
    It should **NOT** be included in custom rulesets as a standard as it contains contradictory rules.
    Instead include individual sniffs from this standard in a custom project/company ruleset to use them.


## Sniffs

**Legend**:
* :wrench: = Includes auto-fixer.
    _Use the `phpcbf` command to run the fixers._
* :bar_chart: = Includes metrics.
    _Use `phpcs` with `--report=info` to see the metrics._
* :books: = Includes CLI documentation.
    _Use `phpcs` with `--generator=Text` to see the documentation._


### Modernize

#### `Modernize.FunctionCalls.Dirname` :wrench: :books:

This sniff will detect and auto-fix two typical code modernizations which can be made related to the `dirname()` function:
1. Since PHP 5.3, calls to `dirname(__FILE__)` can be replaced by `__DIR__`.
    Errorcode: `Modernize.FunctionCalls.Dirname.FileConstant`.
2. Since PHP 7.0, nested function calls to `dirname()` can be changed to use the `$levels` parameter.
    Errorcode: `Modernize.FunctionCalls.Dirname.Nested`.

If a [`php_version` configuration option][php_version-config] has been passed to PHPCS using either `--config-set` or `--runtime-set`, it will be respected by the sniff.
In effect, this means that the sniff will only report on modernizations which can be applied for the PHP version as configured.


### NormalizedArrays

#### `NormalizedArrays.Arrays.ArrayBraceSpacing` :wrench: :bar_chart: :books:

Enforce consistent spacing for the open/close braces of array declarations.

The sniff allows for having different settings for:
* Space between the array keyword and the open parenthesis for long arrays via the `keywordSpacing` property.
    Accepted values: (int) number of spaces or `false` to turn this check off. Defaults to `0` spaces.
* Spaces on the inside of the braces for empty arrays via the `spacesWhenEmpty` property.
    Accepted values: (string) `newline`, (int) number of spaces or `false` to turn this check off. Defaults to `0` spaces.
* Spaces on the inside of the braces for single-line arrays via the `spacesSingleLine` property;
    Accepted values: (int) number of spaces or `false` to turn this check off. Defaults to `0` spaces.
* Spaces on the inside of the braces for multi-line arrays via the `spacesMultiLine` property.
    Accepted values: (string) `newline`, (int) number of spaces or `false` to turn this check off. Defaults to `newline`.

Note: if any of the above properties are set to `newline`, it is recommended to also include an array indentation sniff. This sniff will not handle the indentation.

#### `NormalizedArrays.Arrays.CommaAfterLast` :wrench: :bar_chart: :books:

Enforce/forbid a comma after the last item in an array declaration.

By default, this sniff will:
* Forbid a comma after the last array item for single-line arrays.
* Enforce a comma after the last array item for multi-line arrays.

This can be changed for each type or array individually by setting the `singleLine` and/or `multiLine` properties in a custom ruleset.

Use any of the following values to change the properties: `enforce`, `forbid` or `skip` to not check the comma after the last array item for a particular type of array.

The default for the `singleLine` property is `forbid`. The default for the `multiLine` property is `enforce`.


### Universal

#### `Universal.Arrays.DisallowShortArraySyntax` :wrench: :bar_chart: :books:

Disallow short array syntax.

In contrast to the PHPCS native `Generic.Arrays.DisallowShortArraySyntax` sniff, this sniff will ignore short list syntax and not cause parse errors when the fixer is used.

#### `Universal.Arrays.DuplicateArrayKey` :books:

Detects duplicate array keys in array declarations.

The sniff will make a distinction between keys which will be duplicate in all PHP version and (numeric) keys which will only be a duplicate key in [PHP < 8.0 or PHP >= 8.0][php-rfc-negative_array_index].

If a [`php_version` configuration option][php_version-config] has been passed to PHPCS using either `--config-set` or `--runtime-set`, it will be respected by the sniff and only report duplicate keys for the configured PHP version.

[php-rfc-negative_array_index]: https://wiki.php.net/rfc/negative_array_index

#### `Universal.Arrays.MixedArrayKeyTypes` :books:

Best practice sniff: don't use a mix of integer and string keys for array items.

#### `Universal.Arrays.MixedKeyedUnkeyedArray` :books:

Best practice sniff: don't use a mix of keyed and unkeyed array items.

#### `Universal.Attributes.BracketSpacing` :wrench: :bar_chart: :books:

Standardize the amount of spaces on the inside of attribute block brackets.

* This sniff contains an `spacing` property to set the amount of spaces the sniff should check for.
    Accepted values: (int) number of spaces. Defaults to `0` (spaces).
* By default, the sniff does not make an exception for a new line after the attribute opener or before the attribute closer.
    This can be changed by setting the `ignoreNewlines` property to `true`.
    Accepted values: (bool) `true`|`false`. Defaults to `false`.
    When the `ignoreNewlines` property is set to `true` and a new line is found, the sniff will flag stray blank lines at the start and/or end of an attribute block.

#### `Universal.Attributes.DisallowAttributeParentheses` :wrench: :bar_chart: :books:

Disallow the use of parentheses when instantiating an attribute without passing parameters.

#### `Universal.Attributes.RequireAttributeParentheses` :wrench: :bar_chart: :books:

Require the use of parentheses when instantiating an attribute, whether parameters are passed or not.

#### `Universal.Attributes.TrailingComma` :wrench: :bar_chart: :books:

Require a trailing comma for multi-line, multi-attribute attribute blocks and forbid trailing commas in single-line attribute blocks and in multi-line attributes containing only a single attribute.

#### `Universal.Classes.DisallowAnonClassParentheses` :wrench: :bar_chart: :books:

Disallow the use of parentheses when declaring an anonymous class without passing parameters.

#### `Universal.Classes.RequireAnonClassParentheses` :wrench: :bar_chart: :books:

Require the use of parentheses when declaring an anonymous class, whether parameters are passed or not.

#### `Universal.Classes.DisallowFinalClass` :wrench: :bar_chart: :books:

Disallow classes being declared `final`.

#### `Universal.Classes.RequireFinalClass` :wrench: :bar_chart: :books:

Require all non-`abstract` classes to be declared `final`.

:warning: **Warning**: the auto-fixer for this sniff _may_ have unintended side-effects for applications and should be used with care!
This is considered a **_risky_ fixer**.

#### `Universal.Classes.ModifierKeywordOrder` :wrench: :bar_chart: :books:

Require a consistent modifier keyword order for class declarations.

* This sniff contains an `order` property to specify the preferred order.
    Accepted values: (string) `'extendability readonly'`|`'readonly extendability'`. Defaults to `'extendability readonly'`.

#### `Universal.CodeAnalysis.ConstructorDestructorReturn` :wrench: :books:

* Disallows return type declarations on constructor/destructor methods - error code: `ReturnTypeFound`, auto-fixable.
* Discourages constructor/destructor methods returning a value - error code: `ReturnValueFound`.

If a [`php_version` configuration option][php_version-config] has been passed to PHPCS using either `--config-set` or `--runtime-set`, it will be respected by the sniff.
In effect, this means that the sniff will only report on PHP4-style constructors if the configured PHP version is less than 8.0.

#### `Universal.CodeAnalysis.ForeachUniqueAssignment` :wrench: :books:

Detects `foreach` control structures which use the same variable for both the key as well as the value assignment as this will lead to unexpected - and most likely unintended - behaviour.

Note: The fixer will maintain the existing behaviour of the code. This may not be the _intended_ behaviour.

#### `Universal.CodeAnalysis.NoDoubleNegative` :wrench: :books:

Detects double negation `!!` in code, which is effectively the same as a boolean cast, but with a much higher cognitive load.
Also detects triple negation `!!!`, which is effectively the same as a single negation.

The sniff has modular error codes to allow for disabling individual checks. The error codes are: `FoundDouble`, `FoundDoubleWithInstanceof` (not auto-fixable) and `FoundTriple`.

#### `Universal.CodeAnalysis.NoEchoSprintf` :wrench: :books:

Detects use of the inefficient `echo [v]sprintf(...);` combi. Use `[v]printf()` instead.

#### `Universal.CodeAnalysis.StaticInFinalClass` :wrench: :books:

Detects using `static` instead of `self` in OO constructs which are `final`.

* The sniff has modular error codes to allow for making exceptions based on the type of use for `static`.
    The available error codes are: `ReturnType`, `InstanceOf`, `NewInstance`, `ScopeResolution`.

#### `Universal.Constants.LowercaseClassResolutionKeyword` :wrench: :bar_chart: :books:

Enforce that the `class` keyword when used for class name resolution, i.e. `::class`, is in lowercase.

#### `Universal.Constants.ModifierKeywordOrder` :wrench: :bar_chart: :books:

Require a consistent modifier keyword order for OO constant declarations.

* This sniff contains an `order` property to specify the preferred order.
    Accepted values: (string) `'final visibility'`|`'visibility final'`. Defaults to `'final visibility'`.

#### `Universal.Constants.UppercaseMagicConstants` :wrench: :bar_chart: :books:

Enforce uppercase when using PHP native magic constants, like `__FILE__` et al.

#### `Universal.ControlStructures.DisallowAlternativeSyntax` :wrench: :bar_chart: :books:

Disallow using the alternative syntax for control structures.

* This sniff contains an `allowWithInlineHTML` property to allow alternative syntax when inline HTML is used within the control structure. In all other cases, the use of the alternative syntax will still be disallowed.
    Accepted values: (bool) `true`|`false`. Defaults to `false`.
* The sniff has modular error codes to allow for making exceptions based on specific control structures and/or specific control structures in combination with inline HTML.
    The error codes follow the following pattern: `Found[ControlStructure][WithInlineHTML]`. Examples: `FoundIf`, `FoundSwitchWithInlineHTML`.

#### `Universal.ControlStructures.DisallowLonelyIf` :wrench: :books:

Disallow `if` statements as the only statement in an `else` block.

Note: This sniff will not fix the indentation of the "inner" code.
It is strongly recommended to run this sniff together with the `Generic.WhiteSpace.ScopeIndent` sniff to get the correct indentation.

#### `Universal.ControlStructures.IfElseDeclaration` :wrench: :bar_chart: :books:

Verify that else(if) statements with braces are on a new line.

#### `Universal.Files.SeparateFunctionsFromOO` :bar_chart: :books:

Enforce for a file to either declare (global/namespaced) functions or declare OO structures, but not both.

* Nested function declarations, i.e. functions declared within a function/method will be disregarded for the purposes of this sniff.
    The same goes for anonymous classes, closures and arrow functions.
* Note: This sniff has no opinion on side effects. If you want to sniff for those, use the PHPCS native `PSR1.Files.SideEffects` sniff.
* Also note: This sniff has no opinion on multiple OO structures being declared in one file.
    If you want to sniff for that, use the PHPCS native `Generic.Files.OneObjectStructurePerFile` sniff.

#### `Universal.FunctionDeclarations.NoLongClosures` :bar_chart: :books:

Detects "long" closures and recommends using a named function instead.

The sniff is configurable by setting any of the following properties in a custom ruleset:
* `recommendedLines` (int): determines when a warning will be thrown.
    Defaults to `5`, meaning a warning with the errorcode `ExceedsRecommended` will be thrown if the closure is more than 5 lines long.
* `maxLines` (int): determines when an error will be thrown.
    Defaults to `8`, meaning that an error with the errorcode `ExceedsMaximum` will be thrown if the closure is more than 8 lines long.
* `ignoreCommentLines` (bool): whether or not comment-only lines should be ignored for the lines count.
    Defaults to `true`.
* `ignoreEmptyLines` (bool): whether or not blank lines should be ignored for the lines count.
    Defaults to `true`.

#### `Universal.FunctionDeclarations.RequireFinalMethodsInTraits` :wrench: :bar_chart: :books:

Enforce non-private, non-abstract methods in traits to be declared as `final`.

The available error codes are: `NonFinalMethodFound` and `NonFinalMagicMethodFound`.

#### `Universal.Lists.DisallowLongListSyntax` :wrench: :books:

Disallow the use of long `list`s.

> For metrics about the use of long lists vs short lists, please use the `Universal.Lists.DisallowShortListSyntax` sniff.

#### `Universal.Lists.DisallowShortListSyntax` :wrench: :bar_chart: :books:

Disallow the use of short lists.

#### `Universal.Namespaces.DisallowDeclarationWithoutName` :bar_chart: :books:

Disallow namespace declarations without a namespace name.

This sniff only applies to namespace declarations using the curly brace syntax.

#### `Universal.Namespaces.DisallowCurlyBraceSyntax` :bar_chart: :books:

Disallow the use of the alternative namespace declaration syntax using curly braces.

#### `Universal.Namespaces.EnforceCurlyBraceSyntax` :bar_chart: :books:

Enforce the use of the alternative namespace syntax using curly braces.

#### `Universal.Namespaces.OneDeclarationPerFile` :books:

Disallow the use of multiple namespaces within a file.

#### `Universal.NamingConventions.NoReservedKeywordParameterNames` :books:

Disallow function parameters using reserved keywords as names, as this can quickly become confusing when people use them in function calls using named parameters

* The sniff has modular error codes to allow for making exceptions for specific keywords.
    The error codes follow the following pattern: `[keyword]Found`.

#### `Universal.OOStructures.AlphabeticExtendsImplements` :wrench: :bar_chart: :books:

Enforce that the names used in a class/enum "implements" statement or an interface "extends" statement are listed in alphabetic order.

* This sniff contains a `orderby` property to determine the sort order to use for the statement.
    If all names used are unqualified, the sort order won't make a difference.
    However, if one or more of the names are partially or fully qualified, the chosen sort order will determine how the sorting between unqualified, partially and fully qualified names is handled.
    The sniff supports two sort order options:
    - _'name'_ : sort by the interface name only (default);
    - _'full'_ : sort by the full name as used in the statement (without leading backslash).
    In both cases, the sorting will be done using natural sort, case-insensitive.
* The sniff has modular error codes to allow for selective inclusion/exclusion:
    - `ImplementsWrongOrder` - for "class implements" statements.
    - `ImplementsWrongOrderWithComments` - for "class implements" statements interlaced with comments. These will not be auto-fixed.
    - `ExtendsWrongOrder` - for "interface extends" statements.
    - `ExtendsWrongOrderWithComments` - for "interface extends" statements interlaced with comments. These will not be auto-fixed.
* When fixing, the existing spacing between the names in an `implements`/`extends` statement will not be maintained.
    The fixer will separate each name with a comma and one space.
    If alternative formatting is desired, a sniff which will check and fix the formatting should be added to the ruleset.

#### `Universal.Operators.ConcatPosition` :wrench: :bar_chart: :books:

Enforce that the concatenation operator for multi-line concatenations is in a preferred position, either always at the start of the next line or always at the end of the previous line.

* This sniff contains an `allowOnly` property to set the preferred position for the operator.
    Accepted values: (string) `"start"` or `"end"`. Defaults to `"start"`.
* Note: mid-line concatenation is still allowed and will not be flagged by this sniff.

#### `Universal.Operators.DisallowLogicalAndOr` :bar_chart: :books:

Enforce the use of the boolean `&&` and `||` operators instead of the logical `and`/`or` operators.

:information_source: Note: as the [operator precedence](https://www.php.net/language.operators.precedence) of the logical operators is significantly lower than the operator precedence of boolean operators, this sniff does not contain an auto-fixer.

#### `Universal.Operators.DisallowShortTernary` :bar_chart: :books:

Disallow the use of short ternaries `?:`.

While short ternaries are useful when used correctly, the principle of them is often misunderstood and they are more often than not used incorrectly, leading to hard to debug issues and/or PHP warnings/notices.

#### `Universal.Operators.DisallowStandalonePostIncrementDecrement` :wrench: :bar_chart: :books:

* Disallow the use of post-in/decrements in stand-alone statements - error codes: `PostDecrementFound` and `PostIncrementFound`.
    Using pre-in/decrement is more in line with the principle of least astonishment and prevents bugs when code gets moved around at a later point in time.
* Discourages the use of multiple increment/decrement operators in a stand-alone statement - error code: `MultipleOperatorsFound`.

#### `Universal.Operators.StrictComparisons` :wrench: :bar_chart: :books:

Enforce the use of strict comparisons.

:warning: **Warning**: the auto-fixer for this sniff _may_ cause bugs in applications and should be used with care!
This is considered a **_risky_ fixer**.

#### `Universal.Operators.TypeSeparatorSpacing` :wrench: :bar_chart: :books:

Enforce spacing rules around the union, intersection and DNF type operators.
* No space on either side of a union or intersection type operator.
* No space on the inside of DNF type parenthesis or before/after if the previous/next "thing" is part of the type.
* One space before a DNF open parenthesis when it is at the start of a type.
* One space after a DNF close parenthesis when it is at the end of a type.

The available error codes are: `UnionTypeSpacesBefore`, `UnionTypeSpacesAfter`, `IntersectionTypeSpacesBefore`, `IntersectionTypeSpacesAfter`, `DNFOpenSpacesBefore`, `DNFOpenSpacesAfter`, `DNFCloseTypeSpacesBefore`, `DNFCloseTypeSpacesAfter`.

#### `Universal.PHP.DisallowExitDieParentheses` :wrench: :bar_chart: :books:

Disallow the use of parentheses when calling `exit` or `die` without passing parameters.

#### `Universal.PHP.RequireExitDieParentheses` :wrench: :bar_chart: :books:

Require the use of parentheses when calling `exit` or `die`, whether parameters are passed or not.

#### `Universal.PHP.LowercasePHPTag` :wrench: :bar_chart: :books:

Enforces that the "PHP" in a PHP open tag is lowercase.

#### `Universal.PHP.NoFQNTrueFalseNull` :wrench: :books:

Forbids using `true`, `false` and `null` as fully qualified constants.

#### `Universal.PHP.OneStatementInShortEchoTag` :wrench: :books:

Disallow short open echo tags `<?=` containing more than one PHP statement.

#### `Universal.UseStatements.DisallowMixedGroupUse` :wrench: :bar_chart: :books:

Disallow group use statements which import a combination of namespace/OO construct, functions and/or constants in one statement.

Note: the fixer will use a semi-standardized format for group use statements.
If there are more specific requirements for the formatting of group use statements, the ruleset configurator should ensure that additional sniffs are included in the ruleset to enforce the required format.

#### `Universal.UseStatements.DisallowUseClass` :bar_chart: :books:

Forbid using import `use` statements for classes/traits/interfaces/enums.

Individual sub-types - with/without alias, global imports, imports from the same namespace - can be forbidden by including that specific error code and/or allowed including the whole sniff and excluding specific error codes.

The available error codes are: `FoundWithoutAlias`, `FoundWithAlias`, `FromGlobalNamespace`, `FromGlobalNamespaceWithAlias`, `FromSameNamespace` and `FromSameNamespaceWithAlias`.

#### `Universal.UseStatements.DisallowUseConst` :bar_chart: :books:

Forbid using import `use` statements for constants.

See [`Universal.UseStatements.DisallowUseClass`](#universalusestatementsdisallowuseclass-bar_chart-books) for information on the error codes.

#### `Universal.UseStatements.DisallowUseFunction` :bar_chart: :books:

Forbid using import `use` statements for functions.

See [`Universal.UseStatements.DisallowUseClass`](#universalusestatementsdisallowuseclass-bar_chart-books)  for information on the error codes.

#### `Universal.UseStatements.KeywordSpacing` :wrench: :bar_chart: :books:

Enforce the use of a single space after the `use`, `function`, `const` keywords and both before and after the `as` keyword in import `use` statements.

Companion sniff to the PHPCS native `Generic.WhiteSpace.LanguageConstructSpacing` sniff which doesn't cover the `function`, `const` and `as` keywords when used in an import `use` statement.

The sniff has modular error codes to allow for disabling individual checks. The error codes are: `SpaceAfterUse`, `SpaceAfterFunction`, `SpaceAfterConst`, `SpaceBeforeAs` and `SpaceAfterAs`.

#### `Universal.UseStatements.LowercaseFunctionConst` :wrench: :bar_chart: :books:

Enforce that `function` and `const` keywords when used in an import `use` statement are always lowercase.

Companion sniff to the PHPCS native `Generic.PHP.LowerCaseKeyword` sniff which doesn't cover these keywords when used in an import `use` statement.

#### `Universal.UseStatements.NoLeadingBackslash` :wrench: :bar_chart: :books:

Verify that a name being imported in an import `use` statement does not start with a leading backslash.

Names in import `use` statements should always be fully qualified, so a leading backslash is not needed and it is strongly recommended not to use one.

This sniff handles all types of import use statements supported by PHP, in contrast to other sniffs for the same in, for instance, the PHPCS native `PSR12` or the Slevomat standard, which are incomplete.

#### `Universal.UseStatements.NoUselessAliases` :wrench: :books:

Detects useless aliases in import use statements.

Aliasing something to the same name as the original construct is considered useless (though allowed in PHP).
Note: as OO and function names in PHP are case-insensitive, aliasing to the same name, using a different case is also considered useless.

#### `Universal.WhiteSpace.AnonClassKeywordSpacing` :wrench: :bar_chart: :books:

Standardize the amount of spacing between the `class` keyword and the open parenthesis (if any) for anonymous class declarations.

* This sniff contains an `spacing` property to set the amount of spaces the sniff should check for.
    Accepted values: (int) number of spaces. Defaults to `0` (spaces).

#### `Universal.WhiteSpace.CommaSpacing` :wrench: :bar_chart: :books:

Enforce that there is no space before a comma and exactly one space, or a new line, after a comma.

Additionally, the sniff also enforces that the comma should follow the code and not be placed after a trailing comment.

For the spacing part, the sniff makes the following exceptions:
1. A comma preceded or followed by a parenthesis, curly or square bracket.
    These will not be flagged to prevent conflicts with sniffs handling spacing around braces.
2. A comma preceded or followed by another comma, like for skipping items in a list assignment.
    These will not be flagged.

* The sniff has a separate error code - `TooMuchSpaceAfterCommaBeforeTrailingComment` - for when a comma is found with more than one space after it, followed by a trailing comment.
    Exclude this error code to allow trailing comment alignment.
* The other error codes the sniff uses, `SpaceBefore`, `TooMuchSpaceAfter` and `NoSpaceAfter`, may be suffixed with a context indicator - `*InFunctionDeclaration`, `*InFunctionCall`, `*InClosureUse`, `*InAttributeBlock` or `*InDeclare` -.
    This allows for disabling the sniff in any of these contexts by excluding the specific suffixed error codes.
* The sniff will respect a potentially set [`php_version` configuration option][php_version-config] when deciding how to handle the spacing after a heredoc/nowdoc closer.
    In effect, this means that the sniff will enforce a new line between the closer and a comma if the configured PHP version is less than 7.3.
    When no `php_version` is passed, the sniff will handle the spacing between a heredoc/nowdoc closer and a comma based on whether it is a cross-version compatible heredoc/nowdoc (enforce new line) or a flexible heredoc/nowdoc (enforce no space).

#### `Universal.WhiteSpace.DisallowInlineTabs` :wrench: :books:

Enforce using spaces for mid-line alignment.

While tab versus space based indentation is a question of preference, for mid-line alignment, spaces should always be preferred, as using tabs will result in inconsistent formatting depending on the dev-user's chosen tab width.

> _This sniff is especially useful for tab-indentation based standards which use the `Generic.Whitespace.DisallowSpaceIndent` sniff to enforce this._
>
> **DO** make sure to set the PHPCS native `tab-width` configuration for the best results.
> ```xml
>    <arg name="tab-width" value="4"/>
> ```
>
> The PHPCS native `Generic.Whitespace.DisallowTabIndent` sniff (used for space-based standards) oversteps its reach and silently does mid-line tab to space replacements as well.
> However, the sister-sniff `Generic.Whitespace.DisallowSpaceIndent` leaves mid-line tabs/spaces alone.
> This sniff fills that gap.

#### `Universal.WhiteSpace.FirstClassCallableSpacing` :wrench: :bar_chart: :books:

Standardize the amount of spacing around the `...` in a first class callable.

* This sniff contains an `spacing` property to set the amount of spaces the sniff should check for.
    Accepted values: (int) number of spaces. Defaults to `0` (spaces).

#### `Universal.WhiteSpace.PrecisionAlignment` :wrench: :books:

Enforce code indentation to always be a multiple of a tabstop, i.e. disallow precision alignment.

Note:
* This sniff does not concern itself with tabs versus spaces.
    It is recommended to use the sniff in combination with the PHPCS native `Generic.WhiteSpace.DisallowTabIndent` or the `Generic.WhiteSpace.DisallowSpaceIndent` sniff.
* When using this sniff with tab-based standards, please ensure that the `tab-width` is set and either don't set the `$indent` property or set it to the tab-width (or a multiple thereof).
* The fixer works based on "best guess" and may not always result in the desired indentation. Combine this sniff with the `Generic.WhiteSpace.ScopeIndent` sniff for more precise indentation fixes.

The behaviour of the sniff is customizable via the following properties:
* `indent`: the indent used for the codebase.
    Accepted values: (int|null) number of spaces. Defaults to `null`.
    If this property is not set, the sniff will look to the `--tab-width` CLI value.
    If that also isn't set, the default tab-width of `4` will be used.
* `ignoreAlignmentBefore`: allows for providing a list of token names for which (preceding) precision alignment should be ignored.
    Accepted values: (`array<string>`) token constant names. Defaults to an empty array.
    Usage example:
    ```xml
    <rule ref="Universal.WhiteSpace.PrecisionAlignment">
       <properties>
           <property name="ignoreAlignmentBefore" type="array">
               <!-- Ignore precision alignment in inline HTML -->
               <element value="T_INLINE_HTML"/>
               <!-- Ignore precision alignment in multiline chained method calls. -->
               <element value="T_OBJECT_OPERATOR"/>
               <element value="T_NULLSAFE_OBJECT_OPERATOR"/>
           </property>
       </properties>
    </rule>
   ```
* `ignoreBlankLines`: whether or not potential trailing whitespace on otherwise blank lines should be examined or ignored.
    It is recommended to only set this to `false` if the standard including this sniff does not include the `Squiz.WhiteSpace.SuperfluousWhitespace` sniff (which is included in most standards).
    Accepted values: (bool)`true`|`false`. Defaults to `true`.


## Contributing

Contributions to this project are welcome. Clone the repo, branch off from `develop`, make your changes, commit them and send in a pull request.

If unsure whether the changes you are proposing would be welcome, open an issue first to discuss your proposal.


## License

This code is released under the [GNU Lesser General Public License (LGPLv3)](LICENSE).


[phpcsextra-packagist]:  https://packagist.org/packages/phpcsstandards/phpcsextra
[gha-qa-results]:        https://github.com/PHPCSStandards/PHPCSExtra/actions/workflows/basics.yml
[gha-test-results]:      https://github.com/PHPCSStandards/PHPCSExtra/actions/workflows/test.yml

[phpcs-gh]:              https://github.com/PHPCSStandards/PHP_CodeSniffer
[phpcsutils-gh]:         https://github.com/PHPCSStandards/PHPCSUtils
[composer-installer-gh]: https://github.com/PHPCSStandards/composer-installer

[php_version-config]:    https://github.com/PHPCSStandards/PHP_CodeSniffer/wiki/Configuration-Options#setting-the-php-version
