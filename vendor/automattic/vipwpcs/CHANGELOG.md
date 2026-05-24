# Change Log for VIP Coding Standards

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.1] - 2024-05-14

Props: @GaryJones, @jrnfl, @terriann, @rebeccahum

Increases requirements for PHPCS from 3.7.2 to 3.9.2 for improved PHP 8.2 and PHP 8.3 support. Please ensure you run `composer update automattic/vipwpcs --with-dependencies` to benefit from this.

### Removed
- Functions/RestrictedFunctions:
    * [#812](https://github.com/Automattic/VIP-Coding-Standards/pull/812): Remove restricting `term_exists()`.
    * [#814](https://github.com/Automattic/VIP-Coding-Standards/pull/814): Remove restricting `get_page_by_title()`.
    * [#817](https://github.com/Automattic/VIP-Coding-Standards/pull/817): Remove restricting `get_page_by_path()`.

### Changed
- [#799](https://github.com/Automattic/VIP-Coding-Standards/pull/799): Classes/DeclarationCompatibility: Sync signature definitions with WP Core.

### Maintenance
- [#797](https://github.com/Automattic/VIP-Coding-Standards/pull/797): GH: Add initial dependabot config file.
- GH Actions:
    * [#798](https://github.com/Automattic/VIP-Coding-Standards/pull/798): Bump actions/checkout to 4 from 3.
    * [#802](https://github.com/Automattic/VIP-Coding-Standards/pull/802): Minor tweaks of updating an incorrect URL and adding names to steps.
    * [#803](https://github.com/Automattic/VIP-Coding-Standards/pull/802): Builds against PHP 8.3 can no longer fail, but 8.4 can.
    * [#818](https://github.com/Automattic/VIP-Coding-Standards/pull/818): Add workaround for intermittent `apt-get update` issue.
- Composer:
    * [#805](https://github.com/Automattic/VIP-Coding-Standards/pull/805): Raise minimum version for better PHP 8.2 support.
    * [#805](https://github.com/Automattic/VIP-Coding-Standards/pull/805): Up the minimum PHPCSExtra version to 1.2.1.
    * [#821](https://github.com/Automattic/VIP-Coding-Standards/pull/821): Up the minimum PHPCSUtils version to 1.0.11.
    * [#821](https://github.com/Automattic/VIP-Coding-Standards/pull/821): Up the minimum PHPCS version to 3.9.2.
    * [#821](https://github.com/Automattic/VIP-Coding-Standards/pull/821): Up the minimum WPCS version to 3.1.0.
    * [#821](https://github.com/Automattic/VIP-Coding-Standards/pull/821): Up the minimum PHPCSVariableAnalysis version to 2.11.18.
- Docs:
    * [#805](https://github.com/Automattic/VIP-Coding-Standards/pull/805): Update references to PHPCS under PHPCSStandards, as squizlabs version is abandoned.
    * [#808](https://github.com/Automattic/VIP-Coding-Standards/pull/808): Update Composer installation instructions.
    * [#811](https://github.com/Automattic/VIP-Coding-Standards/pull/811): Update old URLs.
- [#809](https://github.com/Automattic/VIP-Coding-Standards/pull/809): Unit tests: Allow for PHPUnit 8 and 9.

## [3.0.0] - 2023-09-05

Props: @GaryJones, @jrfnl

This release requires [WordPressCS 3.0.0](https://github.com/WordPress/WordPress-Coding-Standards/releases/tag/3.0.0). It is not compatible with WordPressCS 2.x. Users should read the [WordPressCS 3.0 upgrade guide for end-users](https://github.com/WordPress/WordPress-Coding-Standards/wiki/Upgrade-Guide-to-WordPressCS-3.0.0-for-ruleset-maintainers).

Increases requirements for PHPCS from 3.7.1 to 3.7.2.

The tagged releases branch is now `main` instead of `master`.

### Added
- [#777](https://github.com/Automattic/VIP-Coding-Standards/pull/777): 3.0: start using PHPCSUtils.
- [#779](https://github.com/Automattic/VIP-Coding-Standards/pull/779): 3.0: support WordPressCS 3.0.

### Changed
- [#780](https://github.com/Automattic/VIP-Coding-Standards/pull/780): Performance/WPQueryParams: defer to the parent sniff.
  - Two error codes changed:
    - `WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn` is now `WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in`.
    - `WordPressVIPMinimum.Performance.WPQueryParams.SuppressFiltersTrue` is now `WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters`.

### Removed
- [#774](https://github.com/Automattic/VIP-Coding-Standards/pull/774): Performance/BatcacheWhitelistedParams: remove the sniff.
- [#775](https://github.com/Automattic/VIP-Coding-Standards/pull/775): Compatibility/Zoninator: remove the sniff.
- [#776](https://github.com/Automattic/VIP-Coding-Standards/pull/776): Variables/VariableAnalysis: remove the sniff.

### Fixed
- [#784](https://github.com/Automattic/VIP-Coding-Standards/pull/784): Performance/WPQueryParams: prevent false positives for `'exclude'` with `get_users()`.
- [#788](https://github.com/Automattic/VIP-Coding-Standards/pull/788): Security/Mustache: prevent false positives on block editor templates.

### Maintenance
- [#778](https://github.com/Automattic/VIP-Coding-Standards/pull/778): CS: improve use statements.
- [#781](https://github.com/Automattic/VIP-Coding-Standards/pull/781): Performance/NoPaging: add extra tests.
- [#782](https://github.com/Automattic/VIP-Coding-Standards/pull/782): GH Actions: minor tweaks to the composer options used.
- [#783](https://github.com/Automattic/VIP-Coding-Standards/pull/783): Hooks/AlwaysReturnInFilter: remove redundant condition.
- [#785](https://github.com/Automattic/VIP-Coding-Standards/pull/785): Docs: remove redundant `@package` tags.
- [#786](https://github.com/Automattic/VIP-Coding-Standards/pull/786): Add PHPStan to QA checks.
- [#787](https://github.com/Automattic/VIP-Coding-Standards/pull/787): GH Actions: tweak the way the PHPCS/WPCS versions are set.
- [#789](https://github.com/Automattic/VIP-Coding-Standards/pull/789): Updates related to branch rename from `master` to `main`.
- [#790](https://github.com/Automattic/VIP-Coding-Standards/pull/790): PHPUnit: Use 7.5 schema.
- [#791](https://github.com/Automattic/VIP-Coding-Standards/pull/791): Docs: Update `CONTRIBUTING.md`.


## [2.3.4] - 2023-07-05

Props: kshaner, GaryJones, jrfnl, yolih

Increases requirements for PHPCS from 3.5.5+ to 3.7.1.

### Fixed
- [#709](https://github.com/Automattic/VIP-Coding-Standards/pull/709): Add `get_theme_file_path()` to list of allowed include functions.
- [#762](https://github.com/Automattic/VIP-Coding-Standards/pull/762): IncludingFile: allow for more path-returning functions.
- [#748](https://github.com/Automattic/VIP-Coding-Standards/pull/748): ProperEscapingFunction: Fix short tag detection.
- [#760](https://github.com/Automattic/VIP-Coding-Standards/pull/760): RestrictedFunctions: remove reference to function which doesn't exist.

### Changed
- [#768](https://github.com/Automattic/VIP-Coding-Standards/pull/768): DeclarationCompatibility: performance improvement.

- Rulesets:
    * [#763](https://github.com/Automattic/VIP-Coding-Standards/pull/763): Move VariableAnalysis configuration from Go to Minimum.
    * [#765](https://github.com/Automattic/VIP-Coding-Standards/pull/765): Fix the names.
- Composer:
    * [#742](https://github.com/Automattic/VIP-Coding-Standards/pull/742): Up the minimum PHPCS version to 3.7.1.
    * [#764](https://github.com/Automattic/VIP-Coding-Standards/pull/764): Update VariableAnalysis dependency to 2.11.17.
    * [#738](https://github.com/Automattic/VIP-Coding-Standards/pull/738): Allow for the 1.0.0 version of the Composer PHPCS plugin.
    * [#721](https://github.com/Automattic/VIP-Coding-Standards/pull/721): Update composer.json keywords.
    * [#714](https://github.com/Automattic/VIP-Coding-Standards/pull/714): Update PHP Parallel Lint and Console Highlighter.
    * [#741](https://github.com/Automattic/VIP-Coding-Standards/pull/741): Update script names.
    * [#747](https://github.com/Automattic/VIP-Coding-Standards/pull/747): Fix script references.
    * [#708](https://github.com/Automattic/VIP-Coding-Standards/pull/708): Update references to the Composer plugin.
- Tests:
    * [#735](https://github.com/Automattic/VIP-Coding-Standards/pull/735): Unit tests: Support PHP >= 8.1.
    * [#746](https://github.com/Automattic/VIP-Coding-Standards/pull/746): Fix checks for PHP 8.1 and above.
    * [#737](https://github.com/Automattic/VIP-Coding-Standards/pull/737): AdminBarRemovalUnitTest: Ensure final reset is read.
- Coding Standards
    * [#733](https://github.com/Automattic/VIP-Coding-Standards/pull/733): Fix coding standards of VIPCS sniffs.
    * [#756](https://github.com/Automattic/VIP-Coding-Standards/pull/756): Remove extra line at end of classes.
    * [#758](https://github.com/Automattic/VIP-Coding-Standards/pull/758): Simplifications of sniffs extending the WPCS AbstractArrayAssignmentRestrictionsSniff.
    * [#761](https://github.com/Automattic/VIP-Coding-Standards/pull/761): RegexpCompare: remove redundant condition.
    * [#771](https://github.com/Automattic/VIP-Coding-Standards/pull/771): QA: fix condition order.
- CI:
    * [#705](https://github.com/Automattic/VIP-Coding-Standards/pull/705): Various updates.
    * [#750](https://github.com/Automattic/VIP-Coding-Standards/pull/750): Test Higher PHP versions.
    * [#724](https://github.com/Automattic/VIP-Coding-Standards/pull/724): Fix use of deprecated `set-output`.
    * [#725](https://github.com/Automattic/VIP-Coding-Standards/pull/725): Update the xmllint-problem-matcher.
    * [#726](https://github.com/Automattic/VIP-Coding-Standards/pull/726): Various tweaks.
    * [#728](https://github.com/Automattic/VIP-Coding-Standards/pull/728): Bust the cache semi-regularly.
    * [#711](https://github.com/Automattic/VIP-Coding-Standards/pull/711): Version update for various predefined actions.
    * [#712](https://github.com/Automattic/VIP-Coding-Standards/pull/712): Fix build failure.
    * [#755](https://github.com/Automattic/VIP-Coding-Standards/pull/755): Validate the PHPCS installed standards.
    * [#757](https://github.com/Automattic/VIP-Coding-Standards/pull/757): Test and Quicktest tweaks.
    * [#767](https://github.com/Automattic/VIP-Coding-Standards/pull/767): Minor simplifications.
    * [#769](https://github.com/Automattic/VIP-Coding-Standards/pull/769): .gitattributes: readability improvement.
- Docs:
    * [#722](https://github.com/Automattic/VIP-Coding-Standards/pull/722): Updated Docs link for `ORDER BY RAND()`.
    * [#707](https://github.com/Automattic/VIP-Coding-Standards/pull/707): README: update requirements listing.
    * [#706](https://github.com/Automattic/VIP-Coding-Standards/pull/706): README: update for Composer 2.2.
    * [#766](https://github.com/Automattic/VIP-Coding-Standards/pull/766): Various minor doc fixes.
    * [#759](https://github.com/Automattic/VIP-Coding-Standards/pull/759): Bug template: make version table more comprehensive.
    * [#770](https://github.com/Automattic/VIP-Coding-Standards/pull/770): Docs: various tag improvements.

### Deprecated
* [#612](https://github.com/Automattic/VIP-Coding-Standards/pull/612): The `WordPressVIPMinimum.Compatibility.Zoninator` sniff is (soft) deprecated and will be removed in the 3.0.0 release.
* [#613](https://github.com/Automattic/VIP-Coding-Standards/pull/613): The `WordPressVIPMinimum.Performance.BatcacheWhitelistedParams` sniff is (soft) deprecated and will be removed in the 3.0.0 release.


## [2.3.3] - 2021-09-29

Props: gudmdharalds, jrfnl, BrookeDot, rebeccahum

## Changed
- [#690](https://github.com/Automattic/VIP-Coding-Standards/pull/690): Ruleset: do not flag undefined variables in file scope or unused variables before require statement.
- [#691](https://github.com/Automattic/VIP-Coding-Standards/pull/691): Composer: use VariableAnalysis 2.11.1.
- [#694](https://github.com/Automattic/VIP-Coding-Standards/pull/694): PHPCS: enable caching for quicker scanning.
- [#697](https://github.com/Automattic/VIP-Coding-Standards/pull/697): ProperEscapingFunction: upgrade htmlAttrNotByEscHTML to default severity level.

## Removed
- [#692](https://github.com/Automattic/VIP-Coding-Standards/pull/692): RestrictedFunctions: remove dbDelta group.

## [2.3.2] - 2021-04-28

Props: jrfnl

### Fixed
- [#681](https://github.com/Automattic/VIP-Coding-Standards/pull/681): ProperEscapingFunction: improve attribute matching accuracy for notAttrEscAttr.

## [2.3.1] - 2021-04-23

Props: jrfnl

### Fixed
- [#668](https://github.com/Automattic/VIP-Coding-Standards/pull/668): ProperEscapingFunction: fix overreach of comma usage in non-echo expressions for notAttrEscAttr.
- [#670](https://github.com/Automattic/VIP-Coding-Standards/pull/670): ProperEscapingFunction: improve "action" match precision for hrefSrcEscUrl.

### Deprecated
- [#670](https://github.com/Automattic/VIP-Coding-Standards/pull/670): ProperEscapingFunction: private properties `$url_attrs` and `$attr_endings` are deprecated along with the public methods `is_html_attr()` and `attr_expects_url()`.

## [2.3.0] - 2021-04-19

Props: jrfnl, rebeccahum, kevinfodness, GaryJones.

** There is a minor breaking change in the ProperEscapingFunction sniff from PR [#624](https://github.com/Automattic/VIP-Coding-Standards/pull/624). The `escaping_function` property can no longer be overruled via custom rulesets. Please remove any usages of the property in custom rulesets.

** Composer now requires the [phpcodesniffer-composer-installer](https://github.com/PHPCSStandards/composer-installer) plugin per [#583](https://github.com/Automattic/VIP-Coding-Standards/pull/583). Note: If you either include it in the "require-dev" of your `composer.json`, use another Composer PHPCS plugin, or run bash commands to register PHPCS standards, please remove it from those sources to prevent interferences or version constraint conflicts.

### Added
- [#581](https://github.com/Automattic/VIP-Coding-Standards/pull/581): AlwaysReturnInFilter: flag abstract methods for manual inspection.
- [#583](https://github.com/Automattic/VIP-Coding-Standards/pull/583): Composer: require phpcs-composer-installer plugin.
- [#586](https://github.com/Automattic/VIP-Coding-Standards/pull/586): IncludingNonPHPFile: recognition of .phar file extensions.
- [#589](https://github.com/Automattic/VIP-Coding-Standards/pull/589): WPQueryParams: flags 'exclude' array key.
- [#595](https://github.com/Automattic/VIP-Coding-Standards/pull/595): Underscorejs: checks for additional print syntaxes and now throws an additional error for each occurrence of unescaped notation.
- [#624](https://github.com/Automattic/VIP-Coding-Standards/pull/624): ProperEscapingFunction: account for additional escaping functions and check for `esc_attr()` usage in non-HTML attributes.
- [#638](https://github.com/Automattic/VIP-Coding-Standards/pull/638): IncludingFile: new public property `$allowedKeywords` for allowing custom partial keywords in constants to reduce false positives.

### Changed
- [#586](https://github.com/Automattic/VIP-Coding-Standards/pull/586): IncludingNonPHPFile: various performance improvements.
- [#587](https://github.com/Automattic/VIP-Coding-Standards/pull/587): LowExpiryCacheTime: new warning added for manual inspection along with various improvements.
- [#592](https://github.com/Automattic/VIP-Coding-Standards/pull/592): DynamicCalls: various improvements.
- [#595](https://github.com/Automattic/VIP-Coding-Standards/pull/595): Underscorejs: various improvements.
- [#618](https://github.com/Automattic/VIP-Coding-Standards/pull/618): RestrictedFunctions: upgrade setcookie() to error at sniff level and remove Batcache references from messaging.
- [#620](https://github.com/Automattic/VIP-Coding-Standards/pull/620): Ruleset: silence UnusedVariable from VariableAnalysis to reduce noise.
- [#630](https://github.com/Automattic/VIP-Coding-Standards/pull/630): VariableAnalysis: fix incompatibility for VariableAnalysis standard with previously deprecated native VIPCS sniff.
- [#639](https://github.com/Automattic/VIP-Coding-Standards/pull/639): RestrictedFunctions: remove site_option group.
- [#644](https://github.com/Automattic/VIP-Coding-Standards/pull/644): RestrictedFunctions: remove wp_cache_get_multi group.
- [#645](https://github.com/Automattic/VIP-Coding-Standards/pull/645): Ruleset: silence WordPress.WP.AlternativeFunctions.file_system_read_readfile.
- [#646](https://github.com/Automattic/VIP-Coding-Standards/pull/646): Ruleset: silence WordPress.WP.AlternativeFunctions.file_system_read_fclose.
- [#647](https://github.com/Automattic/VIP-Coding-Standards/pull/647): RestrictedFunctions: remove get_super_admins group.
- [#649](https://github.com/Automattic/VIP-Coding-Standards/pull/649): RestrictedFunctions: downgrade switch_to_blog() to warning and change messaging.
- [#652](https://github.com/Automattic/VIP-Coding-Standards/pull/652): RestrictedFunctions/RestrictedVariables: remove usermeta related errors.

### Fixed
- [#444](https://github.com/Automattic/VIP-Coding-Standards/pull/444): ConstantString: only error when a plain constant is passed as constant name parameter.
- [#581](https://github.com/Automattic/VIP-Coding-Standards/pull/581): AlwaysReturnInFilter: fix runtime failure on abstract methods.
- [#584](https://github.com/Automattic/VIP-Coding-Standards/pull/584): Performance: more selective sniffing for efficiency.
- [#586](https://github.com/Automattic/VIP-Coding-Standards/pull/586): IncludingNonPHPFile: various bug fixes such as recognition of interpolated strings and case insensitivity in file extensions. 
- [#587](https://github.com/Automattic/VIP-Coding-Standards/pull/587): LowExpiryCacheTime: allow arithmetic operators, simple floats, numerical strings, zeroes and parentheses in calculations, and FQN time constants.
- [#592](https://github.com/Automattic/VIP-Coding-Standards/pull/592): DynamicCalls: ignore comments, allow double quotes and remove potential memory leak.
- [#595](https://github.com/Automattic/VIP-Coding-Standards/pull/595): Underscorejs: fixed false positive for when a variable is `_.escape()`-ed.
- [#624](https://github.com/Automattic/VIP-Coding-Standards/pull/624): ProperEscapingFunction: slash escaped quotes and non-quoted strings in HTML attributes are now parsed as expected.

### Removed
- [#624](https://github.com/Automattic/VIP-Coding-Standards/pull/624): ProperEscapingFunction: remove `$escaping_functions` public property.

### Maintenance
- [#582](https://github.com/Automattic/VIP-Coding-Standards/pull/582): CI: re-try composer install on failure.
- [#599](https://github.com/Automattic/VIP-Coding-Standards/pull/599): CI: add build against PHP 8.
- [#606](https://github.com/Automattic/VIP-Coding-Standards/pull/606): Ruleset: remove redundant rule ref.
- [#607](https://github.com/Automattic/VIP-Coding-Standards/pull/607): Ruleset: remove redundant rule ref.
- [#608](https://github.com/Automattic/VIP-Coding-Standards/pull/608): Ruleset: remove duplicate rule ref.
- [#611](https://github.com/Automattic/VIP-Coding-Standards/pull/611): Ruleset: remove redundant notice type declaration.
- [#617](https://github.com/Automattic/VIP-Coding-Standards/pull/617): Ruleset: remove redundant notice type declaration.
- [#619](https://github.com/Automattic/VIP-Coding-Standards/pull/619): Docs: Update links to wpvip.com.
- [#631](https://github.com/Automattic/VIP-Coding-Standards/pull/631): QA: remove unused use statements.
- [#632](https://github.com/Automattic/VIP-Coding-Standards/pull/632): Docs: various minor improvements (typos, alignment and code examples).
- [#633](https://github.com/Automattic/VIP-Coding-Standards/pull/633): CI: switch to GitHub Actions.
- [#635](https://github.com/Automattic/VIP-Coding-Standards/pull/635): Ruleset: remove redundant rule ref.
- [#653](https://github.com/Automattic/VIP-Coding-Standards/pull/653): CI: use parallel linting of PHP files.
- [#655](https://github.com/Automattic/VIP-Coding-Standards/pull/655): QA: remove redundant ignore annotations.
- [#656](https://github.com/Automattic/VIP-Coding-Standards/pull/656): CI: always check that sniffs are feature complete.
- [#657](https://github.com/Automattic/VIP-Coding-Standards/pull/657): CI: add "quicktest" stage for non-PR/merge builds.
- [#658](https://github.com/Automattic/VIP-Coding-Standards/pull/658): Release template: add checkbox for dependency check.

## [2.2.0] - 2020-09-09

Props: GaryJones, jrfnl, rebeccahum.

Technically, there's a breaking change due to the use of the VariableAnalysis package over the previous sniff. If you have `WordPressVIPMinimum.Variables.Variables` references in your PHPCS config file or in inline ignore comments, then these will need to be updated to `VariableAnalysis.CodeAnalysis.VariableAnalysis`.

### Added
- [#494](https://github.com/Automattic/VIP-Coding-Standards/pull/494): `.gitattributes` file.
- [#495](https://github.com/Automattic/VIP-Coding-Standards/pull/495): `CODEOWNERS` file.
- [#450](https://github.com/Automattic/VIP-Coding-Standards/pull/450): [VariableAnalysis](https://github.com/sirbrillig/phpcs-variable-analysis/) package.
- [#560](https://github.com/Automattic/VIP-Coding-Standards/pull/560): Allow checking test code coverage.
- [#579](https://github.com/Automattic/VIP-Coding-Standards/pull/560): Docs: Add comparisons and props to change log for old versions.

### Changed
- [#500](https://github.com/Automattic/VIP-Coding-Standards/pull/500): Travis: change from "trusty" to "xenial".
- [#501](https://github.com/Automattic/VIP-Coding-Standards/pull/501): Move and improve `CONTRIBUTING.md`.
- [#502](https://github.com/Automattic/VIP-Coding-Standards/pull/502): CS Ruleset: minor tweaks.
- [#508](https://github.com/Automattic/VIP-Coding-Standards/pull/508): RulesetTest: don't use the system default version of PHP.
- [#558](https://github.com/Automattic/VIP-Coding-Standards/pull/558): Test bootstrap: various minor tweaks.
- [#571](https://github.com/Automattic/VIP-Coding-Standards/pull/571): CS: change yoda conditions to non-yoda.
- [#573](https://github.com/Automattic/VIP-Coding-Standards/pull/573): Composer: Change minimum stability to stable.

### Fixed
- [#503](https://github.com/Automattic/VIP-Coding-Standards/pull/503): RulesetTest, fix compatibility with Windows.
- [#504](https://github.com/Automattic/VIP-Coding-Standards/pull/504): RulesetTest: fail the build on failing ruleset tests, fix the failing ruleset test, and fix the test script to handle 0 values.
- [#505](https://github.com/Automattic/VIP-Coding-Standards/pull/505): DeclarationCompatibility: fix incorrect signature check for `Walker::walk()`.
- [#509](https://github.com/Automattic/VIP-Coding-Standards/pull/509): RulesetTest: Revert #485 and fix one of the three causes properly.
- [#559](https://github.com/Automattic/VIP-Coding-Standards/pull/559): Variables/RestrictedVariables: fix namespace of unit test file, fix the test.
- [#561](https://github.com/Automattic/VIP-Coding-Standards/pull/561): Functions/RestrictedFunctions: fix false positive on class instantiation.
- [#563](https://github.com/Automattic/VIP-Coding-Standards/pull/563): Hooks/AlwaysReturnInFilter: add support for hook-ins using short arrays.
- [#564](https://github.com/Automattic/VIP-Coding-Standards/pull/564): Hooks/PreGetPosts: add support for hook-ins using short arrays.
- [#565](https://github.com/Automattic/VIP-Coding-Standards/pull/565): PreGetPosts: improve the `isEarlyMainQueryCheck()` method.
- [#566](https://github.com/Automattic/VIP-Coding-Standards/pull/566): RestrictedFunctions: fix false negative - functions in `config_settings` would never match.
- [#569](https://github.com/Automattic/VIP-Coding-Standards/pull/569): RestrictedVariables: don't report on "use" in `isset()`.
- [#575](https://github.com/Automattic/VIP-Coding-Standards/pull/575): ProperEscaping: Fix message for action attribute.
- [#576](https://github.com/Automattic/VIP-Coding-Standards/pull/576): Docs: Update notes for releasing.

### Deprecated
- [#450](https://github.com/Automattic/VIP-Coding-Standards/pull/450): Deprecate Variables/VariableAnalysisSniff. Will be removed in the next major release.

## [2.1.0] - 2020-07-07

Bumps requirements to PHPCS 3.5.5+ and WPCS 2.3.0+.

Props: GaryJones, jenkoian, kevinfodness, rebeccahum.

### Added
- `get_page_by_path()` restricted function warning, to suggest `wpcom_vip_get_page_by_path()` function.
- `stats_get_csv()` restricted function error, since this is a Jetpack-only function.
- Expanded list of HTMLExecutingFunctions to include `after`, `appendTo`, `before`, `insertAfter`, `insertBefore`, `prepend`, `prependTo`, `replaceAll` and `replaceWith`.
- Support PHP 5.4+ (down from 5.6+).
- PHP 8 nightly testing.

### Changed
- Expand message for `wp_remote_get()` usage.
- Downgrade `append()` usage violation from Error to Warning for VIP Go, to be consistent with the other HTMLExecutingFunctions.
- Downgrade AdminBarRemoval sniff from Error to Warning for VIP Go.
- Add `get_parent_theme_file_path()` to safelist of path functions for `WordPressVIPMinimum.Files.IncludingFile` sniff.
- Allow short array syntax and fix tests within the VIPCS own coding standards.
- Update issue templates.

### Fixed
- Use new `WordPress.DateTime.RestrictedFunctions` sniff instead of deprecated `WordPress.WP.TimezoneChange`.
- Fixed warnings and information items in Travis.

### Removed
- `get_super_admins()` restricted function rule for VIP Go.
- `WordPressVIPMinimum.VersionControl.MergeConflict` sniff in favour of `Generic.VersionControl.GitMergeConflict`.

## [2.0.0] - 2019-07-12

This release switches from having WPCS `1.*` as a dependency, to WPCS `2.*`. It is not compatible with WPCS `1.*`.

The sniffs in WPCS `2.*` are more accurate, so you may see new violations there weren't being reported before, and a reduction in violations for false positives.  

Props: GaryJones, hanifn, paulscreiber, rebeccahum, tomjn. 

### Added
- Switch to using WPCS `2.*`.
	- Remove reference to WPCS's `PHPAliases.php`.
	- Remove WPCS `1.*`'s `WordPress.VIP` references from rulesets.
	- Bump PHPCS minimum required version to 3.3.1.
	- Update the WPCS namespace.
	- Update ruleset and ruleset test to account for WPCS 2's switch to `WordPress.PHP.IniSet` sniff.
	- Update ruleset test for WPCS security sniffs.
	- Update `DiscouragedPHPFunctions` group exclusion in `WordPressVIPMinimum` ruleset.
	
### Changed 
- Downgrade use of file operation functions from Error to Warning:
	- `delete`
	- `file_put_contents`
	- `flock`
	- `fputcsv`
	- `fputs`
	- `fwrite`
	- `ftruncate`
	- `is_writable`
	- `is_writeable`
	- `link`
	- `rename`
	- `symlink`
	- `tempnam`
	- `touch`
	- `unlink`
	- `fclose`
	- `fopen`
	- `file_get_contents`
- Simplify Travis config.
- Switch references from `vip.wordpress.com` to `wpvip.com`.
- Documentation updates.
- Switch development to a `git-flow` workflow.

## Fixed
- Fixed CS violations in VIPCS code.

## [1.0.0] - 2019-04-24

This release contains many breaking changes.

It requires PHP `>= 5.6`, PHPCS `3.2.3+`, and WPCS `1.*`. It does not work with WPCS `2.*`.

Props: GaryJones, rebeccahum, whyisjake, WPprodigy.

### Reorganisation and Renaming
The sniffs in VIPCS have been reorganised into different categories, with new sniff names and new violation codes. The changes are detailed in the table below. If you reference any of the old violations in your custom ruleset (to change severity, type, or message), or with `// phpcs:ignore` or `// phpcs:disable`, you will need to updates these references to the new violation codes.

| Original Violation | New Violation |
|--------------------|---------------|
| `WordPressVIPMinimum.Actions.PreGetPostSniff.PreGetPosts` | `WordPressVIPMinimum.Hooks.PreGetPosts.PreGetPosts` |
| `WordPressVIPMinimum.Cache.BatcacheWhitelistedParams.strippedGetParam` | `WordPressVIPMinimum.Performance.BatcacheWhitelistedParams.StrippedGetParam` |
| `WordPressVIPMinimum.Cache.CacheValueOverride.CacheValueOverride` | `WordPressVIPMinimum.Performance.CacheValueOverride.CacheValueOverride` |
| `WordPressVIPMinimum.Cache.LowExpiryCacheTime.LowCacheTime` | `WordPressVIPMinimum.Performance.LowExpiryCacheTime.LowCacheTime` |
| `WordPressVIPMinimum.Classes.DeclarationCompatibility.DeclarationCompatibility` | No change  |
| `WordPressVIPMinimum.Classes.RestrictedExtendClasses.wp_cli_wp_cli_command` | `WordPressVIPMinimum.Classes.RestrictedExtendClasses.wp_cli` |
| `WordPressVIPMinimum.Constants.ConstantsRestrictions.ConstantRestrictions` | `WordPressVIPMinimum.Constants.RestrictedConstants.DefiningRestrictedConstant`<br>`WordPressVIPMinimum.Constants.RestrictedConstants.UsingRestrictedConstant` |
| `WordPressVIPMinimum.Constants.ConstantString.NotCheckingConstantName` | No change |
| `WordPressVIPMinimum.Files.IncludingFile.IncludingFile` | `WordPressVIPMinimum.Files.IncludingFile.UsingVariable`<br>`WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant`<br>`WordPressVIPMinimum.Files.IncludingFile.UsingCustomFunction`<br>`WordPressVIPMinimum.Files.IncludingFile.NotAbsolutePath`<br>`WordPressVIPMinimum.Files.IncludingFile.ExternalURL`<br>`WordPressVIPMinimum.Files.IncludingFile.RestrictedConstant` |
| `WordPressVIPMinimum.Files.IncludingNonPHPFile.IncludingSVGCSSFile` | `WordPressVIPMinimum.Files.IncludingNonPHPFile.IncludingSVGCSSFile` |
| `WordPressVIPMinimum.Files.IncludingNonPHPFile.IncludingNonPHPFile` | `WordPressVIPMinimum.Files.IncludingNonPHPFile.IncludingNonPHPFile` |
| `WordPressVIPMinimum.Filters.AlwaysReturn.voidReturn` | `WordPressVIPMinimum.Hooks.AlwaysReturnInFilter.VoidReturn` |
| `WordPressVIPMinimum.Filters.AlwaysReturn.missingReturnStatement` | `WordPressVIPMinimum.Hooks.AlwaysReturnInFilter.MissingReturnStatement` |
| `WordPressVIPMinimum.Filters.RestrictedHook.UploadMimes` | `WordPressVIPMinimum.Hooks.RestrictedHooks.upload_mimes_upload_mimes` |
| `WordPressVIPMinimum.Filters.RestrictedHook.HighTimeout` | `WordPressVIPMinimum.Hooks.RestrictedHooks.http_request_http_request_args`<br>`WordPressVIPMinimum.Hooks.RestrictedHooks.http_request_http_request_timeout` |
| `WordPressVIPMinimum.Functions.CheckReturnValue.CheckReturnValue` | `WordPressVIPMinimum.Functions.CheckReturnValue.DirectFunctionCall`<br>`WordPressVIPMinimum.Functions.CheckReturnValue.NonCheckedVariable` |
| `WordPressVIPMinimum.Functions.CreateFunction.CreateFunction` | `WordPressVIPMinimum.Functions.RestrictedFunctions.create_function_create_function` |
| `WordPressVIPMinimum.Functions.DynamicCalls.DynamicCalls` | No change |
| `WordPressVIPMinimum.Functions.StripTags.StripTagsOneParameter` | No change |
| `WordPressVIPMinimum.Functions.StripTags.StripTagsTwoParameters` | No change |
| `WordPressVIPMinimum.JS.DangerouslySetInnerHTML.dangerouslySetInnerHTML` | `WordPressVIPMinimum.JS.DangerouslySetInnerHTML.Found` |
| `WordPressVIPMinimum.JS.HTMLExecutingFunctions.html` | No change |
| `WordPressVIPMinimum.JS.HTMLExecutingFunctions.append` | No change |
| `WordPressVIPMinimum.JS.HTMLExecutingFunctions.write` | No change |
| `WordPressVIPMinimum.JS.HTMLExecutingFunctions.writeln` | No change |
| `WordPressVIPMinimum.JS.InnerHTML.innerHTML` | `WordPressVIPMinimum.JS.InnerHTML.Found` |
| `WordPressVIPMinimum.JS.StringConcat.StringConcatNext` | `WordPressVIPMinimum.JS.StringConcat.Found` |
| `WordPressVIPMinimum.JS.StrippingTags.VulnerableTagStripping` | No change |
| `WordPressVIPMinimum.JS.Window.VarAssignment` | No change |
| `WordPressVIPMinimum.JS.Window.location` | No change |
| `WordPressVIPMinimum.JS.Window.name` | No change |
| `WordPressVIPMinimum.JS.Window.status` | No change |
| `WordPressVIPMinimum.Plugins.Zoninator.Zoninator` | `WordPressVIPMinimum.Compatibility.Zoninator.RequiresRESTAPI` |
| `WordPressVIPMinimum.TemplatingEngines.UnescapedOutputMustache.{{{` | `WordPressVIPMinimum.Security.Mustache.OutputNotation` |
| `WordPressVIPMinimum.TemplatingEngines.UnescapedOutputMustache.{{&` | `WordPressVIPMinimum.Security.Mustache.VariableNotation` |
| `WordPressVIPMinimum.TemplatingEngines.UnescapedOutputMustache.delimeterChange` | `WordPressVIPMinimum.Security.Mustache.DelimiterChange` |
| `WordPressVIPMinimum.TemplatingEngines.UnescapedOutputMustache.SafeString` | `WordPressVIPMinimum.Security.Mustache.SafeString` |
| `WordPressVIPMinimum.TemplatingEngines.UnescapedOutputTwig.autoescape false` | `WordPressVIPMinimum.Security.Twig.AutoescapeFalse` |
| `WordPressVIPMinimum.TemplatingEngines.UnescapedOutputTwig.raw` | `WordPressVIPMinimum.Security.Twig.RawFound` |
| `WordPressVIPMinimum.TemplatingEngines.UnescapedOutputUnderscorejs.<%=` | `WordPressVIPMinimum.Security.Underscorejs.OutputNotation` |
| `WordPressVIPMinimum.TemplatingEngines.UnescapedOutputUnderscorejs.interpolate` | `WordPressVIPMinimum.Security.Underscorejs.InterpolateFound` |
| `WordPressVIPMinimum.TemplatingEngines.UnescapedOutputVuejs.v-html` | `WordPressVIPMinimum.Security.Vuejs.Found` |
| `WordPressVIPMinimum.Variables.ServerVariables.BasicAuthentication` | No change |
| `WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders` | No change |
| `WordPressVIPMinimum.Variables.VariableAnalysis.VariableRedeclaration` | No change |
| `WordPressVIPMinimum.Variables.VariableAnalysis.UndefinedVariables` | `WordPressVIPMinimum.Variables.VariableAnalysis.UndefinedVariable` |
| `WordPressVIPMinimum.Variables.VariableAnalysis.$...` | `WordPressVIPMinimum.Variables.VariableAnalysis.SelfInsideClosure`<br>`WordPressVIPMinimum.Variables.VariableAnalysis.SelfOutsideClass`<br>`WordPressVIPMinimum.Variables.VariableAnalysis.StaticInsideClosure`<br>`WordPressVIPMinimum.Variables.VariableAnalysis.StaticOutsideClass` |
| `WordPressVIPMinimum.Variables.VariableAnalysis.UnusedVariable` | No change |
| `WordPressVIPMinimum.VIP.ErrorControl.ErrorControl` | Replaced with `Generic.PHP.NoSilencedErrors` |
| `WordPressVIPMinimum.VIP.EscapingVoidReturnFunctions.escapingVoidReturningFunction` | `WordPressVIPMinimum.Security.EscapingVoidReturnFunctions.Found` |
| `WordPressVIPMinimum.VIP.ExitAfterRedirect.NoExitInConditional` | `WordPressVIPMinimum.Security.ExitAfterRedirect.NoExitInConditional` |
| `WordPressVIPMinimum.VIP.ExitAfterRedirect.NoExit` | `WordPressVIPMinimum.Security.ExitAfterRedirect.NoExit` |
| `WordPressVIPMinimum.VIP.FetchingRemoteData.fileGetContentsUknown` | `WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown` |
| `WordPressVIPMinimum.VIP.FetchingRemoteData.fileGetContentsRemoteFile` | `WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsRemoteFile` |
| `WordPressVIPMinimum.VIP.FlushRewriteRules.FlushRewriteRules` | Replaced with `WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules` and `WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules` |
| `WordPressVIPMinimum.VIP.MergeConflict.HEAD` | `WordPressVIPMinimum.MergeConflict.MergeConflict.Start` |
| `WordPressVIPMinimum.VIP.MergeConflict.DELIMITER` | `WordPressVIPMinimum.MergeConflict.MergeConflict.End`<br>`WordPressVIPMinimum.MergeConflict.MergeConflict.Separator` |
| `WordPressVIPMinimum.VIP.PHPFilterFunctions.MissingThirdParameter` | `WordPressVIPMinimum.Security.PHPFilterFunctions.MissingThirdParameter` |
| `WordPressVIPMinimum.VIP.PHPFilterFunctions.RestrictedFilter` | `WordPressVIPMinimum.Security.PHPFilterFunctions.RestrictedFilter` |
| `WordPressVIPMinimum.VIP.PHPFilterFunctions.MissingSecondParameter` | `WordPressVIPMinimum.Security.PHPFilterFunctions.MissingSecondParameter` |
| `WordPressVIPMinimum.VIP.ProperEscapingFunction.hrefSrcEscUrl` | `WordPressVIPMinimum.Security.ProperEscapingFunction.hrefSrcEscUrl` |
| `WordPressVIPMinimum.VIP.ProperEscapingFunction.htmlAttrNotByEscHTML` | `WordPressVIPMinimum.Security.ProperEscapingFunction.htmlAttrNotByEscHTML` |
| `WordPressVIPMinimum.VIP.RegexpCompare.compare_compare` | `WordPressVIPMinimum.Performance.RegexCompare.compare_compare` |
| `WordPressVIPMinimum.VIP.RegexpCompare.compare_meta_compare` | `WordPressVIPMinimum.Performance.RegexCompare.compare_meta_compare` |
| `WordPressVIPMinimum.VIP.RemoteRequestTimeout.timeout_timeout` | `WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.wp_cache_get_multi.wp_cache_get_multi` | `WordPressVIPMinimum.Functions.RestrictedFunctions.wp_cache_get_multi_wp_cache_get_multi` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.opcache_opcache_reset` | `WordPressVIPMinimum.Functions.RestrictedFunctions.opcache_opcache_reset` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.opcache_opcache_invalidate` | `WordPressVIPMinimum.Functions.RestrictedFunctions.opcache_opcache_invalidate` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.opcache_opcache_compile_file` | `WordPressVIPMinimum.Functions.RestrictedFunctions.opcache_opcache_compile_file` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.config_settings_opcache_is_script_cached` | `WordPressVIPMinimum.Functions.RestrictedFunctions.config_settings_opcache_is_script_cached` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.config_settings_opcache_get_status` | `WordPressVIPMinimum.Functions.RestrictedFunctions.config_settings_opcache_get_status` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.config_settings_opcache_get_configuration` | `WordPressVIPMinimum.Functions.RestrictedFunctions.config_settings_opcache_get_configuration` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.get_super_admins_get_super_admins` | `WordPressVIPMinimum.Functions.RestrictedFunctions.get_super_admins_get_super_admins` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.internal_wpcom_vip_irc` | `WordPressVIPMinimum.Functions.RestrictedFunctions.internal_wpcom_vip_irc` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.rewrite_rules_flush_rewrite_rules` | `WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.attachment_url_to_postid_attachment_url_to_postid` | `WordPressVIPMinimum.Functions.RestrictedFunctions.attachment_url_to_postid_attachment_url_to_postid` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.dbDelta_dbDelta` | `WordPressVIPMinimum.Functions.RestrictedFunctions.dbDelta_dbDelta` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.switch_to_blog_switch_to_blog` | `WordPressVIPMinimum.Functions.RestrictedFunctions.switch_to_blog_switch_to_blog` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.get_page_by_title_get_page_by_title` | `WordPressVIPMinimum.Functions.RestrictedFunctions.get_page_by_title_get_page_by_title` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.url_to_postid_url_to_postid` | `WordPressVIPMinimum.Functions.RestrictedFunctions.url_to_postid_url_to_postid` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.url_to_postid_url_to_post_id` | Removed |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.custom_role_add_role` | `WordPressVIPMinimum.Functions.RestrictedFunctions.custom_role_add_role` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.user_meta_get_user_meta` | `WordPressVIPMinimum.Functions.RestrictedFunctions.user_meta_get_user_meta` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.user_meta_update_user_meta` | `WordPressVIPMinimum.Functions.RestrictedFunctions.user_meta_update_user_meta` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.user_meta_delete_user_meta` | `WordPressVIPMinimum.Functions.RestrictedFunctions.user_meta_delete_user_meta` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.user_meta_add_user_meta` | `WordPressVIPMinimum.Functions.RestrictedFunctions.user_meta_add_user_meta` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.term_exists_term_exists` | `WordPressVIPMinimum.Functions.RestrictedFunctions.term_exists_term_exists` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.count_user_posts_count_user_posts` | `WordPressVIPMinimum.Functions.RestrictedFunctions.count_user_posts_count_user_posts` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.wp_old_slug_redirect_wp_old_slug_redirect` | `WordPressVIPMinimum.Functions.RestrictedFunctions.wp_old_slug_redirect_wp_old_slug_redirect` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.get_adjacent_post_get_adjacent_post` | `WordPressVIPMinimum.Functions.RestrictedFunctions.get_adjacent_post_get_adjacent_post` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.get_adjacent_post_get_previous_post` | `WordPressVIPMinimum.Functions.RestrictedFunctions.get_adjacent_post_get_previous_post` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.get_adjacent_post_get_previous_post_link` | `WordPressVIPMinimum.Functions.RestrictedFunctions.get_adjacent_post_get_previous_post_link` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.get_adjacent_post_get_next_post` | `WordPressVIPMinimum.Functions.RestrictedFunctions.get_adjacent_post_get_next_post` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.get_adjacent_post_get_next_post_link` | `WordPressVIPMinimum.Functions.RestrictedFunctions.get_adjacent_post_get_next_post_link` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.get_intermediate_image_sizes_get_intermediate_image_sizes` | `WordPressVIPMinimum.Functions.RestrictedFunctions.get_intermediate_image_sizes_get_intermediate_image_sizes` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.wp_is_mobile_wp_is_mobile` | `WordPressVIPMinimum.Functions.RestrictedFunctions.wp_is_mobile_wp_is_mobile` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.wp_mail_wp_mail` | `WordPressVIPMinimum.Functions.RestrictedFunctions.wp_mail_wp_mail` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.wp_mail_mail` | `WordPressVIPMinimum.Functions.RestrictedFunctions.wp_mail_mail` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.is_multi_author_is_multi_author` | `WordPressVIPMinimum.Functions.RestrictedFunctions.is_multi_author_is_multi_author` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.advanced_custom_fields_the_sub_field` | `WordPressVIPMinimum.Functions.RestrictedFunctions.advanced_custom_fields_the_sub_field` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.advanced_custom_fields_the_field` | `WordPressVIPMinimum.Functions.RestrictedFunctions.advanced_custom_fields_the_field` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.wp_remote_get_wp_remote_get` | `WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.cookies_setcookie` | `WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.get_posts_get_posts` | `WordPressVIPMinimum.Functions.RestrictedFunctions.get_posts_get_posts` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.get_posts_wp_get_recent_posts` | `WordPressVIPMinimum.Functions.RestrictedFunctions.get_posts_wp_get_recent_posts` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.get_posts_get_children` | `WordPressVIPMinimum.Functions.RestrictedFunctions.get_posts_get_children` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.wpcom_vip_get_term_link_wpcom_vip_get_term_link` | `WordPressVIPMinimum.Functions.RestrictedFunctions.wpcom_vip_get_term_link_wpcom_vip_get_term_link` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.wpcom_vip_get_term_by_wpcom_vip_get_term_by` | `WordPressVIPMinimum.Functions.RestrictedFunctions.wpcom_vip_get_term_by_wpcom_vip_get_term_by` |
| `WordPressVIPMinimum.VIP.RestrictedFunctions.wpcom_vip_get_category_by_slug_wpcom_vip_get_category_by_slug` | `WordPressVIPMinimum.Functions.RestrictedFunctions.wpcom_vip_get_category_by_slug_wpcom_vip_get_category_by_slug` |
| `WordPressVIPMinimum.VIP.Robotstxt.RobotstxtSniff` | `WordPressVIPMinimum.Hooks.RestrictedHooks.robotstxt_do_robotstxt`<br>`WordPressVIPMinimum.Hooks.RestrictedHooks.robotstxt_robots_txt` |
| `WordPressVIPMinimum.VIP.StaticStrreplace.StaticStrreplace` | `WordPressVIPMinimum.Security.StaticStrreplace.StaticStrreplace` |
| `WordPressVIPMinimum.VIP.TaxonomyMetaInOptions.PossibleTermMetaInOptions` | `WordPressVIPMinimum.Performance.TaxonomyMetaInOptions.PossibleTermMetaInOptions` |
| `WordPressVIPMinimum.VIP.WPQueryParams.suppressFiltersTrue` | `WordPressVIPMinimum.Performance.WPQueryParams.SuppressFiltersTrue` |
| `WordPressVIPMinimum.VIP.WPQueryParams.post__not_in` | `WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn` |

### Added
- New violations:
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.chmod_chgrp`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.chmod_chown`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.chmod_chmod`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.chmod_lchgrp`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.chmod_lchown`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.directory_rmdir`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_delete`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_flock`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fputcsv`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fputs`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_ftruncate`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fwrite`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_is_writable`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_is_writeable`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_link`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_rename`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_symlink`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_tempnam`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_touch`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_abort`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_cache_expire`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_cache_limiter`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_commit`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_create_id`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_decode`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_destroy`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_encode`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_gc`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_get_cookie_params`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_id`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_is_registered`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_module_name`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_name`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_regenerate_id`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_register_shutdown`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_register`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_reset`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_save_path`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_set_cookie_params`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_set_save_handler`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_start`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_status`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_unregister`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_unset`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_write_close`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.site_option_add_site_option`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.site_option_delete_site_option`
	- `WordPressVIPMinimum.Functions.RestrictedFunctions.site_option_update_site_option`
	- `WordPressVIPMinimum.Performance.NoPaging.nopaging_nopaging`
	- `WordPressVIPMinimum.Performance.OrderByRand.orderby_orderby`
	- `WordPressVIPMinimum.UserExperience.AdminBarRemoval.HidingDetected`
	- `WordPressVIPMinimum.UserExperience.AdminBarRemoval.RemovalDetected`
	- `WordPressVIPMinimum.Variables.RestrictedVariables.user_meta__wpdb__users`
	- `WordPressVIPMinimum.Variables.RestrictedVariables.user_meta__wpdb__usermeta`
	- `WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE`
	- `WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__HTTP_USER_AGENT__`
	- `WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__REMOTE_ADDR__`
	- `WordPressVIPMinimum.Variables.RestrictedVariables.session___session`
- `WordPress-VIP-Go` ruleset test.
- docs about ruleset tests.
- XSD reference and ruleset validation.
- `phpcodesniffer-composer-installer` plugin.
- Copy `has_html_open_tag()` from WPCS.
- Copy `AbstractVariableRestrictionsSniff` from WPCS.

### Changed
- Switch from PHPCS 2.* class names to PHPCS 3.* namespaced classes.
- Refactor all sniffs to extend `VIPCS\Sniff`, which is an extension of `WordPress\Sniff`.
- Tidied up:
	- unused imports
	- unused local variables
	- unused parameters
	- unused private field
	- duplicate array keys
	- redundant self-assignment
	- assignment in conditions
	- not returning void function calls
	- undefined class fields
	- strict comparisons
	- missing scope keywords
	- parentheses to clarify one specific conditional
	- consolidate multiple `isset()` calls
	- consolidate positive nested `if()`’s
	- difference in case for function calls
	- simplified return statements
	- switched to `__DIR__`
	- switched FQCN to import statements
	- use static property
	- use more performant `strpos()` instead of `substr()`
	- split or remove `else` / `elseif` workflows for lower complexity and more comprehension
	- misuse of `array_push()`
	- misuse of `array_values()`
	- misuse of `in_array()`
	- useless `return`
	- redundant `continue`
	- comments that were naming parameters
	- default assignments of `null` to class properties
	- function parameters that match default arg values
	- redundant parentheses
- Ruleset Test improvements:
	- Move mostly duplicate `PHPCS_Ruleset_Test` classes into new `RulesetTest` class.
	- Refactor new class:
		- Accept a ruleset name in the constructor
		- Change public method from `run()` to `passes()`.
		- Break out logic into smaller private methods to make the logic more self-documenting.
		- Refactor variable names in some methods.
		- Decode JSON into objects, not arrays
		- Fix incorrect reference to local `$expected` to `$this->expected`. Somehow, this was still working regardless.
		- Fix bug where it doesn't catch proper number of errors/warnings on a line basis due to order of operations of incrementating after assignment.
		- Add further documentation.
	- Change ruleset test class usage, including adding the name to the "tests passed!" message.
	- Replaced WPCS whitelisting `// XSS OK` comments in this files with `// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped` comments.
	- Change naming of tests from "integration test" to "ruleset tests", to make it more intuitive exactly what these are (composer script, Travis, bin filename).
- Improve `addError()` and `addWarning()` calls
- Remove `Generic.NamingConventions.ConstructorName.OldStyle` from `WordPress-VIP-Go` ruleset
- Travis: Restrict PHPUnit versions to match PHPCS
- Travis: Use `7.4snapshot` instead of nightly, switch from Trusty to Xenial, remove `sudo: false`.
- `EscapingVoidReturnFunctions`: Fix docs and improve logic
- `AlwaysReturnSniff`: trigger errors instead of warnings, don't give violation for when callback args is passed by reference
- Change `exec()` and `shell_exec()` to be flagged as Error.
- Disallow long array syntax in VIPCS sniff code.
- Remove a `WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition` exclusion in the PHPCS config for VIPCS itself.
- Update docs.

### Fixed
- Bumped PHPCompatibility `testVersion` to match PHP requirement.
- Silence `Generic.PHP.DisallowShortOpenTag.EchoFound` for `WordPress-VIP-Go` ruleset: `<?=` is no longer reported.
- Silence `WordPress.WP.AlternativeFunctions.file_system_read_fwrite` and `WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents` since we have `WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_*`.
- Silence Short Echo tags on `WordPress-VIP-Go`.

## [0.4.0] - 2018-12-19

This release contains breaking changes.

Props: GaryJones, nickdaugherty, rebeccahum, tomjn.

### Added
 - `WordPressVIPMinimum.Cache.LowExpiryCacheTime` sniff.
 - `WordPressVIPMinimum.Classes.RestrictedExtendedClasses` sniff, for `WP_CLI_Command`.
 - `WordPressVIPMinimum.Filters.RestrictedHooks` sniff, for `upload_mimes`, as well as `http_request_timeout` and `http_request_args` filters which change timeouts, as we typically don't recommend anything above 3s.
 - `WordPressVIPMinimum.Functions.StripTags` sniff.
 - `WordPressVIPMinimum.JS.DangerouslySetInnerHTML` sniff.
 - `WordPressVIPMinimum.JS.Window` sniff.
 - `WordPressVIPMinimum.VIP.PHPFilterFunctions` sniff.
 - GitHub issue templates.
 - `opcache_*()` functions to list of restricted functions.
 - ACF templating function to list of restricted functions.
 - `.editorconfig` to repo.
 - `Generic.PHP.Syntax` to `WordPressVIPMinimum` ruleset.

### Changed
 - Allow unused `$e` when catching exceptions.
 - Improved accuracy of `WordPressVIPMinimum.Files.IncludingFile`
 - Refactor `WordPressVIPMinimum.VIP.RestrictedFunctions` sniff.
 - Include documentation links directly in error message for `WordPressVIPMinimum.VIP.WPQueryParams.post__not_in`.
 - Composer: Normalized `composer.json`.
 - Composer: Bump to PHPCompatibility ^9.
 - Change severity of `WordPress.CodeAnalysis.AssignmentInCondition.Found` to 1 instead of removing it.
 - Increases the PHPCS (3.2.3) and PHP (5.6+) minimum versions to supported and known good values.
 - Travis: Remove PHPUnit 6 workaround.
 - Travis: updates the PHPCS referenced in the Travis file, and remove the PHP 5.5 and 5.4 checks.
 - Travis: Switch to using build stages.
 - Travis: Extract shell scripts out of Travis config file.
 - Silence `WordPressVIPMinimum.Cache.BatcacheWhitelistedParams` for VIP Go ruleset.
 - Silence variable assignment condition rule.
 - Docs: Updated Readme for more accuracy.
 - Docs: Updated VIP link references.
 - Removed string concatenation for messages for better readability.

### Fixed
 - Unreplaced placeholders for violation messages in `WordPressVIPMinimum.VIP.FetchingRemoteDataSniff`.
 - `WordPressVIPMinimum.Filters.AlwaysReturnSniff` not reporting filter callbacks that don't `return` _anywhere_ inside the function body.
 - Incorrect severity level parameters in `WordPressVIPMinimum.Variables.VariableAnalysis` sniff since they are passed in as a string.
 - Detection of double quotes in `WordPressVIPMinimum.Variables.ServerVariables`, add additional server variables and update unit tests.
 - Typo: `WordPressVIPMinimum.Files.IncludingNonPHPFile` messages, switching `get_file_contents` to `file_get_contents`.
 - Typo: "returning" in `WordPressVIPMinimum.Filters.AlwaysReturn.voidReturn` message.
 - Typo: `WordPressVIPMinimum.VIP.WPQueryParameters.suppressFiltersTrue`, switching `probihted` to `prohibited`.
 - ~~Integration~~ Ruleset tests not running in Travis.
 
### Removed
 - BREAKING: `WordPressVIPMinimum.SVG.HTMLCodeSniff` (SVG support), since it was not working well. You should remove any reference to this in your custom ruleset.
 - `var_dump` from `WordPressVIPMinimum` ruleset since it should be the same type as `var_export`
 - `wpcom_vip_get_page_by_path` from `WordPressVIPMinimum.VIP.RestrictedFunctions`
 - Version check for PHP 7 or less in `WordPressVIPMinimum.Variables.VariableAnalysis` unit test since tests are not failing anymore.

## [0.3.1] - 2018-12-04

Originally tagged as 0.2.5.

Props: emrikol, GaryJones, gudmdharalds, mikeyarce, nickdaugherty, paulschreiber, rebeccahum, sboisvert, tomjn.

## [0.3.0] - 2018-08-15

Props: BrookeDot, david-binda, GaryJones, gudmdharalds, rebeccahum, sboisvert, tomjn, uxcitizen.

## [0.2.4] - 2018-07-18

Props: david-binda, sboisvert, tessneedham, trepmal.

## [0.2.3] - 2018-03-29

Props: david-binda, jacklenox, pyronaur, sboisvert, trepmal, vaurdan.

## [0.2.2] - 2018-01-19

Props: david-binda.

## [0.2.1] - 2017-12-01

Props: david-binda, philipjohn, tomjn.

## [0.2.0] - 2017-08-15

Props: david-binda.

## [0.1.0] - 2017-08-14

Initial release.

Props: david-binda, pkevan.

[3.0.1]: https://github.com/Automattic/VIP-Coding-Standards/compare/3.0.0...3.0.1
[3.0.0]: https://github.com/Automattic/VIP-Coding-Standards/compare/2.3.4...3.0.0
[2.3.4]: https://github.com/Automattic/VIP-Coding-Standards/compare/2.3.3...2.3.4
[2.3.3]: https://github.com/Automattic/VIP-Coding-Standards/compare/2.3.2...2.3.3
[2.3.2]: https://github.com/Automattic/VIP-Coding-Standards/compare/2.3.1...2.3.2
[2.3.1]: https://github.com/Automattic/VIP-Coding-Standards/compare/2.3.0...2.3.1
[2.3.0]: https://github.com/Automattic/VIP-Coding-Standards/compare/2.2.0...2.3.0
[2.2.0]: https://github.com/Automattic/VIP-Coding-Standards/compare/2.1.0...2.2.0
[2.1.0]: https://github.com/Automattic/VIP-Coding-Standards/compare/2.0.0...2.1.0
[2.0.0]: https://github.com/Automattic/VIP-Coding-Standards/compare/1.0.0...2.0.0
[1.0.0]: https://github.com/Automattic/VIP-Coding-Standards/compare/0.4.0...1.0.0
[0.4.0]: https://github.com/automattic/vip-coding-standards/compare/0.3.1...0.4.0
[0.3.1]: https://github.com/automattic/vip-coding-standards/compare/0.3.0...0.3.1
[0.3.0]: https://github.com/automattic/vip-coding-standards/compare/0.2.4...0.3.0
[0.2.4]: https://github.com/automattic/vip-coding-standards/compare/0.2.3...0.2.4
[0.2.3]: https://github.com/automattic/vip-coding-standards/compare/0.2.2...0.2.3
[0.2.2]: https://github.com/automattic/vip-coding-standards/compare/0.2.1...0.2.2
[0.2.1]: https://github.com/automattic/vip-coding-standards/compare/0.2.0...0.2.1
[0.2.0]: https://github.com/automattic/vip-coding-standards/compare/0.1.0...0.2.0
[0.1.0]: https://github.com/automattic/vip-coding-standards/compare/f0e821c0b91f7e5b3850c5ae7bfab4f38d24e406...0.1.0
