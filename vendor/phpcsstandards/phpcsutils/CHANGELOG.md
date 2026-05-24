# Change Log for PHPCSUtils for PHP CodeSniffer

All notable changes to this project will be documented in this file.

This projects adheres to [Keep a CHANGELOG](https://keepachangelog.com/) and uses [Semantic Versioning](https://semver.org/).


## [Unreleased]

_Nothing yet._


## [1.2.1] - 2025-11-17

### Fixed

#### Abstract Sniffs

* The `AbstractArrayDeclarationSniff::getActualArrayKey()` method could cause deprecation notices and even fatal errors, when a PHPCS scan would be run on a different PHP version than the "code under scan" is targetting and the "code under scan" contained deprecated/removed type casts. [#733]

[#733]: https://github.com/PHPCSStandards/PHPCSUtils/pull/733


## [1.2.0] - 2025-11-11

### Added

#### Utils

* New [`PHPCSUtils\Utils\AttributeBlock`][`AttributeBlock`] class: Utility functions to examine attribute blocks. [#720], [#729]
    For the purposes of these utilities, an "attribute block" is defined as being an attribute opener, an attribute closer and everything between. I.e. `#[MyAttribute(1, 2), AnotherAttribute]` is one attribute block.
    Initial set of available methods:
    - `getAttributes()` to retrieve information about each attribute being instantiated within a particular attribute block.
    - `countAttributes()` to retrieve a count of the number of attribute instantiations with a particular attribute block.
    - `appliesTo()` to find the stack pointer to the language construct an attribute block applies to.
* New `PHPCSUtils\Utils\Constants::getAttributeOpeners()`, `PHPCSUtils\Utils\FunctionDeclarations::getAttributeOpeners()`, `PHPCSUtils\Utils\ObjectDeclarations::getAttributeOpeners()` and `PHPCSUtils\Utils\Variables::getAttributeOpeners()` utility methods. [#719]
    These methods will each return an array with the stack pointers to the attribute openers for applicable attribute blocks.
    This may be an empty array if no attributes apply to the constant/function/OO structure/property.

### Changed

#### Other

* Dropped support for [PHP_CodeSniffer] < 3.13.5/4.0.1. [#729]
    Please ensure you run `composer update phpcsstandards/phpcsutils --with-dependencies` to benefit from this.
* Various housekeeping.

[#719]: https://github.com/PHPCSStandards/PHPCSUtils/pull/719
[#720]: https://github.com/PHPCSStandards/PHPCSUtils/pull/720
[#729]: https://github.com/PHPCSStandards/PHPCSUtils/pull/729


## [1.1.3] - 2025-10-16

### Changed

#### Other

* Various housekeeping.

### Fixed

#### PHPCS Backcompat

* `BCFile::getMemberProperties()`: sync with PHPCS 3.13.4 - fix PHP 8.5 "Using null as an array offset" deprecation notice. [#711]

[#711]: https://github.com/PHPCSStandards/PHPCSUtils/pull/711


## [1.1.2] - 2025-09-05

### Added

#### PHPCS BackCompat

* `BCFile::getMemberProperties()`: sync with PHPCS 3.13.3 - support for PHP 8.4 abstract properties. [#698]

#### Utils

* `Variables::getMemberProperties()`: support for PHP 8.4 abstract properties. [#698]

### Changed

#### Utils

* `TypeString::normalizeCase()` will now also normalize a fully qualified `true`, `false` or `null` type to unqualified. [#702]

#### Other

* Dropped support for [PHP_CodeSniffer] < 3.13.3. [#698]
    Please ensure you run `composer update phpcsstandards/phpcsutils --with-dependencies` to benefit from this.
* Various housekeeping.

### Fixed

#### Utils

* `TypeString::isNullable()`: a type string with a fully qualified `null` was not recognized as nullable. [#702]
* `TypeString::isKeyword()`: a fully qualified `true`, `false` or `null` type was not recognized as a keyword type. [#702]

_Note: using fully qualified `true`, `false` or `null` in a typestring is not allowed by PHP. Even so, the `TypeString` utility will now handle this in a parse error tolerant manner._

[#698]: https://github.com/PHPCSStandards/PHPCSUtils/pull/698
[#702]: https://github.com/PHPCSStandards/PHPCSUtils/pull/702


## [1.1.1] - 2025-08-10

### Changed

#### TestUtils

* The [`PHPCSUtils\TestUtils\UtilityMethodTestCase`][`UtilityMethodTestCase`] now uses the PHP_CodeSniffer `LocalFile` instead of the `DummyFile` class under the hood. [#692]

### Fixed

#### TestUtils

* Prevent PHP 8.5 deprecation notices for `Reflection*::setAccessible()` in the [`PHPCSUtils\TestUtils\UtilityMethodTestCase`][`UtilityMethodTestCase`] and the [`PHPCSUtils\TestUtils\ConfigDouble`][`ConfigDouble`] classes. [#695]

[#692]: https://github.com/PHPCSStandards/PHPCSUtils/pull/692
[#695]: https://github.com/PHPCSStandards/PHPCSUtils/pull/695


## [1.1.0] - 2025-06-12

### Added

Compatibility with the new PHP_CodeSniffer `4.x` branch in anticipation of the PHP_CodeSniffer 4.0 release. [#674], [#679]
PHPCSUtils should now be fully compatible with PHP_CodeSniffer 4.0 (again). If you still find an issue, please report it.

#### PHPCS BackCompat

* `BCFile::findExtendedClassName()`: sync with PHPCS 4.0.0 - support for namespace relative names when used as the _extended_ parent class name. [#674]
* `BCFile::findImplementedInterfaceNames()`: sync with PHPCS 4.0.0 - support for namespace relative names when used in _implemented_ interface names. [#674]
* `BCFile::getMemberProperties()`: sync with PHPCS 3.12.0 - support for PHP 8.4 final properties. Thanks [@DanielEScherzer]! [#646]
* `BCFile::getMemberProperties()`: sync with PHPCS 3.13.1 - support for PHP 8.4 asymmetric visibility. [#677]
* `BCFile::getMemberProperties()`: sync with PHPCS 4.0.0. [#674]
    - Add support for PHP 8.4 properties in interfaces.
    - Removed parse error warning.
* `BCFile::getMethodParameters()`: sync with PHPCS 3.13.1 - support for PHP 8.4 asymmetric visibility. [#677]
* `BCFile::findStartOfStatement()`: sync with PHPCS 3.12.1 - support for `goto` as a `switch` - `case` terminating statement. [#661]
* `BCTokens::nameTokens()` as introduced in PHPCS 4.0.0. [#674]
    The same token array previously already existed in PHPCSUtils as `Collections::nameTokens()`.
* `BCTokens::functionNameTokens()`: sync with PHPCS 4.0.0 - added the `T_ANON_CLASS` token. [#674]
* `BCTokens::parenthesisOpeners()`: sync with PHPCS 4.0.0 - added the `T_USE`, `T_ISSET`, `T_UNSET`, `T_EMPTY`, `T_EVAL` and `T_EXIT` tokens. [#674]
    Note: While `T_USE`, `T_ISSET`, `T_UNSET`, `T_EMPTY`, `T_EVAL` and `T_EXIT` will be included in the return value for this method,
    the associated parentheses will not have the `'parenthesis_owner'` index set unless PHPCS 4.0.0 is used.
    Use the [`Parentheses::getOwner()`][`Parentheses`] or the [`Parentheses::hasOwner()`][`Parentheses`] methods if you need to check
    whether any of these tokens are a parentheses owner. The methods in the `Parentheses` class are PHPCS cross-version compatible.

#### TestUtils

* New [`PHPCSUtils\TestUtils\ConfigDouble`][`ConfigDouble`] class as a lightweight and more stable alternative to directly using the PHPCS native `Config` class in tests. [#550], [#612]
    Props to [@fredden] for helping me find and fix a bug in this feature before it was released.
* New [`PHPCSUtils\TestUtils\RulesetDouble`][`RulesetDouble`] class which allows for creating a `Ruleset` object without registering any sniffs. [#674]
    This allows for the [`PHPCSUtils\TestUtils\UtilityMethodTestCase`][`UtilityMethodTestCase`] class to be cross-version compatible with both PHP_CodeSniffer 3.x as well as 4.x.
* New [`PHPCSUtils\TestUtils\UtilityMethodTestCase::parseFile()`][`UtilityMethodTestCase`] method to allow for on-demand tokenizing of a (secondary) test case file. [#591], [#610]
    This method is intended for tests which need to use multiple test case files, like, for example, tests which need to verify that a utility resets a `$fileName` property on seeing another file.
* New [`PHPCSUtils\TestUtils\UtilityMethodTestCase::testTestMarkersAreUnique()` and `PHPCSUtils\TestUtils\UtilityMethodTestCase::assertTestMarkersAreUnique()`][`UtilityMethodTestCase`] methods which automatically check that code sample files used with this `TestCase` do not contain duplicate test case markers. [#642]
    If needs be, the test method can be disabled by overloading it (discouraged). If a test uses multiple test case/code sample files, the assertion method can be called directly.

#### Tokens

* New [`Collections::constantTypeTokens()`][`Collections`] method to support PHP 8.3 typed class constants. [#562]
* New [`Collections::ternaryOperators()`][`Collections`] method. [#549]
* `T_EXIT` to the `Collections::functionCallTokens()` and `Collections::parameterPassingTokens()` in light of the PHP 8.4 "exit as function" change. [#618]
* The PHP 8.4 asymmetric visibility tokens have been added to the [`Collections::propertyModifierKeywords()`][`Collections`] method. [#653]
* `T_INTERFACE` has been added to the [`Collections::ooPropertyScopes()`][`Collections`] method for PHP 8.4 interface properties support. [#674]

#### Utils

* New [`PHPCSUtils\Utils\Constants`][`Constants`] class: Utility functions to examine (class) constants. [#562]
    Initially available method: `getProperties()` to retrieve an array of information about an OO constant declaration.
* New [`PHPCSUtils\Utils\FileInfo`][`FileInfo`] class: Utility functions to check characteristics of the file under scan. [#572]
    Initial set of available methods: `hasByteOrderMark()` and `hasSheBang()`.
* New [`PHPCSUtils\Utils\FilePath`][`FilePath`] class: Utility functions for handling and comparing file paths. [#593]
    Initial set of available methods: `getName()` to retrieve the normalized path for the current file, `isStdin()`, `normalizeAbsolutePath()`, `normalizeDirectorySeparators()`, `trailingSlashIt()` and `startsWith()`.
* New [`PHPCSUtils\Utils\TypeString`][`TypeString`] class: Utility functions for analysing typestrings as retrieved via the various available `getParameters()`/`getProperties()` methods. [#588]
    Initial set of available methods: `getKeywordTypes()`, `isKeyword()`, `normalizeCase()`, `isSingular()`, `isNullable()`, `isUnion()`, `isIntersection()`, `isDNF()`, `toArray()`, `toArrayUnique()`, `filterKeywordTypes()` and `filterOOTypes()`.
    Note: The `is*()` methods will not check if the type string provided is valid, as doing so would inhibit what sniffs can flag. The `is*()` methods will only look at the form of the type string to determine if it could be valid for a certain type.
* New `PHPCSUtils\Utils\ObjectDeclarations::getDeclaredConstants()`, `PHPCSUtils\Utils\ObjectDeclarations::getDeclaredEnumCases()`, `PHPCSUtils\Utils\ObjectDeclarations::getDeclaredProperties()` and `PHPCSUtils\Utils\ObjectDeclarations::getDeclaredMethods()` utility methods. [#592]
    These methods will each return an array with the name of the constant/case/property/method as the key and the typical stack pointer needed for further processing.
    The retrieval of this information is highly optimized for performance. If a sniff needs to search for a named constant/enum case/property/method in an OO structure, in most cases, these methods should be the recommended way for finding the declaration, instead of the sniff attempting to do this itself.
    Mind: the return value of the `getDeclaredProperties()` method includes constructor promoted properties. Passing the stack pointer of constructor promoted properties onto a call to the `Variables::getMemberProperties()` method, however, is currently not supported.
* `FunctionDeclarations::getParameters()`: support for PHP 8.4 asymmetric visibility. [#677]
* `Variables::getMemberProperties()`: support for PHP 8.4 final properties. Thanks [@DanielEScherzer]! [#646]
* `Variables::getMemberProperties()`: support for PHP 8.4 asymmetric visibility. [#677]

### Changed

* The exceptions thrown by PHPCSUtils native utilities have been made more modular and more specific. [#598], [#599], [#600]
    - Passing a parameter of the wrong type will now result in a `PHPCSUtils\Exceptions\TypeError` being thrown.
    - Passing a parameter of the correct type, but with an invalid value, like an empty string when only non-empty strings are expected/accepted, will now result in a `PHPCSUtils\Exceptions\ValueError` being thrown.
    - Passing a (positive) integer stack pointer, which doesn't exist in the token stack of the current file, will now result in a `PHPCSUtils\Exceptions\OutOfBoundsStackPtr` being thrown.
    - Passing a stack pointer to a token which is not within the range of token types which is accepted by the method, will now result in a `PHPCSUtils\Exceptions\UnexpectedTokenType` being thrown.
    - Logic errors will now result in a `PHPCSUtils\Exceptions\LogicException` being thrown.
        This can occur, for instance, when a method takes a `$start` and `$end` parameter and the `$end` pointer is before the `$start` pointer.
    - Missing, conditionally required, parameters, will now result in a `PHPCSUtils\Exceptions\MissingArgumentError` being thrown.
    - Generic errors will now result in a `PHPCSUtils\Exceptions\RuntimeException` being thrown.
    - Previously the PHPCS native `PHP_CodeSniffer\Exceptions\RuntimeException` was used for all these exceptions.
        Catching the PHPCS native `RuntimeException` will still catch the new exceptions, but it is strongly recommended to be more selective when catching exceptions to prevent accidentally hiding errors in sniffs.
    - Also note that the PHPCSUtils native utilities now include more and stricter type checking to help surface bugs in sniffs.

#### Abstract Sniffs

* The `AbstractArrayDeclarationSniff::process()` method will no longer hide exceptions about a non-integer or non-existent stack pointer being passed. [#600]
* The `AbstractArrayDeclarationSniff::processArray()` method will no longer hide exceptions about non-integer or non-existent stack pointers being passed. [#600]

#### PHPCS BackCompat

* `BCFile::getDeclarationName()`: sync with PHPCS 3.12.0 - prevent incorrect result during live coding for unfinished closures. [#644]
* `BCFile::getDeclarationName()`: sync with PHPCS 4.0.0 - no longer accept the `T_CLOSURE` or `T_ANON_CLASS` tokens. [#674]
    Passing these will now result in an exception.
* `BCFile::getDeclarationName()`: sync with PHPCS 4.0.0 - the method will now always return a string. [#674]
    Previously, the method could return `null` when the name could not be determined. Now it will return an empty string.

#### TestUtils

* The [`PHPCSUtils\TestUtils\UtilityMethodTestCase`][`UtilityMethodTestCase`] is now officially compatible with PHPUnit 11 and will no longer generate any deprecation notices on PHPUnit 11.

#### Utils

* [`Parentheses`]: all methods will now recognize closer `use()` as a parentheses owner. [#674]
    Note: PHPCS natively does not recognize closure `use` as a parentheses owners until PHP_CodeSniffer 4.0.
* All methods in the [`PassedParameters`] class will now be able to analyze exit/die when used as a function call. [#618]
* The `Scopes::isOOProperty()` method now allows for PHP 8.4 properties in interfaces. [#674]
    - This also adds support for properties in interfaces to the `Variables::getMemberProperties()` method.
* The `UseStatements::splitAndMergeImportUseStatement()` method will no longer hide exceptions about a non-integer, non-existent or non-`T_USE` stack pointer being passed. [#600]

#### Other

* Dropped support for [PHP_CodeSniffer] < 3.13.0. [#629], [#653]
    Please ensure you run `composer update phpcsstandards/phpcsutils --with-dependencies` to benefit from this.
* Various housekeeping, including making the test suite compatible with PHPUnit 11.

### Deprecated

#### TestUtils

* The (internal) `PHPCSUtils\TestUtils\UtilityMethodTestCase::setStaticConfigProperty()` method. [#550]
    This method has become redundant with the introduction of the `ConfigDouble` class.

### Removed

#### PHPCS BackCompat

* The javascript specific `T_ZSR_EQUAL` token from the `BCTokens::assignmentTokens()` array, as per PHPCS 4.0.0. [#674]
* The javascript specific `T_OBJECT` token from the `BCTokens::blockOpeners()` array, as per PHPCS 4.0.0. [#674]
* The javascript specific `T_PROPERTY` and `T_OBJECT` tokens from the `BCTokens::blockOpeners()` array, as per PHPCS 4.0.0. [#674]

### Fixed

#### Abstract Sniffs

* `AbstractArrayDeclarationSniff::getActualArrayKey()`: will now handle FQN `true`/`false`/`null` correctly. [#668]

#### Utils

* `Context::inForeachCondition()` could misidentify the `as` keyword for `foreach` when the iterable expression contained an array declaration in which the `as` keyword was being used. [#617]
* The `Numbers::is*()` methods could misidentify strings which look like numeric literals with underscores, but are in actual fact invalid as numeric literals, as valid numbers. [#619], [#620]
* `UseStatements::getType()` will now handle unfinished closure `use` statements (parse errors) more consistently and return `'closure'` for those cases. [#667]

[#549]: https://github.com/PHPCSStandards/PHPCSUtils/pull/549
[#550]: https://github.com/PHPCSStandards/PHPCSUtils/pull/550
[#562]: https://github.com/PHPCSStandards/PHPCSUtils/pull/562
[#572]: https://github.com/PHPCSStandards/PHPCSUtils/pull/572
[#588]: https://github.com/PHPCSStandards/PHPCSUtils/pull/588
[#591]: https://github.com/PHPCSStandards/PHPCSUtils/pull/591
[#592]: https://github.com/PHPCSStandards/PHPCSUtils/pull/592
[#593]: https://github.com/PHPCSStandards/PHPCSUtils/pull/593
[#598]: https://github.com/PHPCSStandards/PHPCSUtils/pull/598
[#599]: https://github.com/PHPCSStandards/PHPCSUtils/pull/599
[#600]: https://github.com/PHPCSStandards/PHPCSUtils/pull/600
[#610]: https://github.com/PHPCSStandards/PHPCSUtils/pull/610
[#612]: https://github.com/PHPCSStandards/PHPCSUtils/pull/612
[#617]: https://github.com/PHPCSStandards/PHPCSUtils/pull/617
[#618]: https://github.com/PHPCSStandards/PHPCSUtils/pull/618
[#619]: https://github.com/PHPCSStandards/PHPCSUtils/issues/619
[#620]: https://github.com/PHPCSStandards/PHPCSUtils/pull/620
[#629]: https://github.com/PHPCSStandards/PHPCSUtils/pull/629
[#642]: https://github.com/PHPCSStandards/PHPCSUtils/pull/642
[#644]: https://github.com/PHPCSStandards/PHPCSUtils/pull/644
[#646]: https://github.com/PHPCSStandards/PHPCSUtils/pull/646
[#653]: https://github.com/PHPCSStandards/PHPCSUtils/pull/653
[#661]: https://github.com/PHPCSStandards/PHPCSUtils/pull/661
[#667]: https://github.com/PHPCSStandards/PHPCSUtils/pull/667
[#668]: https://github.com/PHPCSStandards/PHPCSUtils/pull/668
[#674]: https://github.com/PHPCSStandards/PHPCSUtils/pull/674
[#677]: https://github.com/PHPCSStandards/PHPCSUtils/pull/677
[#679]: https://github.com/PHPCSStandards/PHPCSUtils/pull/679


## [1.0.12] - 2024-05-20

### Added

#### PHPCS BackCompat

* `BCFile::getMemberProperties()`: sync with PHPCS 3.10.0 - support for PHP 8.2 DNF types. [#604]
* `BCFile::getMethodProperties()`: sync with PHPCS 3.10.0 - support for PHP 8.2 DNF types. [#604]
* `BCFile::getMethodParameters()`: sync with PHPCS 3.10.0 - support for PHP 8.2 DNF types. [#604]

#### Utils

* `FunctionDeclarations::getParameters()`: support for PHP 8.2 DNF types. [#604]
* `FunctionDeclarations::getProperties()`: support for PHP 8.2 DNF types. [#604]
* `Variables::getMemberProperties()`: support for PHP 8.2 DNF types. [#604]

### Changed

#### Tokens

* `Collections::parameterTypeTokens()`, `Collections::propertyTypeTokens()` and `Collections::returnTypeTokens()`: now include the new `T_TYPE_OPEN_PARENTHESIS` and `T_TYPE_CLOSE_PARENTHESIS` tokens for PHP 8.2 DNF type support. [#604]

#### Utils

* `ControlStructures::getCaughtExceptions()`: will now silently ignore parse errors in the code under scan which prevent the method from analyzing a `catch` statement. [#594]
    The method will now return an empty array instead of throwing a `PHP_CodeSniffer\Exceptions\RuntimeException`.

#### Other

* Dropped support for [PHP_CodeSniffer] < 3.10.0. [#603]
    Please ensure you run `composer update phpcsstandards/phpcsutils --with-dependencies` to benefit from this.
* Various housekeeping and documentation improvements.

### Fixed

#### Utils

* `UseStatements::splitImportUseStatement()`: the values in the return array will now never include a leading backslash. [#590]
    Previously the behaviour around import `use` statements declared with a leading backslash was undefined and the backslash would be included in the return value.

[#590]: https://github.com/PHPCSStandards/PHPCSUtils/pull/590
[#594]: https://github.com/PHPCSStandards/PHPCSUtils/pull/594
[#603]: https://github.com/PHPCSStandards/PHPCSUtils/pull/603
[#604]: https://github.com/PHPCSStandards/PHPCSUtils/pull/604


## [1.0.11] - 2024-04-24

### Changed

#### Other

* Various housekeeping and documentation improvements. Includes a contribution from [@fredden].

### Fixed

#### PHPCS BackCompat

* `BCFile::getMethodProperties()`: small performance improvement & more defensive coding, in line with same fix in PHPCS 3.9.2. [#573]

#### Utils

* `FunctionDeclarations::getProperties()`: small performance improvement & more defensive coding, in line with same fix in PHPCS 3.9.2. [#573]

[#573]: https://github.com/PHPCSStandards/PHPCSUtils/pull/573


## [1.0.10] - 2024-03-18

### Changed

#### Other

* Dropped support for [PHP_CodeSniffer] < 3.9.0. [#561]
    Please ensure you run `composer update phpcsstandards/phpcsutils --with-dependencies` to benefit from this.
* Various housekeeping and documentation improvements.

### Deprecated

#### Utils

* `NamingConventions::AZ_UPPER` constant. [#563]
* `NamingConventions::AZ_LOWER` constant. [#563]

### Fixed

#### PHPCS BackCompat

* `BackCompat\Helper::getEncoding()`: PHP 8.4 deprecation notice. [#568]
* `BackCompat\Helper::ignoreAnnotations()`: PHP 8.4 deprecation notice. [#568]

[#561]: https://github.com/PHPCSStandards/PHPCSUtils/pull/561
[#563]: https://github.com/PHPCSStandards/PHPCSUtils/pull/563
[#568]: https://github.com/PHPCSStandards/PHPCSUtils/pull/568


## [1.0.9] - 2023-12-08

### Added

#### PHPCS BackCompat

* `BCFile::getMemberProperties()`: sync with PHPCS 3.8.0 - support for PHP 8.2 `true` type. [#524]
* `BCFile::getMethodProperties()`: sync with PHPCS 3.8.0 - support for PHP 8.2 `true` type. [#524]
* `BCFile::getMethodParameters()`: sync with PHPCS 3.8.0 - support for PHP 8.2 `true` type. [#524]

### Changed

#### TestUtils

* Significant performance improvement for the [`UtilityMethodTestCase`]. [#525]

#### Other

* Dropped support for [PHP_CodeSniffer] < 3.8.0. [#523]
    Please ensure you run `composer update phpcsstandards/phpcsutils --with-dependencies` to benefit from this.
* Small improvements to the documentation website generation. Includes a contribution from [@fredden].
* Various housekeeping and documentation improvements. Includes a contribution from [@fredden].

[#523]: https://github.com/PHPCSStandards/PHPCSUtils/pull/523
[#524]: https://github.com/PHPCSStandards/PHPCSUtils/pull/524
[#525]: https://github.com/PHPCSStandards/PHPCSUtils/pull/525


## [1.0.8] - 2023-07-17

### Changed

#### PHPCS BackCompat

* `BCFile::getDeclarationName()`: sync with PHPCS 3.8.0 - support for functions called `self`, `parent` or `static` which return by reference. [#494]

#### Other

* Various housekeeping and minor documentation improvements.

### Fixed

#### Fixers

* The [`SpacesFixer`] will no longer throw an (incorrect) exception when the second pointer passed is a comment token and this comment token is the last content in a file. [#493]

[#493]: https://github.com/PHPCSStandards/PHPCSUtils/pull/493
[#494]: https://github.com/PHPCSStandards/PHPCSUtils/pull/494


## [1.0.7] - 2023-07-10

### Changed

#### Other

* Various housekeeping and maintenance updates, including making the test suite compatible with PHPUnit 10.

### Fixed

#### Utils

* The `Arrays::getDoubleArrowPtr()` method could previously get confused over a double arrow in a keyed list used as an array value. [#485]

[#485]: https://github.com/PHPCSStandards/PHPCSUtils/pull/485


## [1.0.6] - 2023-05-27

### Changed

#### PHPCS BackCompat

* `BCFile::getClassProperties()`: sync with PHPCS 3.8.0 - support for PHP 8.2 `readonly` classes. [#470]
* `BCFile::getMethodParameters()`: sync with PHPCS 3.8.0 - support for constructor property promotion with `readonly` properties without explicit visibility. [#472]

#### Utils

* The results of the following methods will now (also) be cached for improved performance when multiple sniffs call these functions for the same token during a PHPCS run. [#464], [#466]
    - `FunctionDeclarations::getProperties()`
    - `Variables::getMemberProperties()`
* Additionally, the results of the `UseStatements::splitImportUseStatement()` method will be cached more often and the cache checked earlier. [#467]
* The return value of the `ControlStructures::getCaughtExceptions()` method will no longer contain "empty" entries for catch statements without a named exception. It will return an empty array instead. [#474]

#### Other

* Various small housekeeping and maintenance updates.

### Fixed

### Abstract Sniffs

* `AbstractArrayDeclarationSniff`: fixed a potential "Trying to access array offset on value of type bool" PHP notice. [#476]
* `AbstractArrayDeclarationSniff`: the abstract will no longer trigger potentially available magic `__get()`/`__set()` etc methods. [#477]

[#464]: https://github.com/PHPCSStandards/PHPCSUtils/pull/464
[#466]: https://github.com/PHPCSStandards/PHPCSUtils/pull/466
[#467]: https://github.com/PHPCSStandards/PHPCSUtils/pull/467
[#470]: https://github.com/PHPCSStandards/PHPCSUtils/pull/470
[#472]: https://github.com/PHPCSStandards/PHPCSUtils/pull/472
[#474]: https://github.com/PHPCSStandards/PHPCSUtils/pull/474
[#476]: https://github.com/PHPCSStandards/PHPCSUtils/pull/476
[#477]: https://github.com/PHPCSStandards/PHPCSUtils/pull/477


## [1.0.5] - 2023-04-17

### Fixed

#### Utils

* The `Lists::getAssignments()` method could previously get confused over exotic list keys. Fixed now. [#459]

[#459]: https://github.com/PHPCSStandards/PHPCSUtils/pull/459


## [1.0.4] - 2023-04-15

### Changed

#### Other

* Minor documentation improvements.

### Fixed

#### Utils

* The `FunctionDeclarations::getParameters()` method will now correctly handle constructor promoted properties with `readonly`, but without explicit visibility set. [#456]

[#456]: https://github.com/PHPCSStandards/PHPCSUtils/pull/456


## [1.0.3] - 2023-04-13

### Changed

#### Other

* Various small housekeeping and maintenance updates.

### Fixed

#### Utils

* The `PassedParameters` class now allows for function calls to global functions called `self()`, `parent()` or `static()`. [#452]

[#452]: https://github.com/PHPCSStandards/PHPCSUtils/pull/452


## [1.0.2] - 2023-03-28

### Changed

#### Tokens

* The `Collections::arrayOpenTokensBC()`, `Collections::arrayTokensBC()`, `Collections::listOpenTokensBC()`, `Collections::listTokensBC()`, `Collections::shortArrayListOpenTokensBC()`, `Collections::shortArrayTokensBC()` and `Collections::shortListTokensBC()` token arrays will no longer contain the `T_OPEN_SQUARE_BRACKET` and/or the `T_CLOSE_SQUARE_BRACKET` token constants if PHP_CodeSniffer 3.7.2 or higher is used. [#444]
    An upstream bugfix makes it unnecessary to check those tokens for being a short array or short list.
    Sniff which use these token arrays is combination with using the `Arrays`/`Lists` classes, should experience a performance boost on PHPCS 3.7.2+ due to this change.

#### Other

* Minor documentation improvements.
* Various small housekeeping and maintenance updates.

### Fixed

#### Utils

* The `Lists::isShortList()` method will now correctly recognize a short list nested in a long list as a short list. [#446]
    Note: this is a parse error in PHP, but the method should still handle this correctly.

[#444]: https://github.com/PHPCSStandards/PHPCSUtils/pull/444
[#446]: https://github.com/PHPCSStandards/PHPCSUtils/pull/446


## [1.0.1] - 2023-01-05

### Changed

#### Other

* Composer: The version requirements for the [Composer PHPCS plugin] have been widened to allow for version 1.0.0. [#428]
    Please ensure you run `composer update phpcsstandards/phpcsutils --with-dependencies` to benefit from this.
* Removed the references to pre-1.0.0 QA releases from the docs. [#425]
* Various small housekeeping and maintenance updates. Thanks [@szepeviktor] for contributing.

[#425]: https://github.com/PHPCSStandards/PHPCSUtils/pull/425
[#428]: https://github.com/PHPCSStandards/PHPCSUtils/pull/428


## [1.0.0] - 2023-01-04

For the full list of features, please see the changelogs of the alpha/rc releases:
* [1.0.0-rc1](https://github.com/PHPCSStandards/PHPCSUtils/releases/tag/1.0.0-rc1)
* [1.0.0-alpha4](https://github.com/PHPCSStandards/PHPCSUtils/releases/tag/1.0.0-alpha4)
* [1.0.0-alpha3](https://github.com/PHPCSStandards/PHPCSUtils/releases/tag/1.0.0-alpha3)
* [1.0.0-alpha2](https://github.com/PHPCSStandards/PHPCSUtils/releases/tag/1.0.0-alpha2)
* [1.0.0-alpha1](https://github.com/PHPCSStandards/PHPCSUtils/releases/tag/1.0.0-alpha1)

### Changed

#### Other

* Minor documentation improvements.
* Maintainability improvements.


## [1.0.0-rc1] - 2022-12-27

### Added

#### Tokens

* New [`Collections::constantModifierKeywords()`][`Collections`] method. [#400]
* New [`Collections::listOpenTokensBC()`][`Collections`] method. [#405]

### Changed

#### Utils

* `ObjectDeclarations::getClassProperties()`: the array return value will now include `'abstract_token'`, `'final_token'`, and `'readonly_token'` indexes identifying the stack pointers to these keyword, where applicable. If the keyword is not used, the value will be set to `false`. [#401]

#### Other

* Various housekeeping and CI maintenance.

### Removed

Everything which was previously deprecated in the [1.0.0-alpha4 release], has now been removed. [#410]

This includes:
* The `PHPCS23Utils` standard.
* [`Collections`] class: direct access to the properties in the class.
* [`Collections`] class: the `Collections::arrowFunctionTokensBC()`, `Collections::functionDeclarationTokensBC()`, `Collections::parameterTypeTokensBC()`, `Collections::propertyTypeTokensBC()` and `Collections::returnTypeTokensBC()` methods.
* The `ControlStructures::getDeclareScopeOpenClose()` method.
* The `FunctionDeclarations::getArrowFunctionOpenClose()` method.
* The `FunctionDeclarations::isArrowFunction()` method.

See the changelog for the [1.0.0-alpha4 release] for details about replacements for the removed functionality.

### Fixed

#### Utils

* `Arrays::getOpenClose()`: small efficiency fix. [#406]
* `ControlStructures::hasBody()`: correctly identify that a control structure does not have a body - `'has_body' = false` -, when the control structure is ended by a PHP close tag. [#403]
* `Lists::getOpenClose()`: small efficiency fix. [#407]

[1.0.0-alpha4 release]: https://github.com/PHPCSStandards/PHPCSUtils/releases/tag/1.0.0-alpha4

[#400]: https://github.com/PHPCSStandards/PHPCSUtils/pull/400
[#401]: https://github.com/PHPCSStandards/PHPCSUtils/pull/401
[#403]: https://github.com/PHPCSStandards/PHPCSUtils/pull/403
[#405]: https://github.com/PHPCSStandards/PHPCSUtils/pull/405
[#406]: https://github.com/PHPCSStandards/PHPCSUtils/pull/406
[#407]: https://github.com/PHPCSStandards/PHPCSUtils/pull/407
[#410]: https://github.com/PHPCSStandards/PHPCSUtils/pull/410


## [1.0.0-alpha4] - 2022-10-25

Notes:
* While still in alpha, some BC-breaks may be introduced. These are clearly indicated in the changelog with the :warning: symbol.
* Until PHPCS 4.x has been released, PHPCSUtils does not formally support it, though an effort is made to keep up with the changes and anticipate potential compatibility issues.
    For testing purposes only, the composer configuration allows for PHPCSUtils to be installed with PHPCS 4.x.

### Breaking Changes

Support for PHP_CodeSniffer < 3.7.1 has been dropped after consultation with the principle external standards which depend on PHPCSUtils. [#347]

This was unfortunately necessary as the incessant additions of new syntaxes since PHP 7.4 made it impossible to continue to support PHPCS < 3.7.1, while still providing reliable results for modern PHP code.

### Added

Now support for PHPCS < 3.7.1 has been dropped, this edition adds support to all functionality in PHPCSUtils for new syntaxes and features from PHP 8.0 and 8.1 and preliminary support for PHP 8.2.

This means that support for the following syntaxes/features has been added (or existing support verified/improved):
* PHP 7.4
    - Array unpacking in array expressions.
* PHP 8.0
    - The `mixed` type. [#163]
    - Union types, including supporting the `false` and `null` types. [#168], [#225]
    - Constructor property promotion. [#169], [#226]
    - Nullsafe object operators. [#176], [#183]
    - Namespaced names as single token (cross-version PHPCS 3.x vs 4.x). [#205], [#206], [#207], [#208], [#209], [#210], [#211], [#212], [#213], [#217], [#241]
    - Dereferencing of interpolated text strings.
    - Named arguments in function calls. [#235], [#243], [#383]
    - Match expressions. [#247], [#335], [#356]
    - Trailing commas in parameter lists and closure `use` lists.
    - Attributes. [#357]
* PHP 8.1
    - Enumerations. [#285], [#358]
    - Explicit octal notation. [#293]
    - Array unpacking with string keys.
    - `never` type.
    - Named parameters after argument unpacking. [#383]
    - First class callables. [#362]
    - Readonly properties. [#363]
    - `new` in initializers.
    - Intersection types. [#365]
* PHP 8.2
    - Constants in traits. [#366]
    - Readonly classes. [#367]
    - `true` type. [#368]
    - `null` and `false` as stand-alone types.

Please report any bugs/oversights you encounter!

#### PHPCS Backcompat

* `BCTokens::magicConstants()` as introduced in PHPCS 3.5.6. [#172]
    The same token array previously already existed in PHPCSUtils as `Collections::$magicConstants` (which has now been deprecated).

#### TestUtils

* New [`UtilityMethodTestCase::usesPhp8NameTokens()`][UtilityMethodTestCase::usesPhp8NameTokens] method as a helper for tests using token position calculations when testing against both PHPCS 3.x as well as 4.x. [#200], [#217], [#241]

#### Tokens

* New [`PHPCSUtils\Tokens\TokenHelper`][`TokenHelper`] class: Utility functions for working with tokens and token collections. [#304]
    This class initially contains a `tokenExists()` method to work around potential interference from PHP-Parser also backfilling tokens.
    :point_right: External standards using a function call to `defined()` to determine whether a token is available should replace those with a function call to the `TokenHelper::tokenExists()` method.
* New [`Collections::arrayOpenTokensBC()`][`Collections`] method. [#233], [#311]
* New [`Collections::functionCallTokens()`][`Collections`] method to retrieve the tokens which can represent function calls and function-call-like language constructs, like class instantiations. [#233]
* New [`Collections::nameTokens()`][`Collections`] method to retrieve the tokens which can be used for "names", be it namespace, OO, function or constant names. [#204], [#217]
* New [`Collections::namespacedNameTokens()`][`Collections`] method to retrieve the tokens which can be encountered in a fully, partially or unqualified name and in namespace relative names. [#202], [#217]
* New [`Collections::parameterPassingTokens()`][`Collections`] method to retrieve the tokens which can be passed to the methods in the [`PassedParameters`] class. [#233]
* New [`Collections::phpOpenTags()`][`Collections`] method. [#254]
* New [`Collections::shortArrayListOpenTokensBC()`][`Collections`] method. [#381]

#### Utils

* New [`PHPCSUtils\Utils\Context`][`Context`] class: Utility functions to determine the context in which an arbitrary token is used. [#219]. [#390]
    Initial set of available methods: `inEmpty()`, `inIsset()`, `inUnset()`, `inAttribute`, `inForeachCondition()` and `inForCondition()`.
* New [`PHPCSUtils\Utils\MessageHelper`][`MessageHelper`] class: Utility functions for creating error/warning messages. [#249], [#391]
    Initial set of available methods: `addMessage()`, `addFixableMessage()`, `stringToErrorcode()` and `showEscapeChars()`.
* New [`PHPCSUtils\Utils\PassedParameters::getParameterFromStack()`][PassedParameters::getParameterFromStack] efficiency method to retrieve a potentially named function call parameter from a parameter information stack as previously retrieved via `PassedParameters::getParameters()`. [#235], [#237], [#383]
* New [`PHPCSUtils\Utils\TextStrings::getEndOfCompleteTextString()`][TextStrings::getEndOfCompleteTextString] method. [#320]
    This method allows to retrieve the stack pointer to the last token within a - potentially multi-line - text string.
    This method compliments the `TextStrings::getCompleteTextString()` method which will retrieve the contents of the complete text string.
* New [`PHPCSUtils\Utils\TextStrings::getEmbeds()`][TextStrings::getEmbeds] method to retrieve an array with all variables/expressions as embedded in a text string. [#321]
* New [`PHPCSUtils\Utils\TextStrings::stripEmbeds()`][TextStrings::stripEmbeds] method to strip all embedded variables/expressions from a text string.  [#321]
* New [`PHPCSUtils\Utils\TextStrings::getStripEmbeds()`][TextStrings::getStripEmbeds] method. [#321]
    This method is `public` for those rare cases where both the embeds, as well as the text stripped off embeds, is needed.
* New [`PHPCSUtils\Utils\UseStatements::mergeImportUseStatements()`][UseStatements::mergeImportUseStatements] method. [#196]

#### Other

* PHPCSUtils will now cache the results of (potentially) token walking intensive or processing intensive function calls during a run. [#332], [#377]
    This should significantly improve performance when multiple sniffs call these functions for the same token during a PHPCS run.
    The results of the following functions will now be cached:
    - `Arrays::getDoubleArrowPtr()`
    - `Arrays::isShortArray()`
    - `FunctionDeclarations::getParameters()`
    - `Lists::getAssignments()`
    - `Lists::isShortList()`
    - `Namespaces::findNamespacePtr()`
    - `PassedParameters::getParameters()`
    - `TextStrings::getEndOfCompleteTextString()`
    - `TextStrings::getStripEmbeds()`
    - `UseStatements::splitImportUseStatement()`

### Changed

#### PHPCS Backcompat

* All token array methods in the [`BCTokens`] class are fully up-to-date with the upstream `PHP_CodeSniffer\Util\Tokens` properties as per PHPCS `master` at the time of this release. [#327], [#347], [#360]
* All methods in the [`BCFile`] class are fully up-to-date with the upstream `PHP_CodeSniffer\Files\File` methods as per PHPCS `master` at the time of this release. [#347]
* `BCFile::getMethodParameters()`: forward compatibility with PHPCS 4.x in which closure `use` will be a parenthesis owner. [#251]
* If a non-existent token array is requested from the `BCTokens` class, a `PHPCSUtils\Exceptions\InvalidTokenArray` exception will be thrown. [#344]
    The `PHPCSUtils\Exceptions\InvalidTokenArray` exception extends the PHPCS native `PHP_CodeSniffer\Exceptions\RuntimeException`.
    Previously, an empty array would be returned.

#### TestUtils

* `UtilityMethodTestCase`: all properties contained in the test case class will now always be reset after the tests have run. [#325]
* `UtilityMethodTestCase::getTargetToken()`: when the target token cannot be found, the method will now throw a (catchable) `PHPCSUtils\Exceptions\TestTargetNotFound` exception instead of failing the test. [#248], [#371]
    If uncaught, this means that the test will be marked as _errored_ instead of _failed_.
* `UtilityMethodTestCase::getTargetToken()`: this method is now `static`, which allows for it to be used in "set up before class" fixtures. [#382]. [#385]

#### Tokens

* `Collections::functionCallTokens()` and `Collections::parameterPassingTokens()`: now include the `T_PARENT` token. [#328]
    This accounts for a change in PHPCS 3.7.0 for how `parent` in `new parent` is tokenized.
* If a non-existent token array is requested from the `Collections` class, a `PHPCSUtils\Exceptions\InvalidTokenArray` exception will be thrown. [#349]
    The `PHPCSUtils\Exceptions\InvalidTokenArray` exception extends the PHPCS native `PHP_CodeSniffer\Exceptions\RuntimeException`.

#### Utils

* `Arrays::isShortArray()`/`Lists::isShortList()`: the underlying logic has been completely refactored for improved accuracy and improved performance. [#392]
* `FunctionDeclarations::getParameters()`: the return array of the method may contain two new keys - `property_visibility` and `visibility_token`. [#169]
    These keys will only be included if constructor property promotion is detected.
* `FunctionDeclarations::getParameters()`: forward compatibility with PHPCS 4.x in which closure `use` will be a parenthesis owner. [#251]
* [`GetTokensAsString`]: previously the `tabReplaced()` method was an alias for the `normal()` method. This has now been reversed. [#297]
* `Operators::isReference()`: forward compatibility with PHPCS 4.x in which closure `use` will be a parenthesis owner. [#195]
* [`Parentheses`]: all methods will now recognize `isset()`, `unset()`, `empty()`, `exit()`, `die()` and `eval()` as parentheses owners. [#215]
    Note: PHPCS natively does not recognize these constructs as parentheses owners, though this _may_ change in the future. See: [PHPCS#3118]
* [`PassedParameters`]: all methods will now recognize anonymous class instantiations as constructs which can pass parameters. [#234]
* `PassedParameters::getParameters()`: when a named parameter is encountered in a function call, the returned parameter information array for that parameter will now contain the following additional keys: `'name'`, `'name_token'` and the top-level index for the parameter will be set to the parameter `'name'` instead of its position. [#235], [#243], [#383]
    The existing `'start'`, `'end'`, `'raw'` and `'clean'` keys will contain the same content as before: the information about the parameter value (excluding the name part).
* `PassedParameters::getParameters()`: new, optional parameter `$limit` to allow for limiting the number of parameters/array items to be parsed. [#261]
    This allows for higher efficiency when retrieving the parameters/array entries, especially for large arrays if only the first few entries need to be examined.
    Use with care on function calls, as this can break support for named parameters!
* `PassedParameters::getParameters()`: new, optional parameter `$isShortArray` to allow for skipping the "is short array" check for predetermined short arrays. [#269], [#270]
     Use judiciously and with extreme care!
* `PassedParameters::getParameter()`: new _semi-optional_ `$paramNames` parameter to allow for retrieving a parameter from a function call using named parameters. [#235]
    :warning: This parameter is **_required_** when examining a function call and an exception will be thrown if it was not passed.
    This new `$paramNames` parameter allows for passing either the parameter name as a _string_ or as an _array of strings_.
    _While a parameter can only have one name, a lot of packages have been, and may still be, reviewing and renaming parameters to more descriptive names to support named parameters in PHP 8.0, so, to allow for supporting multiple versions of packages, different names can be used for the same parameter in a PHP 8.0+ function call and by allowing multiple names to be passed, the method supports this._
* `PassedParameters::getParameter()`: efficiency improvement applicable to function calls not using named parameters and arrays. [#261], [#262]
* `PassedParameters::hasParameters()`: now allows for the `T_PARENT` token for `new parent()` class instantiations. [#328]
    This accounts for a change in PHPCS 3.7.0 regarding how `parent` in `new parent` is tokenized.
* `PassedParameters::hasParameters()`: new, optional parameter `$isShortArray` to allow for skipping the "is short array" check for predetermined short arrays. [#269], [#270]
     Use judiciously and with extreme care!
* `UseStatements::getType()`: forward compatibility with PHPCS 4.x in which closure `use` will be a parenthesis owner. [#251]

#### Other

* The `master` branch has been renamed to `stable`. [#397]
* :warning: All non-`abstract` classes in this package are now `final`. [#376]
* The parameter names in various function declarations have been changed to not intersect with reserved keywords. [#256]
    :warning: In the unlikely event an external standard using PHPCSUtils would be using function calls with named parameters, the parameter names will need to be updated to match.
* Composer: The package will now identify itself as a static analysis tool. Thanks [@GaryJones]! [#341]
* Readme/website homepage: the installation instructions have been updated to include information on installing this library and the included [Composer PHPCS plugin] in combination with Composer >= 2.2. [#291], [#292]
* Various documentation improvements. [#216], [#309], [#394], [#395], [#396], [#398]
* Various housekeeping and CI maintenance.
    Amongst other things, CI is now run via GitHub Actions ([#239]), the PHPCSUtils native tests now use the [PHPUnit Polyfills] package ([#277]) and the tests are now run against PHP 5.4 - 8.2.

### Deprecated

:warning: Everything which has currently been deprecated, will be removed before the final `1.0.0` version of PHPCSUtils is tagged.

#### Tokens

[`Collections`] class: direct access to the properties in the class is now deprecated for forward-compatibility reasons.
All properties have a replacement which should be used instead, in most cases this will be a method with the same name as the previously used property,

| Deprecated                                                    | Replacement                                                                                          | PR             | Remarks                                  |
| ------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------- | -------------- | ---------------------------------------- |
| `Collections::$alternativeControlStructureSyntaxTokens`       | `Collections::alternativeControlStructureSyntaxes()`                                                 | [#311]         | Mind the change in the name!             |
| `Collections::$alternativeControlStructureSyntaxCloserTokens` | `Collections::alternativeControlStructureSyntaxClosers()`                                            | [#311]         | Mind the change in the name!             |
| `Collections::$arrayTokens`                                   | `Collections::arrayTokens()`                                                                         | [#311]         |                                          |
| `Collections::$arrayTokensBC`                                 | `Collections::arrayTokensBC()`                                                                       | [#311]         |                                          |
| `Collections::$classModifierKeywords`                         | `Collections::classModifierKeywords()`                                                               | [#311]         |                                          |
| `Collections::$closedScopes`                                  | `Collections::closedScopes()`                                                                        | [#311]         |                                          |
| `Collections::$controlStructureTokens`                        | `Collections::controlStructureTokens()`                                                              | [#311]         |                                          |
| `Collections::$incrementDecrementOperators`                   | `Collections::incrementDecrementOperators()`                                                         | [#311]         |                                          |
| `Collections::$listTokens`                                    | `Collections::listTokens()`                                                                          | [#311]         |                                          |
| `Collections::$listTokensBC`                                  | `Collections::listTokensBC()`                                                                        | [#311]         |                                          |
| `Collections::$magicConstants`                                | `PHPCSUtils\BackCompat\BCTokens::magicConstants()` or `PHP_CodeSniffer\Util\Tokens::$magicConstants` | [#311]         |                                          |
| `Collections::$namespaceDeclarationClosers`                   | `Collections::namespaceDeclarationClosers()`                                                         | [#311]         |                                          |
| `Collections::$objectOperators`                               | `Collections::objectOperators()`                                                                     | [#176]         |                                          |
| `Collections::$OOCanImplement`                                | `Collections::ooCanImplement()`                                                                      | [#311]         | Mind the case change in the `oo` prefix! |
| `Collections::$OOCanExtend`                                   | `Collections::ooCanExtend()`                                                                         | [#311]         | Mind the case change in the `oo` prefix! |
| `Collections::$OOConstantScopes`                              | `Collections::ooConstantScopes()`                                                                    | [#311]         | Mind the case change in the `oo` prefix! |
| `Collections::$OOPropertyScopes`                              | `Collections::ooPropertyScopes()`                                                                    | [#311]         | Mind the case change in the `oo` prefix! |
| `Collections::$OOHierarchyKeywords`                           | `Collections::ooHierarchyKeywords()`                                                                 | [#311]         | Mind the case change in the `oo` prefix! |
| `Collections::$OONameTokens`                                  | `Collections::namespacedNameTokens()`                                                                | [#202]         | Mind the change name!                    |
| `Collections::$parameterTypeTokens`                           | `Collections::parameterTypeTokens()`                                                                 | [#168]         |                                          |
| `Collections::$propertyModifierKeywords`                      | `Collections::propertyModifierKeywords()`                                                            | [#311]         |                                          |
| `Collections::$propertyTypeTokens`                            | `Collections::propertyTypeTokens()`                                                                  | [#168]         |                                          |
| `Collections::$returnTypeTokens`                              | `Collections::returnTypeTokens()`                                                                    | [#168]         |                                          |
| `Collections::$shortArrayTokens`                              | `Collections::shortArrayTokens()`                                                                    | [#311]         |                                          |
| `Collections::$shortArrayTokensBC`                            | `Collections::shortArrayTokensBC()`                                                                  | [#311]         |                                          |
| `Collections::$shortListTokens`                               | `Collections::shortListTokens()`                                                                     | [#311]         |                                          |
| `Collections::$shortListTokensBC`                             | `Collections::shortListTokensBC()`                                                                   | [#311]         |                                          |
| `Collections::$textStingStartTokens`                          | `Collections::textStringStartTokens()`                                                               | [#311], [#319] | Mind the fixed typo in the name!         |

Additionally, the following methods in the `Collections` class have been deprecated:

| Deprecated                                   | Replacement                                | PR     |
| -------------------------------------------- | ------------------------------------------ | ------ |
| `Collections::arrowFunctionTokensBC()`       | Use the `T_FN` token instead.              | [#347] |
| `Collections::functionDeclarationTokensBC()` | `Collections::functionDeclarationTokens()` | [#347] |
| `Collections::parameterTypeTokensBC()`       | `Collections::parameterTypeTokens()`       | [#347] |
| `Collections::propertyTypeTokensBC()`        | `Collections::propertyTypeTokens()`        | [#347] |
| `Collections::returnTypeTokensBC()`          | `Collections::returnTypeTokens()`          | [#347] |

#### Utils

* `ControlStructures::getDeclareScopeOpenClose()`: this method is no longer needed, check the `scope_opener`/`scope_closer` on the `T_DECLARE` token instead. [#347]
* `FunctionDeclarations::getArrowFunctionOpenClose()`: this method is no longer needed, check the `scope_opener`/`scope_closer` etc on the `T_FN` token instead. [#347]
* `FunctionDeclarations::isArrowFunction()`: this method is no longer needed, use the `T_FN` token instead. [#347]

#### Other

* The `PHPCS23Utils` standard, which is no longer needed now support for PHPCS < 3.7.1 has been dropped. [#347]

### Removed

* Support for PHPCS < 3.7.1. [#347]

#### Utils

* The following constants, which were only intended for internal use, have been removed: [#347]
    - `PHPCSUtils\Utils\Numbers::REGEX_NUMLIT_STRING`
    - `PHPCSUtils\Utils\Numbers::REGEX_HEX_NUMLIT_STRING`
    - `PHPCSUtils\Utils\Numbers::UNSUPPORTED_PHPCS_VERSION`

### Fixed

#### Abstract Sniffs

* `AbstractArrayDeclarationSniff::getActualArrayKey()`: will now handle escaped vars in heredoc array keys better. [#379]

#### Fixers

* [`SpacesFixer`]: in a specific new line vs trailing comment situation, the fixer would incorrectly add a stray new line. [#229]

#### TestUtils

* `UtilityMethodTestCase::getTargetToken()`: will now throw a `PHPCSUtils\Exceptions\TestMarkerNotFound` exception if the provided test marker comment is not found. [#273], [#372]
    This prevents the method potentially misidentifying the target token.

#### Tokens

* `Collections::parameterTypeTokens()`, `Collections::propertyTypeTokens()` and `Collections::returnTypeTokens()`: include the `namespace` keyword for namespace relative identifier names. [#180]

#### Utils

* `Arrays::isShortArray()`/`Lists::isShortList()`: fixed a number of bugs which previously resulted in an incorrect short list/short array determination. [#392]
* `FunctionDeclarations::getParameters()`: will now correctly recognize namespace relative names when used in a parameter type. [#180]
* `FunctionDeclarations::getProperties()`: will now correctly recognize namespace relative names when used in the return type. [#180]
* `ObjectDeclarations::findExtendedClassName()`: will now correctly recognize namespace relative names when used as the _extended_ parent class name. [#181]
* `ObjectDeclarations::findExtendedInterfaceNames()`: will now correctly recognize namespace relative names when used in _extended_ interface names. [#181]
* `ObjectDeclarations::findImplementedInterfaceNames()`: will now correctly recognize namespace relative names when used in _implemented_ interface names. [#181]
* `ObjectDeclarations::getClassProperties()`: will now correctly handle classes declared with both the `final` as well as the `abstract` keyword. [#252]
* `Operators::isReference()`: if one parameter in a function declaration was passed by reference, then all `T_BITWISE_AND` (not intersection type `&`'s) tokens would be regarded as references. [#188]
* `Operators::isReference()`: the `&` would not be recognized as a reference for arrow function parameters passed by reference. [#192]
* `Operators::isUnaryPlusMinus()`: a preceding `exit`, `break`, `continue` or arrow function `=>` is now recognized as an indicator that a plus/minus sign is unary. [#187], [#197]
* `Variables::getMemberProperties()`: will now correctly recognize namespace relative names when used in a property type. [#180]

[UtilityMethodTestCase::usesPhp8NameTokens]: https://phpcsutils.com/phpdoc/classes/PHPCSUtils-TestUtils-UtilityMethodTestCase.html#method_usesPhp8NameTokens
[PassedParameters::getParameterFromStack]:   https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-PassedParameters.html#method_getParameterFromStack
[TextStrings::getEndOfCompleteTextString]:   https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-TextStrings.html#method_getEndOfCompleteTextString
[TextStrings::getEmbeds]: https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-TextStrings.html#method_getEmbeds
[TextStrings::getStripEmbeds]: https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-TextStrings.html#method_getStripEmbeds
[TextStrings::stripEmbeds]: https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-TextStrings.html#method_stripEmbeds
[UseStatements::mergeImportUseStatements]:   https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-UseStatements.html#method_mergeImportUseStatements

[#163]: https://github.com/PHPCSStandards/PHPCSUtils/pull/163
[#168]: https://github.com/PHPCSStandards/PHPCSUtils/pull/168
[#169]: https://github.com/PHPCSStandards/PHPCSUtils/pull/169
[#172]: https://github.com/PHPCSStandards/PHPCSUtils/pull/172
[#176]: https://github.com/PHPCSStandards/PHPCSUtils/pull/176
[#180]: https://github.com/PHPCSStandards/PHPCSUtils/pull/180
[#181]: https://github.com/PHPCSStandards/PHPCSUtils/pull/181
[#183]: https://github.com/PHPCSStandards/PHPCSUtils/pull/183
[#187]: https://github.com/PHPCSStandards/PHPCSUtils/pull/187
[#188]: https://github.com/PHPCSStandards/PHPCSUtils/pull/188
[#192]: https://github.com/PHPCSStandards/PHPCSUtils/pull/192
[#195]: https://github.com/PHPCSStandards/PHPCSUtils/pull/195
[#196]: https://github.com/PHPCSStandards/PHPCSUtils/pull/196
[#197]: https://github.com/PHPCSStandards/PHPCSUtils/pull/197
[#200]: https://github.com/PHPCSStandards/PHPCSUtils/pull/200
[#202]: https://github.com/PHPCSStandards/PHPCSUtils/pull/202
[#204]: https://github.com/PHPCSStandards/PHPCSUtils/pull/204
[#205]: https://github.com/PHPCSStandards/PHPCSUtils/pull/205
[#206]: https://github.com/PHPCSStandards/PHPCSUtils/pull/206
[#207]: https://github.com/PHPCSStandards/PHPCSUtils/pull/207
[#208]: https://github.com/PHPCSStandards/PHPCSUtils/pull/208
[#209]: https://github.com/PHPCSStandards/PHPCSUtils/pull/209
[#210]: https://github.com/PHPCSStandards/PHPCSUtils/pull/210
[#211]: https://github.com/PHPCSStandards/PHPCSUtils/pull/211
[#212]: https://github.com/PHPCSStandards/PHPCSUtils/pull/212
[#213]: https://github.com/PHPCSStandards/PHPCSUtils/pull/213
[#215]: https://github.com/PHPCSStandards/PHPCSUtils/pull/215
[#216]: https://github.com/PHPCSStandards/PHPCSUtils/pull/216
[#217]: https://github.com/PHPCSStandards/PHPCSUtils/pull/217
[#219]: https://github.com/PHPCSStandards/PHPCSUtils/pull/219
[#225]: https://github.com/PHPCSStandards/PHPCSUtils/pull/225
[#226]: https://github.com/PHPCSStandards/PHPCSUtils/pull/226
[#229]: https://github.com/PHPCSStandards/PHPCSUtils/pull/229
[#233]: https://github.com/PHPCSStandards/PHPCSUtils/pull/233
[#234]: https://github.com/PHPCSStandards/PHPCSUtils/pull/234
[#235]: https://github.com/PHPCSStandards/PHPCSUtils/pull/235
[#237]: https://github.com/PHPCSStandards/PHPCSUtils/pull/237
[#239]: https://github.com/PHPCSStandards/PHPCSUtils/pull/239
[#241]: https://github.com/PHPCSStandards/PHPCSUtils/pull/241
[#243]: https://github.com/PHPCSStandards/PHPCSUtils/pull/243
[#247]: https://github.com/PHPCSStandards/PHPCSUtils/pull/247
[#248]: https://github.com/PHPCSStandards/PHPCSUtils/pull/248
[#249]: https://github.com/PHPCSStandards/PHPCSUtils/pull/249
[#251]: https://github.com/PHPCSStandards/PHPCSUtils/pull/251
[#252]: https://github.com/PHPCSStandards/PHPCSUtils/pull/252
[#254]: https://github.com/PHPCSStandards/PHPCSUtils/pull/254
[#256]: https://github.com/PHPCSStandards/PHPCSUtils/pull/256
[#261]: https://github.com/PHPCSStandards/PHPCSUtils/pull/261
[#262]: https://github.com/PHPCSStandards/PHPCSUtils/pull/262
[#269]: https://github.com/PHPCSStandards/PHPCSUtils/pull/269
[#270]: https://github.com/PHPCSStandards/PHPCSUtils/pull/270
[#273]: https://github.com/PHPCSStandards/PHPCSUtils/pull/273
[#277]: https://github.com/PHPCSStandards/PHPCSUtils/pull/277
[#285]: https://github.com/PHPCSStandards/PHPCSUtils/pull/285
[#291]: https://github.com/PHPCSStandards/PHPCSUtils/pull/291
[#292]: https://github.com/PHPCSStandards/PHPCSUtils/pull/292
[#293]: https://github.com/PHPCSStandards/PHPCSUtils/pull/293
[#297]: https://github.com/PHPCSStandards/PHPCSUtils/pull/297
[#304]: https://github.com/PHPCSStandards/PHPCSUtils/pull/304
[#309]: https://github.com/PHPCSStandards/PHPCSUtils/pull/309
[#311]: https://github.com/PHPCSStandards/PHPCSUtils/pull/311
[#319]: https://github.com/PHPCSStandards/PHPCSUtils/pull/319
[#320]: https://github.com/PHPCSStandards/PHPCSUtils/pull/320
[#321]: https://github.com/PHPCSStandards/PHPCSUtils/pull/321
[#325]: https://github.com/PHPCSStandards/PHPCSUtils/pull/325
[#327]: https://github.com/PHPCSStandards/PHPCSUtils/pull/327
[#328]: https://github.com/PHPCSStandards/PHPCSUtils/pull/328
[#332]: https://github.com/PHPCSStandards/PHPCSUtils/pull/332
[#335]: https://github.com/PHPCSStandards/PHPCSUtils/pull/335
[#341]: https://github.com/PHPCSStandards/PHPCSUtils/pull/341
[#344]: https://github.com/PHPCSStandards/PHPCSUtils/pull/344
[#347]: https://github.com/PHPCSStandards/PHPCSUtils/pull/347
[#349]: https://github.com/PHPCSStandards/PHPCSUtils/pull/349
[#356]: https://github.com/PHPCSStandards/PHPCSUtils/pull/356
[#357]: https://github.com/PHPCSStandards/PHPCSUtils/pull/357
[#358]: https://github.com/PHPCSStandards/PHPCSUtils/pull/358
[#360]: https://github.com/PHPCSStandards/PHPCSUtils/pull/360
[#362]: https://github.com/PHPCSStandards/PHPCSUtils/pull/362
[#363]: https://github.com/PHPCSStandards/PHPCSUtils/pull/363
[#365]: https://github.com/PHPCSStandards/PHPCSUtils/pull/365
[#366]: https://github.com/PHPCSStandards/PHPCSUtils/pull/366
[#367]: https://github.com/PHPCSStandards/PHPCSUtils/pull/367
[#368]: https://github.com/PHPCSStandards/PHPCSUtils/pull/368
[#371]: https://github.com/PHPCSStandards/PHPCSUtils/pull/371
[#372]: https://github.com/PHPCSStandards/PHPCSUtils/pull/372
[#376]: https://github.com/PHPCSStandards/PHPCSUtils/pull/376
[#377]: https://github.com/PHPCSStandards/PHPCSUtils/pull/377
[#379]: https://github.com/PHPCSStandards/PHPCSUtils/pull/379
[#381]: https://github.com/PHPCSStandards/PHPCSUtils/pull/381
[#382]: https://github.com/PHPCSStandards/PHPCSUtils/pull/382
[#383]: https://github.com/PHPCSStandards/PHPCSUtils/pull/383
[#385]: https://github.com/PHPCSStandards/PHPCSUtils/pull/385
[#390]: https://github.com/PHPCSStandards/PHPCSUtils/pull/390
[#391]: https://github.com/PHPCSStandards/PHPCSUtils/pull/391
[#392]: https://github.com/PHPCSStandards/PHPCSUtils/pull/392
[#394]: https://github.com/PHPCSStandards/PHPCSUtils/pull/394
[#395]: https://github.com/PHPCSStandards/PHPCSUtils/pull/395
[#396]: https://github.com/PHPCSStandards/PHPCSUtils/pull/396
[#397]: https://github.com/PHPCSStandards/PHPCSUtils/pull/397
[#398]: https://github.com/PHPCSStandards/PHPCSUtils/pull/398

[PHPCS#3118]: https://github.com/squizlabs/PHP_CodeSniffer/issues/3118
[PHPUnit Polyfills]: https://github.com/Yoast/PHPUnit-Polyfills


## [1.0.0-alpha3] - 2020-06-29

Notes:

* While still in alpha, some BC-breaks may be introduced. These are clearly indicated in the changelog with the :warning: symbol.
* Until PHPCS 4.x has been released, PHPCSUtils does not formally support it, though an effort is made to keep up with the changes and anticipate potential compatibility issues.
    For testing purposes, the composer configuration allows for PHPCSUtils to be installed with PHPCS 4.x.
* Until PHP 8.0 has been released, PHPCSUtils does not formally support it, though an effort is made to keep up with the changes and anticipate potential compatibility issues.
    For testing purposes, the composer configuration allows for PHPCSUtils to be installed with PHP 8.

### Added

* New [`PHPCSUtils\Utils\NamingConventions`][`NamingConventions`] class: Utility functions for working with identifier names (namespace names, class/trait/interface names, function names, variable and constant names). [#119]
* New [`PHPCSUtils\BackCompat\Helper::getEncoding()`][Helper::getEncoding] method. [#118]
* New [`PHPCSUtils\Utils\ControlStructures::getCaughtExceptions()`][ControlStructures::getCaughtExceptions] method. [#114], [#138]
* New [`PHPCSUtils\Utils\UseStatements::splitAndMergeImportUseStatement()`][UseStatements::splitAndMergeImportUseStatement] method. [#117]

#### PHPCS BackCompat

* `BCFile::getMethodProperties()`: support for "static" as a return type (PHP 8.0). [#134], [PHPCS#2952]

#### TestUtils

* [`UtilityMethodTestCase`]: new public `$phpcsVersion` property for use in tests. [#107]
    **Note**: if the PHPCS version is needed within a data provider method for a test, `Helper::getVersion()` still needs to be used as the data providers are run before the `setUpBeforeClass()`-like methods which set the property.

#### Tokens

* New [`Collections::$incrementDecrementOperators`][`Collections`] property. [#130]
* New [`Collections::$magicConstants`][`Collections`] property. [#106]
* New [`Collections::$objectOperators`][`Collections`] property. [#130]
* New [`Collections::$OOHierarchyKeywords`][`Collections`] property representing the keywords to access properties or methods from inside a class definition, i.e `self`, `parent` and `static`. [#115]
* New [`Collections::$OONameTokens`][`Collections`] property containing tokens which can be part of a partially/fully qualified name when used in inline code. [#113]
* New [`Collections::functionDeclarationTokens()`][`Collections`] method to retrieve the tokens which represent a keyword starting a function declaration. [#133]
    This method is compatible with PHPCS 3.5.3 and higher.
* New [`Collections::functionDeclarationTokensBC()`][`Collections`] method to retrieve the tokens which represent a keyword starting a function declaration (cross-version compatible). [#133]
    This method is compatible with PHPCS 2.6.0 and higher.
* New [`Collections::parameterTypeTokensBC()`][`Collections`] method to retrieve the tokens which need to be recognized for parameter types cross-version. [#109]
    Use this method when the implementing standard needs to support PHPCS < 3.3.0.
* New [`Collections::propertyTypeTokensBC()`][`Collections`] method to retrieve the tokens which need to be recognized for property types cross-version. [#109]
    Use this method when the implementing standard needs to support PHPCS < 3.3.0.
* New [`Collections::returnTypeTokensBC()`][`Collections`] method to retrieve the tokens which need to be recognized for return types cross-version. [#109]
    Use this method when the implementing standard needs to support PHPCS < 3.5.4.
* `Collections::$returnTypeTokens`: support for "static" as a return type (PHP 8.0). [#134]

#### Utils

* `FunctionDeclarations::getProperties()`: support for "static" as a return type (PHP 8.0). [#134]

### Changed

#### PHPCS BackCompat

* `BCFile::getDeclarationName()`: has been made compatible with PHPCS 4.x. [#110]
* `BCFile::getMethodProperties()`: has been made compatible with PHPCS 4.x. [#109]
* `BCFile::getMemberProperties()`: has been made compatible with PHPCS 4.x. [#109]
* `BCTokens`: :warning: The visibility of the `BCTokens::$phpcsCommentTokensTypes`, `BCTokens::$ooScopeTokens`, `BCTokens::$textStringTokens` properties has changed from `protected` to `private`. [#139]
* `Helper::setConfigData()`: has been made compatible with PHPCS 4.x. [#137]
    A new `$config` parameter has been added to the method. This parameter is a required parameter when the method is used with PHPCS 4.x.

#### TestUtils

* [`UtilityMethodTestCase`]: tests for JS/CSS will now automatically be skipped when run in combination with PHPCS 4.x (which drops JS/CSS support). [#111]
* Confirmed that the currently available test utils are compatible with PHPUnit 9.x. [#103]

#### Tokens

* `Collections::$parameterTypeTokens`: has been made compatible with PHPCS 4.x. [#109]
    :warning: This removes support for PHPCS < 3.3.0 from the property. Use the [`Collections::parameterTypeTokensBC()`][`Collections`] method instead if PHPCS < 3.3.0 needs to be supported.
* `Collections::$propertyTypeTokens`: has been made compatible with PHPCS 4.x. [#109]
    :warning: This removes support for PHPCS < 3.3.0 from the property. Use the [`Collections::propertyTypeTokensBC()`][`Collections`] method instead if PHPCS < 3.3.0 needs to be supported.
* `Collections::$returnTypeTokens`: has been made compatible with PHPCS 4.x. [#109]
    :warning: This removes support for PHPCS < 3.5.4 from the property. Use the [`Collections::returnTypeTokensBC()`][`Collections`] method instead if PHPCS < 3.5.4 needs to be supported.

#### Utils

* `FunctionDeclarations::getArrowFunctionOpenClose()`: has been made compatible with PHPCS 4.x. [#109]
* `FunctionDeclarations::getProperties()`: has been made compatible with PHPCS 4.x. [#109]
* :warning: `Lists::getAssignments()`: the return value of the method has been consolidated to be less fiddly to work with. [#129]
    - :warning: The `nested_list` index key in the return value has been renamed to `is_nested_list`.
* `ObjectDeclarations::getName()`: has been made compatible with PHPCS 4.x. [#110]
* `Variables::getMemberProperties()`: has been made compatible with PHPCS 4.x. [#109]

#### Other

* Composer: PHPCSUtils can now be installed in combination with PHPCS `4.0.x-dev@dev` for testing purposes.
* Composer: The version requirements for the [Composer PHPCS plugin] have been widened to allow for version 0.7.0 which supports Composer 2.0.0.
* Readme/website homepage: textual improvements. Props [@GaryJones]. [#121]
* Readme/website homepage: added additional FAQ question & answers. [#157]
* The website homepage is now generated using the GitHub Pages gem with Jekyll, making maintenance easier. [#141]
* Significant improvements to the docblock documentation and by extension the [generated API documentation]. [#145], [#146], [#147], [#148], [#149], [#150], [#151], [#152], [#153], [#154], [#155], [#156]
* Various housekeeping.

### Fixed

#### Abstract Sniffs

* `AbstractArrayDeclarationSniff`: improved parse error handling. [#99]

#### PHPCS BackCompat

* `BCFile::findEndOfStatement()`: now supports arrow functions when used as a function argument, in line with the same change made in PHPCS 3.5.5. [#143]
* `BcFile::isReference()`: bug fix, the reference operator was not recognized as such for closures declared to return by reference. [#160], [PHPCS#2977]

#### Utils

* `FunctionDeclarations::getArrowFunctionOpenClose()`: now supports arrow functions when used as a function argument, in line with the same change made in PHPCS 3.5.5. [#143]
* `FunctionDeclarations::getArrowFunctionOpenClose()`: now supports for arrow functions returning heredoc/nowdocs, in line with the same change made in PHPCS `master` and expected to be released in PHPCS 3.5.6. [#143]
* `FunctionDeclarations::getName()`: bug fix for functions declared to return by reference. [#131]
* `FunctionDeclarations::isMagicFunction()`: bug fix for nested functions. [#127]
* `Operators::isReference()`: bug fix, the reference operator was not recognized as such for closures declared to return by reference. [#142]
* `Namespaces::getType()`: improved type detection for when the `namespace` keyword is used as an operator in the global namespace. [#132]
* `TextStrings::getCompleteTextString()`: will now remove the newline at the end of a heredoc/nowdoc. [#136]
    PHP itself does not include the last new line in a heredoc/nowdoc text string when handling it, so the method shouldn't either.

[generated API documentation]: https://phpcsutils.com/phpdoc/
[Helper::getEncoding]: https://phpcsutils.com/phpdoc/classes/PHPCSUtils-BackCompat-Helper.html#method_getEncoding
[ControlStructures::getCaughtExceptions]: https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-ControlStructures.html#method_getCaughtExceptions
[UseStatements::splitAndMergeImportUseStatement]: https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-UseStatements.html#method_splitAndMergeImportUseStatement

[#99]: https://github.com/PHPCSStandards/PHPCSUtils/pull/99
[#103]: https://github.com/PHPCSStandards/PHPCSUtils/pull/103
[#106]: https://github.com/PHPCSStandards/PHPCSUtils/pull/106
[#107]: https://github.com/PHPCSStandards/PHPCSUtils/pull/107
[#109]: https://github.com/PHPCSStandards/PHPCSUtils/pull/109
[#110]: https://github.com/PHPCSStandards/PHPCSUtils/pull/110
[#111]: https://github.com/PHPCSStandards/PHPCSUtils/pull/111
[#113]: https://github.com/PHPCSStandards/PHPCSUtils/pull/113
[#114]: https://github.com/PHPCSStandards/PHPCSUtils/pull/114
[#115]: https://github.com/PHPCSStandards/PHPCSUtils/pull/115
[#117]: https://github.com/PHPCSStandards/PHPCSUtils/pull/117
[#118]: https://github.com/PHPCSStandards/PHPCSUtils/pull/118
[#119]: https://github.com/PHPCSStandards/PHPCSUtils/pull/119
[#129]: https://github.com/PHPCSStandards/PHPCSUtils/pull/129
[#121]: https://github.com/PHPCSStandards/PHPCSUtils/pull/121
[#127]: https://github.com/PHPCSStandards/PHPCSUtils/pull/127
[#130]: https://github.com/PHPCSStandards/PHPCSUtils/pull/130
[#131]: https://github.com/PHPCSStandards/PHPCSUtils/pull/131
[#132]: https://github.com/PHPCSStandards/PHPCSUtils/pull/132
[#133]: https://github.com/PHPCSStandards/PHPCSUtils/pull/133
[#134]: https://github.com/PHPCSStandards/PHPCSUtils/pull/134
[#136]: https://github.com/PHPCSStandards/PHPCSUtils/pull/136
[#137]: https://github.com/PHPCSStandards/PHPCSUtils/pull/137
[#138]: https://github.com/PHPCSStandards/PHPCSUtils/pull/138
[#139]: https://github.com/PHPCSStandards/PHPCSUtils/pull/139
[#141]: https://github.com/PHPCSStandards/PHPCSUtils/pull/141
[#142]: https://github.com/PHPCSStandards/PHPCSUtils/pull/142
[#143]: https://github.com/PHPCSStandards/PHPCSUtils/pull/143
[#145]: https://github.com/PHPCSStandards/PHPCSUtils/pull/145
[#146]: https://github.com/PHPCSStandards/PHPCSUtils/pull/146
[#147]: https://github.com/PHPCSStandards/PHPCSUtils/pull/147
[#148]: https://github.com/PHPCSStandards/PHPCSUtils/pull/148
[#149]: https://github.com/PHPCSStandards/PHPCSUtils/pull/149
[#150]: https://github.com/PHPCSStandards/PHPCSUtils/pull/150
[#151]: https://github.com/PHPCSStandards/PHPCSUtils/pull/151
[#152]: https://github.com/PHPCSStandards/PHPCSUtils/pull/152
[#153]: https://github.com/PHPCSStandards/PHPCSUtils/pull/153
[#154]: https://github.com/PHPCSStandards/PHPCSUtils/pull/154
[#155]: https://github.com/PHPCSStandards/PHPCSUtils/pull/155
[#156]: https://github.com/PHPCSStandards/PHPCSUtils/pull/156
[#157]: https://github.com/PHPCSStandards/PHPCSUtils/pull/157
[#160]: https://github.com/PHPCSStandards/PHPCSUtils/pull/160
[PHPCS#2952]: https://github.com/squizlabs/PHP_CodeSniffer/pull/2952
[PHPCS#2977]: https://github.com/squizlabs/PHP_CodeSniffer/pull/2977


## [1.0.0-alpha2] - 2020-02-16

Note:

* While still in alpha, some BC-breaks may be introduced. These are clearly indicated in the changelog with the :warning: symbol.

### Added

* New [`PHPCSUtils\Utils\ControlStructures`][`ControlStructures`] class: Utility functions for use when examining control structures. [#70]
* New [`PHPCSUtils\Utils\FunctionDeclarations::isArrowFunction()`][FunctionDeclarations::isArrowFunction] method. [#77], [#79]
* New [`PHPCSUtils\Utils\FunctionDeclarations::getArrowFunctionOpenClose()`][FunctionDeclarations::getArrowFunctionOpenClose] method. [#77], [#79]

#### PHPCS BackCompat

* `BCFile::isReference()`: support for arrow functions returning by reference. [#77]
* `BCFile::getMethodParameters()`: support for arrow functions. [#77], [#79]
* `BCFile::getMethodProperties()`: support for arrow functions. [#77], [#79], [#89]
* `BCFile::getDeclarationName()`: allow functions to be called "fn". [#77]
* `BCFile::findEndOfStatement()`: support for arrow functions. [#77], [#79]
* `BCFile::findStartOfStatement()`: support for arrow functions. [#77]

#### Tokens

* New [`Collections::$alternativeControlStructureSyntaxTokens`][`Collections`] property. [#70]
* New [`Collections::$alternativeControlStructureSyntaxCloserTokens`][`Collections`] property. [#68], [#69]
* New [`Collections::$controlStructureTokens`][`Collections`] property. [#70]
* New [`Collections::arrowFunctionTokensBC()`][`Collections`] method. [#79]

#### Utils

* `Arrays::getDoubleArrowPtr()`: support for arrow functions. [#77], [#79], [#84]
* `FunctionDeclarations::getParameters()`: support for arrow functions. [#77], [#79]
* `FunctionDeclarations::getProperties()`: support for arrow functions. [#77], [#79]
* `Operators::isReference()`: support for arrow functions returning by reference. [#77]
* `Parentheses::getOwner()`: support for arrow functions. [#77]
* `Parentheses::isOwnerIn()`: support for arrow functions. [#77], [#79]

#### Other

* Documentation website at <https://phpcsutils.com/>

### Changed

#### PHPCS BackCompat

* `BCFile::getCondition()`: sync with PHPCS 3.5.4 - added support for new `$first` parameter. [#73]

#### Tokens

* The `Collections::$returnTypeTokens` property now includes `T_ARRAY` to allow for supporting arrow functions in PHPCS < 3.5.3. [#77]

#### Utils

* :warning: `Conditions::getCondition()`: sync with PHPCS 3.5.4 - renamed the existing `$reverse` parameter to `$first` and reversing the meaning of the boolean values, to stay in line with PHPCS itself. [#73]
* :warning: `Numbers`: the `$unsupportedPHPCSVersions` property has been replaced with an `UNSUPPORTED_PHPCS_VERSION` constant. [#88]

#### Other

* Various housekeeping.

[FunctionDeclarations::isArrowFunction]: https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-FunctionDeclarations.html#method_isArrowFunction
[FunctionDeclarations::getArrowFunctionOpenClose]: https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-FunctionDeclarations.html#method_getArrowFunctionOpenClose

[#68]: https://github.com/PHPCSStandards/PHPCSUtils/pull/68
[#69]: https://github.com/PHPCSStandards/PHPCSUtils/pull/69
[#70]: https://github.com/PHPCSStandards/PHPCSUtils/pull/70
[#73]: https://github.com/PHPCSStandards/PHPCSUtils/pull/73
[#77]: https://github.com/PHPCSStandards/PHPCSUtils/pull/77
[#79]: https://github.com/PHPCSStandards/PHPCSUtils/pull/79
[#84]: https://github.com/PHPCSStandards/PHPCSUtils/pull/84
[#88]: https://github.com/PHPCSStandards/PHPCSUtils/pull/88
[#89]: https://github.com/PHPCSStandards/PHPCSUtils/pull/89


## 1.0.0-alpha1 - 2020-01-23

Initial alpha release containing:

* A `PHPCS23Utils` standard which can be used to allow an external PHPCS standard to be compatible with both PHPCS 2.x as well as 3.x.
* A `PHPCSUtils` standard which contains generic utilities which can be used when writing sniffs.
    **_This standard does not contain any sniffs!_**
    To use these utilities in PHPCS 3.x, all that is needed is for this package to be installed and registered with PHPCS using `installed_paths`. If the package is requested via Composer, this will automatically be handled by the [Composer PHPCS plugin].
    To use these utilities in PHPCS 2.x, make sure the external standard includes the `PHPCS23Utils` standard in the `ruleset.xml` file like so: `<rule ref="PHPCS23Utils"/>`.

All utilities offered are compatible with PHP_CodeSniffer 2.6.0 up to the latest stable release.

This initial alpha release contains the following utility classes:

### Abstract Sniffs

* [`AbstractArrayDeclarationSniff`]: to examine array declarations.

### BackCompat

* [`BCFile`]: Backport of the latest versions of PHPCS native utility functions from the `PHP_CodeSniffer\Files\File` class to make them available in older PHPCS versions without the bugs and other quirks that the older versions of the native functions had.
* [`BCTokens`]: Backport of the latest versions of PHPCS native token arrays from the `PHP_CodeSniffer\Util\Tokens` class to make them available in older PHPCS versions.
* [`Helper`]: Utility methods to retrieve (configuration) information from PHP_CodeSniffer 2.x as well as 3.x.

### Fixers

* [`SpacesFixer`]: Utility to check and, if necessary, fix the whitespace between two tokens.

### TestUtils

* [`UtilityMethodTestCase`]: Base class for use when testing utility methods for PHP_CodeSniffer.
    Compatible with both PHPCS 2.x as well as 3.x. Supports PHPUnit 4.x up to 8.x.
    See the usage instructions in the class docblock.

### Tokens

* [`Collections`]: Collections of related tokens as often used and needed for sniffs.
    These are additional "token groups" to compliment the ones available through the PHPCS native `PHP_CodeSniffer\Util\Tokens` class.

### Utils

* [`Arrays`]: Utility functions for use when examining arrays.
* [`Conditions`]: Utility functions for use when examining token conditions.
* [`FunctionDeclarations`]: Utility functions for use when examining function declaration statements.
* [`GetTokensAsString`]: Utility functions to retrieve the content of a set of tokens as a string.
* [`Lists`]: Utility functions to retrieve information when working with lists.
* [`Namespaces`]: Utility functions for use when examining T_NAMESPACE tokens and to determine the namespace of arbitrary tokens.
* [`Numbers`]: Utility functions for working with integer/float tokens.
* [`ObjectDeclarations`]: Utility functions for use when examining object declaration statements.
* [`Operators`]: Utility functions for use when working with operators.
* [`Orthography`]: Utility functions for checking the orthography of arbitrary text strings.
* [`Parentheses`]: Utility functions for use when examining parenthesis tokens and arbitrary tokens wrapped in parentheses.
* [`PassedParameters`]: Utility functions to retrieve information about parameters passed to function calls, array declarations, isset and unset constructs.
* [`Scopes`]: Utility functions for use when examining token scopes.
* [`TextStrings`]: Utility functions for working with text string tokens.
* [`UseStatements`]: Utility functions for examining use statements.
* [`Variables`]: Utility functions for use when examining variables.


[Unreleased]:   https://github.com/PHPCSStandards/PHPCSUtils/compare/stable...HEAD
[1.2.1]:        https://github.com/PHPCSStandards/PHPCSUtils/compare/1.2.0...1.2.1
[1.2.0]:        https://github.com/PHPCSStandards/PHPCSUtils/compare/1.1.3...1.2.0
[1.1.3]:        https://github.com/PHPCSStandards/PHPCSUtils/compare/1.1.2...1.1.3
[1.1.2]:        https://github.com/PHPCSStandards/PHPCSUtils/compare/1.1.1...1.1.2
[1.1.1]:        https://github.com/PHPCSStandards/PHPCSUtils/compare/1.1.0...1.1.1
[1.1.0]:        https://github.com/PHPCSStandards/PHPCSUtils/compare/1.0.12...1.1.0
[1.0.12]:       https://github.com/PHPCSStandards/PHPCSUtils/compare/1.0.11...1.0.12
[1.0.11]:       https://github.com/PHPCSStandards/PHPCSUtils/compare/1.0.10...1.0.11
[1.0.10]:       https://github.com/PHPCSStandards/PHPCSUtils/compare/1.0.9...1.0.10
[1.0.9]:        https://github.com/PHPCSStandards/PHPCSUtils/compare/1.0.8...1.0.9
[1.0.8]:        https://github.com/PHPCSStandards/PHPCSUtils/compare/1.0.7...1.0.8
[1.0.7]:        https://github.com/PHPCSStandards/PHPCSUtils/compare/1.0.6...1.0.7
[1.0.6]:        https://github.com/PHPCSStandards/PHPCSUtils/compare/1.0.5...1.0.6
[1.0.5]:        https://github.com/PHPCSStandards/PHPCSUtils/compare/1.0.4...1.0.5
[1.0.4]:        https://github.com/PHPCSStandards/PHPCSUtils/compare/1.0.3...1.0.4
[1.0.3]:        https://github.com/PHPCSStandards/PHPCSUtils/compare/1.0.2...1.0.3
[1.0.2]:        https://github.com/PHPCSStandards/PHPCSUtils/compare/1.0.1...1.0.2
[1.0.1]:        https://github.com/PHPCSStandards/PHPCSUtils/compare/1.0.0...1.0.1
[1.0.0]:        https://github.com/PHPCSStandards/PHPCSUtils/compare/1.0.0-rc1...1.0.0
[1.0.0-rc1]:    https://github.com/PHPCSStandards/PHPCSUtils/compare/1.0.0-alpha4...1.0.0-rc1
[1.0.0-alpha4]: https://github.com/PHPCSStandards/PHPCSUtils/compare/1.0.0-alpha3...1.0.0-alpha4
[1.0.0-alpha3]: https://github.com/PHPCSStandards/PHPCSUtils/compare/1.0.0-alpha2...1.0.0-alpha3
[1.0.0-alpha2]: https://github.com/PHPCSStandards/PHPCSUtils/compare/1.0.0-alpha1...1.0.0-alpha2

[Composer PHPCS plugin]: https://github.com/PHPCSStandards/composer-installer
[PHP_CodeSniffer]:       https://github.com/PHPCSStandards/PHP_CodeSniffer

[`AbstractArrayDeclarationSniff`]: https://phpcsutils.com/phpdoc/classes/PHPCSUtils-AbstractSniffs-AbstractArrayDeclarationSniff.html
[`BCFile`]:                        https://phpcsutils.com/phpdoc/classes/PHPCSUtils-BackCompat-BCFile.html
[`BCTokens`]:                      https://phpcsutils.com/phpdoc/classes/PHPCSUtils-BackCompat-BCTokens.html
[`Helper`]:                        https://phpcsutils.com/phpdoc/classes/PHPCSUtils-BackCompat-Helper.html
[`SpacesFixer`]:                   https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Fixers-SpacesFixer.html
[`ConfigDouble`]:                  https://phpcsutils.com/phpdoc/classes/PHPCSUtils-TestUtils-ConfigDouble.html
[`RulesetDouble`]:                 https://phpcsutils.com/phpdoc/classes/PHPCSUtils-TestUtils-RulesetDouble.html
[`UtilityMethodTestCase`]:         https://phpcsutils.com/phpdoc/classes/PHPCSUtils-TestUtils-UtilityMethodTestCase.html
[`Collections`]:                   https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Tokens-Collections.html
[`TokenHelper`]:                   https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Tokens-TokenHelper.html
[`Arrays`]:                        https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-Arrays.html
[`AttributeBlock`]:                https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-AttributeBlock.html
[`Conditions`]:                    https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-Conditions.html
[`Constants`]:                     https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-Constants.html
[`Context`]:                       https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-Context.html
[`ControlStructures`]:             https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-ControlStructures.html
[`FunctionDeclarations`]:          https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-FunctionDeclarations.html
[`GetTokensAsString`]:             https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-GetTokensAsString.html
[`FileInfo`]:                      https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-FileInfo.html
[`FilePath`]:                      https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-FilePath.html
[`Lists`]:                         https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-Lists.html
[`MessageHelper`]:                 https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-MessageHelper.html
[`Namespaces`]:                    https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-Namespaces.html
[`NamingConventions`]:             https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-NamingConventions.html
[`Numbers`]:                       https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-Numbers.html
[`ObjectDeclarations`]:            https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-ObjectDeclarations.html
[`Operators`]:                     https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-Operators.html
[`Orthography`]:                   https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-Orthography.html
[`Parentheses`]:                   https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-Parentheses.html
[`PassedParameters`]:              https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-PassedParameters.html
[`Scopes`]:                        https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-Scopes.html
[`TextStrings`]:                   https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-TextStrings.html
[`TypeString`]:                    https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-TypeString.html
[`UseStatements`]:                 https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-UseStatements.html
[`Variables`]:                     https://phpcsutils.com/phpdoc/classes/PHPCSUtils-Utils-Variables.html

[@DanielEScherzer]: https://github.com/DanielEScherzer
[@fredden]:         https://github.com/fredden
[@GaryJones]:       https://github.com/GaryJones
[@szepeviktor]:     https://github.com/szepeviktor
