# Change Log for the PHPCSExtra standard for PHP_CodeSniffer

All notable changes to this project will be documented in this file.

This projects adheres to [Keep a CHANGELOG](https://keepachangelog.com/) and uses [Semantic Versioning](https://semver.org/).

**Legend**:
:wrench: = Includes auto-fixer.
:bar_chart: = Includes metrics.
:books: = Includes CLI documentation.


## [Unreleased]

_Nothing yet._


## [1.5.0] - 2025-11-12

### Added

#### Universal

* :wrench: :bar_chart: :books: New `Universal.Attributes.BracketSpacing` sniff to enforce a fixed number of spaces on the inside of attribute block brackets. [#386], [#406]
    The sniff offers the following properties to influence its behaviour:
    - `spacing` (defaults to `0`).
    - `ignoreNewlines` (defaults to `false`).
        When new lines are allowed (`ignoreNewlines`=`true`) and a new line is found, the sniff will verify that there are no superfluous blank lines at the start/end of an attribute block.
* :wrench: :bar_chart: :books: New `Universal.Attributes.DisallowAttributeParentheses` sniff to forbid parentheses for attribute instantiations without arguments. [#387], [#409]
* :wrench: :bar_chart: :books: New `Universal.Attributes.RequireAttributeParentheses` sniff to demand that all attribute instantiations always use parentheses, even if no argument is passed. [#387], [#410]
* :wrench: :bar_chart: :books: New `Universal.Attributes.TrailingComma` sniff to demand a trailing comma for multi-line, multi-attribute attribute blocks and forbid trailing commas in single-line attribute blocks and in multi-line attributes containing only a single attribute. [#397], [#413]
* :wrench: :bar_chart: :books: New `Universal.PHP.DisallowExitDieParentheses` sniff to forbid invocations of `exit`/`die` with parentheses when no argument is passed. [#399]
* :wrench: :bar_chart: :books: New `Universal.PHP.RequireExitDieParentheses` sniff to demand that invocations of `exit`/`die` always use parentheses, even if no argument is passed. [#398]
* :wrench: :books: New `Universal.WhiteSpace.FirstClassCallableSpacing` sniff to enforce the spacing around the ellipses for first class callables. [#385]
    The sniff offers the following property to influence its behaviour: `spacing` (defaults to `0`).

### Changed

#### Universal

* `Universal.WhiteSpace.CommaSpacing`: improved handling of commas in PHP attributes. [#391], [#412]
    - The spacing after a trailing comma (after the last attribute in a block) will no longer be checked to prevent potential conflicts with attribute bracket spacing sniffs.
    - Spacing errors for commas between attributes in an attribute block will now get an `*InAttributeBlock` error code suffix, so in-/excluding them selectively is more straight-forward.
        Note: spacing around commas between _parameters passed to an attribute_ will still be reported with the `*InFunctionCall` error code suffix, same as before.
    - Metrics for comma spacing in attribute blocks is now also measured and reported separately.

#### Other

* Composer: The minimum `PHP_CodeSniffer` requirement has been updated to `^3.13.5 || ^4.0.1` (was `^3.13.4 || ^4.0.0`). [#408]
* Composer: The minimum `PHPCSUtils` requirement has been updated to `^1.2.0` (was `^1.1.2`). [#408]
* Various housekeeping.

[#385]: https://github.com/PHPCSStandards/PHPCSExtra/pull/385
[#386]: https://github.com/PHPCSStandards/PHPCSExtra/issues/386
[#387]: https://github.com/PHPCSStandards/PHPCSExtra/issues/387
[#391]: https://github.com/PHPCSStandards/PHPCSExtra/issues/391
[#397]: https://github.com/PHPCSStandards/PHPCSExtra/issues/397
[#398]: https://github.com/PHPCSStandards/PHPCSExtra/pull/398
[#399]: https://github.com/PHPCSStandards/PHPCSExtra/pull/399
[#406]: https://github.com/PHPCSStandards/PHPCSExtra/pull/406
[#408]: https://github.com/PHPCSStandards/PHPCSExtra/pull/408
[#409]: https://github.com/PHPCSStandards/PHPCSExtra/pull/409
[#410]: https://github.com/PHPCSStandards/PHPCSExtra/pull/410
[#412]: https://github.com/PHPCSStandards/PHPCSExtra/pull/412
[#413]: https://github.com/PHPCSStandards/PHPCSExtra/pull/413


## [1.4.2] - 2025-10-28

### Changed

#### Other

* Various housekeeping.

### Fixed

#### Universal

* `Universal.OOStructures.AlphabeticExtendsImplements` incorrectly displayed the "expected" and "found" names. [#378]
* `Universal.WhiteSpace.CommaSpacing` could accidentally cause a fatal error if the content adjacent to the comma was a text string containing percentage signs. Thanks [@westonruter] for reporting! [#400], [#401]

[#378]: https://github.com/PHPCSStandards/PHPCSExtra/pull/378
[#400]: https://github.com/PHPCSStandards/PHPCSExtra/issues/400
[#401]: https://github.com/PHPCSStandards/PHPCSExtra/pull/401


## [1.4.1] - 2025-09-05

### Changed

#### Other

* Various housekeeping.


### Fixed

#### Universal

* `Universal.PHP.NoFQNTrueFalseNull`: fix breakage due to upstream change in tokenization. [#375]

[#375]: https://github.com/PHPCSStandards/PHPCSExtra/pull/375


## [1.4.0] - 2025-06-14

### Added

* All sniffs: compatibility with the new PHP_CodeSniffer `4.x` branch in anticipation of the PHP_CodeSniffer 4.0 release. [#367]
    PHPCSExtra should now be fully compatible with PHP_CodeSniffer 4.0. If you still find an issue, please report it.

### Changed

#### Other

* Composer: The minimum `PHP_CodeSniffer` requirement has been updated to `^3.13.0` (was `^3.12.1`). [#361]
* Composer: The minimum `PHPCSUtils` requirement has been updated to `^1.1.0` (was `^1.0.12`). [#361]
* Various housekeeping.

### Deprecated

#### Universal

* `Universal.WhiteSpace.PrecisionAlignment`: support for scanning JS/CSS files via this sniff is now deprecated. [#367]

### Fixed

#### Universal

* `Universal.Arrays.DuplicateArrayKey`: false positives for numeric strings with leading/trailing underscores. Fixed via the update to PHPCSUtils 1.1.0.  [#363]
* `Universal.Arrays.DuplicateArrayKey`: false negatives for fully qualified `true`/`false`/`null` arrays keys. Fixed via the update to PHPCSUtils 1.1.0. [#363]
* `Universal.WhiteSpace.CommaSpacing`: wrong errorcode for comma's in closure use statements after the update to PHPCSUtils 1.1.0. [#362]

[#361]: https://github.com/PHPCSStandards/PHPCSExtra/pull/361
[#362]: https://github.com/PHPCSStandards/PHPCSExtra/pull/362
[#363]: https://github.com/PHPCSStandards/PHPCSExtra/pull/363
[#367]: https://github.com/PHPCSStandards/PHPCSExtra/pull/367


## [1.3.1] - 2025-06-08

### Changed

#### Other

* Composer: The minimum `PHPCSUtils` requirement has been updated to `^1.0.12` (was `^1.0.9`). [#346]
* Various housekeeping.

### Fixed

#### Universal

* `Universal.UseStatements.DisallowMixedGroupUse`: the fixer could get confused when the "base" name for the group name contained a leading backslash, leading to parse errors in the fixed code. [#350]

[#346]: https://github.com/PHPCSStandards/PHPCSExtra/pull/346
[#350]: https://github.com/PHPCSStandards/PHPCSExtra/pull/350


## [1.3.0] - 2025-04-21

### Added

#### Universal

* :wrench: :books: New `Universal.PHP.NoFQNTrueFalseNull` sniff to forbid using `true`, `false` and `null` as fully qualified constants. Thanks [@rodrigoprimo] for reviewing. [#327]
* `Universal.Operators.TypeSeparatorSpacing`: support for checking the spacing around the parentheses used in PHP 8.2+ DNF types. [#329]

### Changed

#### Universal

* The `Universal.WhiteSpace.DisallowInlineTabs` sniff will now also check for inline tabs in heredoc/nowdoc openers and between the `yield` and `from` keywords. [#320]

#### Other

* Composer: The minimum `PHP_CodeSniffer` requirement has been updated to `^3.12.1` (was `^3.8.0`). [#330]
* Developer happiness: prevent creating a `composer.lock` file. Thanks [@fredden]! [#307]
* Various housekeeping, including improvements to the documentation and tests.

[#307]: https://github.com/PHPCSStandards/PHPCSExtra/pull/307
[#320]: https://github.com/PHPCSStandards/PHPCSExtra/pull/320
[#327]: https://github.com/PHPCSStandards/PHPCSExtra/pull/327
[#329]: https://github.com/PHPCSStandards/PHPCSExtra/pull/329
[#330]: https://github.com/PHPCSStandards/PHPCSExtra/pull/330


## [1.2.1] - 2023-12-08

### Changed

#### Other

* Composer: The minimum `PHP_CodeSniffer` requirement has been updated to `^3.8.0` (was `^3.7.2`). [#298]
* Composer: The minimum `PHPCSUtils` requirement has been updated to `^1.0.9` (was `^1.0.8`). [#298]

Please ensure you run `composer update phpcsstandards/phpcsextra --with-dependencies` to benefit from this.

[#298]: https://github.com/PHPCSStandards/PHPCSExtra/pull/298


## [1.2.0] - 2023-12-02

### Added

#### Universal

* :wrench: :books: New `Universal.CodeAnalysis.NoDoubleNegative` sniff to detect double negatives (!!) and advise to use a boolean cast instead. Thanks [@diedexx] for reviewing. [#277]
* :wrench: :books: New `Universal.Operators.ConcatPosition` sniff to enforce that the concatenation operator for multi-line concatenations is in a preferred position, either always at the start of the next line or always at the end of the previous line. [#294]
* :wrench: :bar_chart: :books: New `Universal.PHP.LowercasePHPTag` sniff to enforce that the "PHP" in a PHP open tag is lowercase. Thanks [@fredden] for reviewing. [#276]

### Changed

#### NormalizedArrays

* `NormalizedArrays.Arrays.CommaAfterLast`: the sniff now has two extra error codes to distinguish between multi-line arrays with the last array item on the _same line_ as the array closer vs the last array item being on a line _before_ the array closer. Thanks [@stronk7] for suggesting and patching this. [#283], [#284]
    These new error codes allow for selectively excluding that specific situation from triggering the sniff.
    The new error codes are `FoundMultiLineCloserSameLine` (for `multiLine="forbid"`) and `MissingMultiLineCloserSameLine` (for `multiLine="enforce"`).
    The pre-existing `FoundMultiLine` and `FoundSingleLine` error codes continue to be used for multi-line arrays with the last array item on a different line than the array closer.

#### Other

* Various housekeeping.

[#276]: https://github.com/PHPCSStandards/PHPCSExtra/pull/276
[#277]: https://github.com/PHPCSStandards/PHPCSExtra/pull/277
[#283]: https://github.com/PHPCSStandards/PHPCSExtra/issues/283
[#284]: https://github.com/PHPCSStandards/PHPCSExtra/pull/284
[#294]: https://github.com/PHPCSStandards/PHPCSExtra/pull/294


## [1.1.2] - 2023-09-21

### Changed

#### Other

* Various housekeeping.

### Fixed

#### Universal

* `Universal.CodeAnalysis.ConstructorDestructorReturn`: the sniff will now correctly ignore methods mirroring the class name (PHP-4 style constructors) in namespaced code. [#207], [#272]

[#272]: https://github.com/PHPCSStandards/PHPCSExtra/pull/272


## [1.1.1] - 2023-08-26

### Changed

#### Modernize

* `Modernize.FunctionCalls.Dirname`: the sniff will now respect a potentially set [`php_version` configuration option][php_version-config] and only report on modernizations which are possible on the configured `php_version`. [#261]
    If the `php_version` is not set, the sniff will continue to report on all modernization options.

#### Other

* Various documentation improvements. Props in part to [@szepeviktor].
* Improved defensive coding in select places.
* Various housekeeping.

[#261]: https://github.com/PHPCSStandards/PHPCSExtra/pull/261


## [1.1.0] - 2023-07-19

### Added

#### Universal

* :wrench: :books: New `Universal.CodeAnalysis.NoEchoSprintf` sniff to detect use of the inefficient `echo [v]sprintf(...);` combi and recommends using `[v]printf()` instead. [#242]
* :bar_chart: :books: New `Universal.FunctionDeclarations.NoLongClosures` sniff to detect "long" closures and recommend using a named function instead. [#240]
    The sniff offers the following properties to influence its behaviour: `recommendedLines` (defaults to `5`), `maxLines` (defaults to `8`), `ignoreCommentLines` (defaults to `true`) and `ignoreEmptyLines` (defaults to `true`).
* :wrench: :bar_chart: :books: New `Universal.FunctionDeclarations.RequireFinalMethodsInTraits` sniff to enforce non-private, non-abstract methods in traits to be declared as `final`. [#243], [#245]
    There is a separate `NonFinalMagicMethodFound` error code for magic methods to allow those to be excluded from the check.
* :wrench: :bar_chart: :books: New `Universal.UseStatements.DisallowMixedGroupUse` sniff to disallow group use statements which import a combination of namespace/OO construct, functions and/or constants in one statement. [#241], [#246]
    Note: the fixer will use a semi-standardized format for group use statements. If there are more specific requirements for the formatting of group use statements, the ruleset configurator should ensure that additional sniffs are included in the ruleset to enforce the required format.
* :wrench: :bar_chart: :books: New `Universal.UseStatements.KeywordSpacing` sniff to enforce the use of a single space after the `use`, `function`, `const` keywords and both before and after the `as` keyword in import `use` statements. [#247]
    The sniff has modular error codes to allow for disabling individual checks.
* :wrench: :books: New `Universal.UseStatements.NoUselessAliases` sniff to detect useless aliases (aliasing something to its original name) in import use statements. [#244]
    Note: as OO and function names in PHP are case-insensitive, aliasing to the same name, using a different case is also considered useless.
* :wrench: :bar_chart: :books: New `Universal.WhiteSpace.CommaSpacing` sniff to enforce that there is no space before a comma and exactly one space, or a new line, after a comma. [#254]
    Additionally, the sniff also enforces that the comma should follow the code and not be placed after a trailing comment.
    The sniff has modular error codes to allow for disabling individual checks and checks in certain contexts.
    The sniff will respect a potentially set [`php_version` configuration option][php_version-config] when deciding how to handle the spacing after a heredoc/nowdoc closer.

### Changed

#### Universal

* Minor performance improvements for the `Universal.Arrays.DuplicateArrayKey` and the `Universal.CodeAnalysis.ConstructorDestructorReturn` sniffs. [#251], [#252]

#### Other

* Composer: The minimum `PHPCSUtils` requirement has been updated to `^1.0.8` (was `^1.0.6`). [#249], [#254]
* Various housekeeping.

[#240]: https://github.com/PHPCSStandards/PHPCSExtra/pull/240
[#241]: https://github.com/PHPCSStandards/PHPCSExtra/pull/241
[#242]: https://github.com/PHPCSStandards/PHPCSExtra/pull/242
[#243]: https://github.com/PHPCSStandards/PHPCSExtra/pull/243
[#244]: https://github.com/PHPCSStandards/PHPCSExtra/pull/244
[#245]: https://github.com/PHPCSStandards/PHPCSExtra/pull/245
[#246]: https://github.com/PHPCSStandards/PHPCSExtra/pull/246
[#247]: https://github.com/PHPCSStandards/PHPCSExtra/pull/247
[#249]: https://github.com/PHPCSStandards/PHPCSExtra/pull/249
[#251]: https://github.com/PHPCSStandards/PHPCSExtra/pull/251
[#252]: https://github.com/PHPCSStandards/PHPCSExtra/pull/252
[#254]: https://github.com/PHPCSStandards/PHPCSExtra/pull/254


## [1.0.4] - 2023-06-18

### Changed

#### Other

* Composer: The minimum `PHPCSUtils` requirement has been updated to `^1.0.6` (was `^1.0.0`). [#237]
* Various housekeeping.

### Fixed

#### Universal

* `Universal.Constants.LowercaseClassResolutionKeyword`: prevent false positives for function calls to methods called `class`. [#226]

[#226]: https://github.com/PHPCSStandards/PHPCSExtra/pull/226
[#237]: https://github.com/PHPCSStandards/PHPCSExtra/pull/237


## [1.0.3] - 2023-03-28

### Changed

#### Universal

* `Universal.WhiteSpace.DisallowInlineTabs`: significant performance improvement. [#216], [#217]

#### Other

* Various housekeeping.

### Fixed

#### Modernize

* `Modernize.FunctionCalls.Dirname`: prevent false positives for attribute classes called `dirname`. [#211], [#213]

[#211]: https://github.com/PHPCSStandards/PHPCSExtra/pull/211
[#213]: https://github.com/PHPCSStandards/PHPCSExtra/pull/213
[#216]: https://github.com/PHPCSStandards/PHPCSExtra/pull/216
[#217]: https://github.com/PHPCSStandards/PHPCSExtra/pull/217


## [1.0.2] - 2023-01-10

### Changed

#### Universal

* `Universal.CodeAnalysis.ConstructorDestructorReturn`: the sniff will now respect a potentially set [`php_version` configuration option][php_version-config] and only report on PHP4-style constructors when the `php_version` is below `'80000'`. Thanks [@anomiex] for reporting! [#207], [#208]

[#207]: https://github.com/PHPCSStandards/PHPCSExtra/issues/207
[#208]: https://github.com/PHPCSStandards/PHPCSExtra/pull/208


## [1.0.1] - 2023-01-05

### Fixed

#### Universal

* `Universal.CodeAnalysis.ConstructorDestructorReturn`: fixed false positive for return statements in nested functions/closures declared within constructor/destructor methods. Thanks [@anomiex] for reporting! [#201], [#202]

[#201]: https://github.com/PHPCSStandards/PHPCSExtra/issues/201
[#202]: https://github.com/PHPCSStandards/PHPCSExtra/pull/202


## [1.0.0] - 2023-01-04

:warning: Important: this package now requires [PHPCSUtils 1.0.0]. Please make sure you use `--with-[all-]dependencies` when running `composer update`. :exclamation:

For the full list of features, please see the changelogs of the alpha/rc releases:
* [1.0.0-rc1](https://github.com/PHPCSStandards/PHPCSExtra/releases/tag/1.0.0-rc1)
* [1.0.0-alpha3](https://github.com/PHPCSStandards/PHPCSExtra/releases/tag/1.0.0-alpha3)
* [1.0.0-alpha2](https://github.com/PHPCSStandards/PHPCSExtra/releases/tag/1.0.0-alpha2)
* [1.0.0-alpha1](https://github.com/PHPCSStandards/PHPCSExtra/releases/tag/1.0.0-alpha1)

### Changed

#### Other

* Updated various sniffs to take advantage of PHPCSUtils 1.0.0(-rc1). [#193], [#194], [#195]
* Minor documentation improvements.
* Various housekeeping.

### Fixed

#### Modernize

* `Modernize.FunctionCalls.Dirname`: the sniff will now correctly recognize magic constants in a case-insensitive manner. [#187]

[PHPCSUtils 1.0.0]: https://github.com/PHPCSStandards/PHPCSUtils/releases/tag/1.0.0

[#187]: https://github.com/PHPCSStandards/PHPCSExtra/pull/187
[#193]: https://github.com/PHPCSStandards/PHPCSExtra/pull/193
[#194]: https://github.com/PHPCSStandards/PHPCSExtra/pull/194
[#195]: https://github.com/PHPCSStandards/PHPCSExtra/pull/195


## [1.0.0-RC1] - 2022-12-07

:warning: Important: this package now requires [PHPCSUtils 1.0.0-alpha4]. Please make sure you use `--with-[all-]dependencies` when running `composer update`. :exclamation:

### Added

#### Modernize

* This is a new standard with one sniff to start with.
* :wrench: :books: New `Modernize.FunctionCalls.Dirname` sniff to detect and auto-fix two typical code modernizations which can be made related to the [`dirname()`][php-manual-dirname] function. [#172]

#### Universal

* :wrench: :bar_chart: :books: New `Universal.Classes.DisallowAnonClassParentheses` sniff to disallow the use of parentheses when declaring an anonymous class without passing parameters. [#76], [#162]
* :wrench: :bar_chart: :books: New `Universal.Classes.RequireAnonClassParentheses` sniff to require the use of parentheses when declaring an anonymous class, whether parameters are passed or not. [#76], [#166]
* :wrench: :bar_chart: :books: New `Universal.Classes.DisallowFinalClass` sniff to disallow classes being declared `final`. [#108], [#114], [#148], [#163]
* :wrench: :bar_chart: :books: New `Universal.Classes.RequireFinalClass` sniff to require all non-`abstract` classes to be declared `final`. [#109], [#148], [#164]
    Warning: the auto-fixer for this sniff _may_ have unintended side-effects for applications and should be used with care! This is considered a _risky_ fixer.
* :wrench: :bar_chart: :books: New `Universal.Classes.ModifierKeywordOrder` sniff to standardize the modifier keyword order for class declarations. [#142]
    The sniff offers an `order` property to specify the preferred order.
* :wrench: :books: New `Universal.CodeAnalysis.ConstructorDestructorReturn` sniff to verify that class constructor/destructor methods 1) do not have a return type declaration and 2) do not return a value. [#137], [#140], [#146] Inspired by [@derickr].
* :wrench: :books: New `Universal.CodeAnalysis.ForeachUniqueAssignment` sniff to detect `foreach` control structures which use the same variable for both the key as well as the value assignment as this will lead to unexpected - and most likely unintended - behaviour. [#110], [#175]
    The fixer will maintain the existing behaviour of the code. Mind: this may not be the _intended_ behaviour.
* :wrench: :books: New `Universal.CodeAnalysis.StaticInFinalClass` sniff to detect using `static` instead of `self` in OO constructs which are `final`. [#116], [#180]
    The sniff has modular error codes to allow for making exceptions based on the type of use for `static`.
* :wrench: :bar_chart: :books: New `Universal.Constants.LowercaseClassResolutionKeyword` sniff to enforce that the `class` keyword when used for class name resolution, i.e. `::class`, is in lowercase. [#72]
* :wrench: :bar_chart: :books: New `Universal.Constants.ModifierKeywordOrder` sniff to standardize the modifier keyword order for OO constant declarations. [#143]
    The sniff offers an `order` property to specify the preferred order.
* :wrench: :books: New `Universal.ControlStructures.DisallowLonelyIf` sniff to disallow `if` statements as the only statement in an `else` block. [#85], [#168], [#169]
    Inspired by the [ESLint "no lonely if"] rule.
    Note: This sniff will not fix the indentation of the "inner" code. It is strongly recommended to run this sniff together with the `Generic.WhiteSpace.ScopeIndent` sniff to get the correct indentation.
* :bar_chart: :books: New `Universal.Files.SeparateFunctionsFromOO` sniff to enforce that a file should either declare (global/namespaced) functions or declare OO structures, but not both. [#95], [#170], [#171]
    Nested function declarations, i.e. functions declared within a function/method will be disregarded for the purposes of this sniff.
    The same goes for anonymous classes, closures and arrow functions.
* :books: New `Universal.NamingConventions.NoReservedKeywordParameterNames` sniff to verify that function parameters do not use reserved keywords as names, as this can quickly become confusing when people use them in function calls using named parameters. [#80], [#81], [#106], [#107], [#173]
    The sniff has modular error codes to allow for making exceptions for specific keywords.
* :wrench: :bar_chart: :books: New `Universal.Operators.TypeSeparatorSpacing` sniff to enforce no spaces around union type and intersection type separators. [#117]
* :wrench: :books: New `Universal.PHP.OneStatementInShortEchoTag` sniff to disallow short open echo tags `<?=` containing more than one PHP statement. [#89], [#147], [#165]
* :wrench: :bar_chart: :books: New `Universal.WhiteSpace.AnonClassKeywordSpacing` sniff to standardize the amount of spacing between the `class` keyword and the open parenthesis (if any) for anonymous class declarations. [#120]
    The sniff offers a `spacing` property to set the amount of spaces the sniff should check for.
* :wrench: :books: New `Universal.WhiteSpace.PrecisionAlignment` sniff to enforce indentation to always be a multiple of a tabstop, i.e. disallow precision alignment. [#119], [#122], [#123], [#124]
    Note:
    - This sniff does not concern itself with tabs versus spaces.
        It is recommended to use the sniff in combination with the PHPCS native `Generic.WhiteSpace.DisallowTabIndent` or the `Generic.WhiteSpace.DisallowSpaceIndent` sniff.
    - When using this sniff with tab-based standards, please ensure that the `tab-width` is set and either don't set the `$indent` property or set it to the tab-width (or a multiple thereof).
    - The fixer works based on "best guess" and may not always result in the desired indentation. Combine this sniff with the `Generic.WhiteSpace.ScopeIndent` sniff for more precise indentation fixes.
    - The behaviour of the sniff is customizable via the following properties:
        + `indent`: the indent used for the codebase.
        + `ignoreAlignmentBefore`: allows for providing a list of token names for which (preceding) precision alignment should be ignored.
        + `ignoreBlankLines`: whether or not potential trailing whitespace on otherwise blank lines should be examined or ignored.

### Changed

#### Universal

* `Universal.Arrays.DisallowShortArraySyntax`: the sniff will now record metrics about long vs short array usage. [#154]
* `Universal.Arrays.DuplicateArrayKey`: where relevant, the sniff will now make a distinction between keys which will be duplicate in all PHP version and (numeric) keys which will only be a duplicate key in [PHP < 8.0 or PHP >= 8.0][php-rfc-negative_array_index]. [#177], [#178]
    If a [`php_version` configuration option][php_version-config] has been passed to PHPCS, it will be respected by the sniff and only report duplicate keys for the configured PHP version.
* `Universal.ControlStructures.DisallowAlternativeSyntax`: the sniff will now also record a metric when single-line (no body) control structures are encountered. [#158]
* `Universal.ControlStructures.DisallowAlternativeSyntax`: the error message thrown by the sniff is now more descriptive. [#159]
* `Universal.ControlStructures.DisallowAlternativeSyntax`: metrics will no longer be recorded for `elseif` and `else` keywords, but only on the `if` keyword as the type of syntax used has to be the same for the whole "chain". [#161]
* `Universal.Lists.DisallowLongListSyntax`: the sniff will no longer record (incomplete) metrics about long vs short list usage. [#155]
* `Universal.Lists.DisallowShortListSyntax`: the sniff will now record (complete) metrics about long vs short list usage. [#155]
* `Universal.OOStructures.AlphabeticExtendsImplements`: documented support for `enum ... implements`. [#150]
* `Universal.UseStatements.DisallowUseClass`: updated error message and metric name to take PHP 8.1 `enum`s into account. [#149]
* `Universal.UseStatements.NoLeadingBackslash`: the sniff will now also flag and auto-fix leading backslashes in group use statements. [#167]

#### Other

* Updated the sniffs for compatibility with PHPCSUtils 1.0.0-alpha4. [#134]
* Updated the sniffs to correctly handle PHP 8.0/8.1/8.2 features whenever relevant.
* Readme: Updated installation instructions for compatibility with Composer 2.2+. [#101]
* Composer: The minimum `PHP_CodeSniffer` requirement has been updated to `^3.7.1` (was `^3.3.1`). [#115], [#130]
* Composer: The package will now identify itself as a static analysis tool. Thanks [@GaryJones]! [#126]
* All non-`abstract` classes in this package are now `final`. [#121]
* All XML documentation now has a schema annotation. [#128]
* Various housekeeping.

### Fixed

The upgrade to PHPCSUtils 1.0.0-alpha4 took care of a number of bugs, which potentially could have affected sniffs in this package.

#### NormalizedArrays

* `NormalizedArrays.Arrays.ArrayBraceSpacing`: the sniff now allows for trailing comments after the array opener in multi-line arrays. [#118]
* `NormalizedArrays.Arrays.ArrayBraceSpacing`: trailing comments at the end of an array, but before the closer, in multi-line arrays will no longer confuse the sniff. [#135]
* `NormalizedArrays.Arrays.CommaAfterLast`: the fixer will now recognize PHP 7.3+ flexible heredoc/nowdocs and in that case, will add the comma on the same line as the heredoc/nowdoc closer. [#144]

#### Universal

* `Universal.Arrays.DisallowShortArraySyntax`: nested short arrays in short lists will now be detected and fixed correctly. [#153]
* `Universal.ControlStructures.DisallowAlternativeSyntax`: the sniff will no longer bow out indiscriminately when the `allowWithInlineHTML` property is set to `true`. [#90], [#161]
* `Universal.ControlStructures.DisallowAlternativeSyntax`: when alternative control structure syntax is allowed in combination with inline HTML (`allowWithInlineHTML` property set to `true`), inline HTML in functions declared within the control structure body will no longer be taken into account for determining whether or not the control structure contains inline HTML. [#160]
* `Universal.Lists.DisallowShortListSyntax`: the sniff will work around a tokenizer bug in PHPCS 3.7.1, which previously could lead to false negatives. [#151].
* `Universal.Lists.DisallowShortListSyntax`: nested short lists in short arrays will now be detected and fixed correctly. [#152]
* `Universal.Operators.DisallowStandalonePostIncrementDecrement`: the sniff will now correctly recognize stand-alone statements which end on a PHP close tag. [#176]

[#72]:  https://github.com/PHPCSStandards/PHPCSExtra/pull/72
[#76]:  https://github.com/PHPCSStandards/PHPCSExtra/pull/76
[#80]:  https://github.com/PHPCSStandards/PHPCSExtra/pull/80
[#81]:  https://github.com/PHPCSStandards/PHPCSExtra/pull/81
[#85]:  https://github.com/PHPCSStandards/PHPCSExtra/pull/85
[#89]:  https://github.com/PHPCSStandards/PHPCSExtra/pull/89
[#90]:  https://github.com/PHPCSStandards/PHPCSExtra/pull/90
[#95]:  https://github.com/PHPCSStandards/PHPCSExtra/pull/95
[#101]: https://github.com/PHPCSStandards/PHPCSExtra/pull/101
[#106]: https://github.com/PHPCSStandards/PHPCSExtra/pull/106
[#107]: https://github.com/PHPCSStandards/PHPCSExtra/pull/107
[#108]: https://github.com/PHPCSStandards/PHPCSExtra/pull/108
[#109]: https://github.com/PHPCSStandards/PHPCSExtra/pull/109
[#110]: https://github.com/PHPCSStandards/PHPCSExtra/pull/110
[#114]: https://github.com/PHPCSStandards/PHPCSExtra/pull/114
[#115]: https://github.com/PHPCSStandards/PHPCSExtra/pull/115
[#116]: https://github.com/PHPCSStandards/PHPCSExtra/pull/116
[#117]: https://github.com/PHPCSStandards/PHPCSExtra/pull/117
[#118]: https://github.com/PHPCSStandards/PHPCSExtra/pull/118
[#119]: https://github.com/PHPCSStandards/PHPCSExtra/pull/119
[#120]: https://github.com/PHPCSStandards/PHPCSExtra/pull/120
[#121]: https://github.com/PHPCSStandards/PHPCSExtra/pull/121
[#122]: https://github.com/PHPCSStandards/PHPCSExtra/pull/122
[#123]: https://github.com/PHPCSStandards/PHPCSExtra/pull/123
[#124]: https://github.com/PHPCSStandards/PHPCSExtra/pull/124
[#126]: https://github.com/PHPCSStandards/PHPCSExtra/pull/126
[#128]: https://github.com/PHPCSStandards/PHPCSExtra/pull/128
[#130]: https://github.com/PHPCSStandards/PHPCSExtra/pull/130
[#134]: https://github.com/PHPCSStandards/PHPCSExtra/pull/134
[#135]: https://github.com/PHPCSStandards/PHPCSExtra/pull/135
[#137]: https://github.com/PHPCSStandards/PHPCSExtra/pull/137
[#140]: https://github.com/PHPCSStandards/PHPCSExtra/pull/140
[#142]: https://github.com/PHPCSStandards/PHPCSExtra/pull/142
[#143]: https://github.com/PHPCSStandards/PHPCSExtra/pull/143
[#144]: https://github.com/PHPCSStandards/PHPCSExtra/pull/144
[#146]: https://github.com/PHPCSStandards/PHPCSExtra/pull/146
[#147]: https://github.com/PHPCSStandards/PHPCSExtra/pull/147
[#148]: https://github.com/PHPCSStandards/PHPCSExtra/pull/148
[#149]: https://github.com/PHPCSStandards/PHPCSExtra/pull/149
[#150]: https://github.com/PHPCSStandards/PHPCSExtra/pull/150
[#151]: https://github.com/PHPCSStandards/PHPCSExtra/pull/151
[#152]: https://github.com/PHPCSStandards/PHPCSExtra/pull/152
[#153]: https://github.com/PHPCSStandards/PHPCSExtra/pull/153
[#154]: https://github.com/PHPCSStandards/PHPCSExtra/pull/154
[#155]: https://github.com/PHPCSStandards/PHPCSExtra/pull/155
[#158]: https://github.com/PHPCSStandards/PHPCSExtra/pull/158
[#159]: https://github.com/PHPCSStandards/PHPCSExtra/pull/159
[#160]: https://github.com/PHPCSStandards/PHPCSExtra/pull/160
[#161]: https://github.com/PHPCSStandards/PHPCSExtra/pull/161
[#162]: https://github.com/PHPCSStandards/PHPCSExtra/pull/162
[#163]: https://github.com/PHPCSStandards/PHPCSExtra/pull/163
[#164]: https://github.com/PHPCSStandards/PHPCSExtra/pull/164
[#165]: https://github.com/PHPCSStandards/PHPCSExtra/pull/165
[#166]: https://github.com/PHPCSStandards/PHPCSExtra/pull/166
[#167]: https://github.com/PHPCSStandards/PHPCSExtra/pull/167
[#168]: https://github.com/PHPCSStandards/PHPCSExtra/pull/168
[#169]: https://github.com/PHPCSStandards/PHPCSExtra/pull/169
[#170]: https://github.com/PHPCSStandards/PHPCSExtra/pull/170
[#171]: https://github.com/PHPCSStandards/PHPCSExtra/pull/171
[#172]: https://github.com/PHPCSStandards/PHPCSExtra/pull/172
[#173]: https://github.com/PHPCSStandards/PHPCSExtra/pull/173
[#175]: https://github.com/PHPCSStandards/PHPCSExtra/pull/175
[#176]: https://github.com/PHPCSStandards/PHPCSExtra/pull/176
[#177]: https://github.com/PHPCSStandards/PHPCSExtra/pull/177
[#178]: https://github.com/PHPCSStandards/PHPCSExtra/pull/178
[#180]: https://github.com/PHPCSStandards/PHPCSExtra/pull/180

[php-manual-dirname]:           https://www.php.net/function.dirname
[php-rfc-negative_array_index]: https://wiki.php.net/rfc/negative_array_index
[ESLint "no lonely if"]:        https://eslint.org/docs/latest/rules/no-lonely-if
[PHPCSUtils 1.0.0-alpha4]:      https://github.com/PHPCSStandards/PHPCSUtils/releases/tag/1.0.0-alpha4


## [1.0.0-alpha3] - 2020-06-29

### Added

#### Universal

* :wrench: :books: New `Universal.Arrays.DisallowShortArraySyntax` sniff to disallow short array syntax. [#40]
    In contrast to the PHPCS native `Generic.Arrays.DisallowShortArraySyntax` sniff, this sniff will ignore short list syntax and not cause parse errors when the fixer is used.
* :wrench: :bar_chart: :books: New `Universal.Constants.UppercaseMagicConstants` sniff to enforce that PHP native magic constants are in uppercase. [#64]
* :bar_chart: :books: New `Universal.Namespaces.DisallowDeclarationWithoutName` sniff to disallow namespace declarations without a namespace name. [#50]
* :bar_chart: :books: New `Universal.Operators.DisallowLogicalAndOr` sniff to enforce the use of the boolean `&&` and `||` operators instead of the logical `and`/`or` operators. [#52]
    Note: as the [operator precedence] of the logical operators is significantly lower than the operator precedence of boolean operators, this sniff does not contain an auto-fixer.
* :bar_chart: :books: New `Universal.Operators.DisallowShortTernary` sniff to disallow the use of short ternaries `?:`. [#42]
    While short ternaries are useful when used correctly, the principle of them is often misunderstood and they are more often than not used incorrectly, leading to hard to debug issues and/or PHP warnings/notices.
* :wrench: :bar_chart: :books: New `Universal.Operators.DisallowStandalonePostIncrementDecrement` sniff disallow the use of post-in/decrements in stand-alone statements and discourage the use of multiple increment/decrement operators in a stand-alone statement. [#65]
* :wrench: :bar_chart: :books: New `Universal.Operators.StrictComparisons` sniff to enforce the use of strict comparisons. [#48]
    Warning: the auto-fixer for this sniff _may_ cause bugs in applications and should be used with care! This is considered a _risky_ fixer.
* :wrench: :bar_chart: :books: New `Universal.OOStructures.AlphabeticExtendsImplements` sniff to verify that the names used in a class "implements" statement or an interface "extends" statement are listed in alphabetic order. [#55]
    - This sniff contains a public `orderby` property to determine the sort order to use for the statement.
        If all names used are unqualified, the sort order won't make a difference.
        However, if one or more of the names are partially or fully qualified, the chosen sort order will determine how the sorting between unqualified, partially and fully qualified names is handled.
        The sniff supports two sort order options:
        + _'name'_ : sort by the interface name only (default);
        + _'full'_ : sort by the full name as used in the statement (without leading backslash).
        In both cases, the sorting will be done using natural sort, case-insensitive.
    - The sniff has modular error codes to allow for selective inclusion/exclusion:
        + `ImplementsWrongOrder` - for "class implements" statements.
        + `ImplementsWrongOrderWithComments` - for "class implements" statements interlaced with comments. These will not be auto-fixed.
        + `ExtendsWrongOrder` - for "interface extends" statements.
        + `ExtendsWrongOrderWithComments` - for "interface extends" statements interlaced with comments. These will not be auto-fixed.
    - When fixing, the existing spacing between the names in an `implements`/`extends` statement will not be maintained.
        The fixer will separate each name with a comma and one space.
        If alternative formatting is desired, a sniff which will check and fix the formatting should be added to the ruleset.
* :wrench: :bar_chart: :books: New `Universal.UseStatements.LowercaseFunctionConst` sniff to enforce that `function` and `const` keywords when used in an import `use` statement are always lowercase. [#58]
* :wrench: :bar_chart: :books: New `Universal.UseStatements.NoLeadingBackslash` sniff to verify that a name being imported in an import `use` statement does not start with a leading backslash. [#46]
    Names in import `use` statements should always be fully qualified, so a leading backslash is not needed and it is strongly recommended not to use one.
    This sniff handles all types of import use statements supported by PHP, in contrast to other sniffs for the same in, for instance, the PSR12 or the Slevomat standard, which are incomplete.
* :wrench: :books: New `Universal.WhiteSpace.DisallowInlineTabs` sniff to enforce using spaces for mid-line alignment. [#43]

### Changed

#### Other

* The `master` branch has been renamed to `stable`.
* Composer: The version requirements for the [Composer PHPCS plugin] have been widened to allow for version 0.7.0 which supports Composer 2.0.0. [#62]
* Various housekeeping.

[#40]: https://github.com/PHPCSStandards/PHPCSExtra/pull/40
[#42]: https://github.com/PHPCSStandards/PHPCSExtra/pull/42
[#43]: https://github.com/PHPCSStandards/PHPCSExtra/pull/43
[#46]: https://github.com/PHPCSStandards/PHPCSExtra/pull/46
[#48]: https://github.com/PHPCSStandards/PHPCSExtra/pull/48
[#50]: https://github.com/PHPCSStandards/PHPCSExtra/pull/50
[#52]: https://github.com/PHPCSStandards/PHPCSExtra/pull/52
[#55]: https://github.com/PHPCSStandards/PHPCSExtra/pull/55
[#58]: https://github.com/PHPCSStandards/PHPCSExtra/pull/58
[#62]: https://github.com/PHPCSStandards/PHPCSExtra/pull/62
[#64]: https://github.com/PHPCSStandards/PHPCSExtra/pull/64
[#65]: https://github.com/PHPCSStandards/PHPCSExtra/pull/65

[operator precedence]: https://www.php.net/language.operators.precedence


## [1.0.0-alpha2] - 2020-02-18

### Added

#### Universal

* :wrench: :bar_chart: :books: New `Universal.ControlStructures.DisallowAlternativeSyntax` sniff to disallow using the alternative syntax for control structures. [#23]
    - This sniff contains a `allowWithInlineHTML` property to allow alternative syntax when inline HTML is used within the control structure. In all other cases, the use of the alternative syntax will still be disallowed.
    - The sniff has modular error codes to allow for making exceptions based on specific control structures and/or specific control structures in combination with inline HTML.
* :bar_chart: `Universal.UseStatements.DisallowUseClass/Function/Const`: new, additional metrics about the import source will be shown in the `info` report. [#25]

#### Other

* Readme: installation instructions and sniff list. [#26]

### Changed

#### Universal

* `Universal.Arrays.DuplicateArrayKey`: wording of the error message. [#18]
* `Universal.UseStatements.DisallowUseClass/Function/Const`: the error codes have been made more modular. [#25]
    Each of these sniffs now has four additional error codes:
    <ul>
        <li><code>FoundSameNamespace</code>, <code>FoundSameNamespaceWithAlias</code> for <code>use</code> statements importing from the same namespace;</li>
        <li><code>FoundGlobalNamespace</code>, <code>FoundGlobalNamespaceWithAlias</code> for <code>use</code> statements importing from the global namespace, like import statements for PHP native classes, functions and constants.</li>
    </ul>
    In all other circumstances, the existing error codes <code>FoundWithAlias</code> and <code>FoundWithoutAlias</code> will continue to be used.

#### Other

* Improved formatting of the CLI documentation which can be viewed using `--generator=text`. [#17]
* Various housekeeping.

### Fixed

#### Universal

* `Universal.Arrays.DuplicateArrayKey`: improved handling of parse errors. [#34]
* `Universal.ControlStructures.IfElseDeclaration`: the fixer will now respect tab indentation. [#19]
* `Universal.UseStatements.DisallowUseClass/Function/Const`: the determination of whether a import is aliased in now done in a case-insensitive manner. [#25]
* `Universal.UseStatements.DisallowUseClass/Function/Const`: an import from the global namespace would previously always be seen as non-aliased, even when it was aliased. [#25]
* `Universal.UseStatements.DisallowUseClass/Function/Const`: improved tolerance for `use` import statements with leading backslashes. [#25]

[#17]: https://github.com/PHPCSStandards/PHPCSExtra/pull/17
[#18]: https://github.com/PHPCSStandards/PHPCSExtra/pull/18
[#19]: https://github.com/PHPCSStandards/PHPCSExtra/pull/19
[#23]: https://github.com/PHPCSStandards/PHPCSExtra/pull/23
[#25]: https://github.com/PHPCSStandards/PHPCSExtra/pull/25
[#26]: https://github.com/PHPCSStandards/PHPCSExtra/pull/26
[#34]: https://github.com/PHPCSStandards/PHPCSExtra/pull/34


## 1.0.0-alpha1 - 2020-01-23

Initial alpha release containing:
* A `NormalizedArrays` standard which will contain a full set of sniffs to check the formatting of array declarations.
* A `Universal` standard which will contain a collection of universally applicable sniffs.
    DO NOT INCLUDE THIS AS A STANDARD.
    `Universal`, like the upstream PHPCS `Generic` standard, contains sniffs which contradict each other.
    Include individual sniffs from this standard in a custom project/company ruleset to use them.

This initial alpha release contains the following sniffs:

### NormalizedArrays

* :wrench: :bar_chart: :books: `NormalizedArrays.Arrays.ArrayBraceSpacing`: enforce consistent spacing for the open/close braces of array declarations.
    The sniff allows for having different settings for:
    - Space between the array keyword and the open parenthesis for long arrays via the `keywordSpacing` property.
        Accepted values: (int) number of spaces or `false` to turn this check off. Defaults to `0` spaces.
    - Spaces on the inside of the braces for empty arrays via the `spacesWhenEmpty` property.
        Accepted values: (string) `newline`, (int) number of spaces or `false` to turn this check off. Defaults to `0` spaces.
    - Spaces on the inside of the braces for single-line arrays via the `spacesSingleLine` property;
        Accepted values: (int) number of spaces or `false` to turn this check off. Defaults to `0` spaces.
    - Spaces on the inside of the braces for multi-line arrays via the `spacesMultiLine` property.
        Accepted values: (string) `newline`, (int) number of spaces or `false` to turn this check off. Defaults to `newline`.
    Note: if any of the above properties are set to `newline`, it is recommended to also include an array indentation sniff. This sniff will not handle the indentation.
* :wrench: :bar_chart: :books: `NormalizedArrays.Arrays.CommaAfterLast`: enforce/forbid a comma after the last item in an array declaration.
    By default, this sniff will:
    <ul>
        <li>forbid a comma after the last array item for single-line arrays.</li>
        <li>enforce a comma after the last array item for multi-line arrays.</li>
    </ul>
    This can be changed for each type or array individually by setting the <code>singleLine</code> or <code>multiLine</code> properties in a custom ruleset.
    The valid values are: <code>enforce</code>, <code>forbid</code> or <code>skip</code> to not check the comma after the last array item for a particular type of array.

### Universal

* :books: `Universal.Arrays.DuplicateArrayKey`: detects duplicate array keys in array declarations.
* :books: `Universal.Arrays.MixedArrayKeyTypes`: best practice sniff: don't use a mix of integer and numeric keys for array items.
* :books: `Universal.Arrays.MixedKeyedUnkeyedArray`: best practice sniff: don't use a mix of keyed and unkeyed array items.
* :wrench: :bar_chart: :books: `Universal.ControlStructures.IfElseDeclaration`: verify that else(if) statements with braces are on a new line.
* :wrench: :bar_chart: :books: `Universal.Lists.DisallowLongListSyntax`: disallow the use of long `list`s.
* :wrench: :bar_chart: :books: `Universal.Lists.DisallowShortListSyntax`: disallow the use of short lists.
* :bar_chart: :books: `Universal.Namespaces.DisallowCurlyBraceSyntax`: disallow the use of the alternative namespace declaration syntax using curly braces.
* :bar_chart: :books: `Universal.Namespaces.EnforceCurlyBraceSyntax`: enforce the use of the alternative namespace syntax using curly braces.
* :books: `Universal.Namespaces.OneDeclarationPerFile`: disallow the use of multiple namespaces within a file.
* :bar_chart: :books: `Universal.UseStatements.DisallowUseClass`: forbid using import use statements for classes/traits/interfaces.
    Individual sub-types can be allowed by excluding specific error codes.
* :bar_chart: :books: `Universal.UseStatements.DisallowUseConst`: forbid using import use statements for constants.
    Individual sub-types can be allowed by excluding specific error codes.
* :bar_chart: :books: `Universal.UseStatements.DisallowUseFunction`: forbid using import use statements for functions.
    Individual sub-types can be allowed by excluding specific error codes.

[Composer PHPCS plugin]: https://github.com/PHPCSStandards/composer-installer
[php_version-config]:    https://github.com/PHPCSStandards/PHP_CodeSniffer/wiki/Configuration-Options#setting-the-php-version

[Unreleased]: https://github.com/PHPCSStandards/PHPCSExtra/compare/stable...HEAD
[1.5.0]: https://github.com/PHPCSStandards/PHPCSExtra/compare/1.4.2...1.5.0
[1.4.2]: https://github.com/PHPCSStandards/PHPCSExtra/compare/1.4.1...1.4.2
[1.4.1]: https://github.com/PHPCSStandards/PHPCSExtra/compare/1.4.0...1.4.1
[1.4.0]: https://github.com/PHPCSStandards/PHPCSExtra/compare/1.3.1...1.4.0
[1.3.1]: https://github.com/PHPCSStandards/PHPCSExtra/compare/1.3.0...1.3.1
[1.3.0]: https://github.com/PHPCSStandards/PHPCSExtra/compare/1.2.1...1.3.0
[1.2.1]: https://github.com/PHPCSStandards/PHPCSExtra/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/PHPCSStandards/PHPCSExtra/compare/1.1.2...1.2.0
[1.1.2]: https://github.com/PHPCSStandards/PHPCSExtra/compare/1.1.1...1.1.2
[1.1.1]: https://github.com/PHPCSStandards/PHPCSExtra/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/PHPCSStandards/PHPCSExtra/compare/1.0.4...1.1.0
[1.0.4]: https://github.com/PHPCSStandards/PHPCSExtra/compare/1.0.3...1.0.4
[1.0.3]: https://github.com/PHPCSStandards/PHPCSExtra/compare/1.0.2...1.0.3
[1.0.2]: https://github.com/PHPCSStandards/PHPCSExtra/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/PHPCSStandards/PHPCSExtra/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/PHPCSStandards/PHPCSExtra/compare/1.0.0-rc1...1.0.0
[1.0.0-RC1]: https://github.com/PHPCSStandards/PHPCSExtra/compare/1.0.0-alpha3...1.0.0-rc1
[1.0.0-alpha3]: https://github.com/PHPCSStandards/PHPCSExtra/compare/1.0.0-alpha2...1.0.0-alpha3
[1.0.0-alpha2]: https://github.com/PHPCSStandards/PHPCSExtra/compare/1.0.0-alpha1...1.0.0-alpha2

[@anomiex]:      https://github.com/anomiex
[@derickr]:      https://github.com/derickr
[@diedexx]:      https://github.com/diedexx
[@fredden]:      https://github.com/fredden
[@GaryJones]:    https://github.com/GaryJones
[@rodrigoprimo]: https://github.com/rodrigoprimo
[@stronk7]:      https://github.com/stronk7
[@szepeviktor]:  https://github.com/szepeviktor
[@westonruter]:  https://github.com/westonruter
