# PHPCSUtils: A suite of utility functions for use with PHP_CodeSniffer

<div aria-hidden="true">

[![Latest Stable Version](https://img.shields.io/packagist/v/phpcsstandards/phpcsutils?label=stable)][phpcsutils-packagist]
[![Release Date of the Latest Version](https://img.shields.io/github/release-date/PHPCSStandards/PHPCSUtils.svg?maxAge=1800)][phpcsutils-releases]
:construction:
[![Latest Unstable Version](https://img.shields.io/badge/unstable-dev--develop-e68718.svg?maxAge=2419200)](https://packagist.org/packages/phpcsstandards/phpcsutils#dev-develop)
[![Last Commit to Unstable](https://img.shields.io/github/last-commit/PHPCSStandards/PHPCSUtils/develop.svg)](https://github.com/PHPCSStandards/PHPCSUtils/commits/develop)

[![Docs website](https://github.com/PHPCSStandards/PHPCSUtils/actions/workflows/update-docs.yml/badge.svg)][phpcsutils-web]
[![CS Build Status](https://github.com/PHPCSStandards/PHPCSUtils/actions/workflows/basics.yml/badge.svg?branch=develop)](https://github.com/PHPCSStandards/PHPCSUtils/actions/workflows/basics.yml)
[![Test Build Status](https://github.com/PHPCSStandards/PHPCSUtils/actions/workflows/test.yml/badge.svg?branch=develop)][phpcsutils-tests-gha]
[![Coverage Status](https://coveralls.io/repos/github/PHPCSStandards/PHPCSUtils/badge.svg?branch=develop)](https://coveralls.io/github/PHPCSStandards/PHPCSUtils?branch=develop)

[![Minimum PHP Version](https://img.shields.io/packagist/dependency-v/phpcsstandards/phpcsutils/php.svg)][phpcsutils-packagist]
[![Tested on PHP 5.4 to 8.4](https://img.shields.io/badge/tested%20on-PHP%205.4%20|%205.5%20|%205.6%20|%207.0%20|%207.1%20|%207.2%20|%207.3%20|%207.4%20|%208.0%20|%208.1%20|%208.2%20|%208.3%20|%208.4-brightgreen.svg?maxAge=2419200)][phpcsutils-tests-gha]

[![License: LGPLv3](https://img.shields.io/github/license/PHPCSStandards/PHPCSUtils)](https://github.com/PHPCSStandards/PHPCSUtils/blob/stable/LICENSE)
![Awesome](https://img.shields.io/badge/awesome%3F-yes!-brightgreen.svg)

</div>

* [Features](#features)
* [Minimum Requirements](#minimum-requirements)
* [Integrating PHPCSUtils in your external PHPCS standard](#integrating-phpcsutils-in-your-external-phpcs-standard)
    - [Composer-based](#composer-based)
    - [Non-Composer based integration](#non-composer-based-integration)
* [Frequently Asked Questions](#frequently-asked-questions)
* [Potential Support Questions from your End-Users](#potential-support-questions-from-your-end-users)
* [Contributing](#contributing)
* [License](#license)


## Features

[PHPCSUtils][phpcsutils-repo] is a set of utilities to aid developers of sniffs for [PHP_CodeSniffer] (or "PHPCS" for short).

This package offers the following features:

<div id="feature-list">

### An ever-growing number of utility functions for use with PHP_CodeSniffer

Whether you need to split an `array` into the individual items, are trying to determine which variables are being assigned to in a `list()` or are figuring out whether a function has a DocBlock, PHPCSUtils has got you covered!

Includes improved versions of the PHPCS native utility functions and plenty of new utility functions.

These functions are compatible with PHPCS 3.13.5 up to PHPCS `4.x`.

### A collection of static properties and methods for often-used token groups

Collections of related tokens often-used and needed for sniffs.
These are additional "token groups" to compliment the ones available through the PHPCS native `PHP_CodeSniffer\Util\Tokens` class.

### Several abstract sniff classes which your sniffs can extend

These classes take most of the heavy lifting away for some frequently occurring sniff types.

### Test utilities

An abstract `UtilityMethodTestCase` class to support testing of your utility methods written for PHP_CodeSniffer.
Supports PHPUnit 4.x up to 11.x.

### Use the latest version of PHP_CodeSniffer native utility functions

Normally to use the latest version of PHP_CodeSniffer native utility functions, you would have to raise the minimum requirements of your external PHPCS standard.

Now you won't have to anymore. This package allows you to use the latest version of those utility functions in all PHP_CodeSniffer versions from PHPCS 3.13.0 and up.

### Fully documented

To see detailed information about all the available abstract sniffs, utility functions and PHPCS helper functions, have a read through the [extensive documentation][phpcsutils-web].

</div>


## Minimum Requirements

* PHP 5.4 or higher.
* [PHP_CodeSniffer] 3.13.5+/4.0.1+.
* Recommended PHP extensions for optimal functionality:
    - PCRE with Unicode support (normally enabled by default)


## Integrating PHPCSUtils in your external PHPCS standard

### Composer-based

If your external PHP_CodeSniffer standard only supports Composer-based installs, integrating PHPCSUtils is pretty straight forward.

Run the following from the root of your external PHPCS standard's project:

```bash
composer config allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
composer require phpcsstandards/phpcsutils:"^1.0"
```

No further action needed. You can start using all the utility functions, abstract sniff classes and other features of PHPCSUtils straight away.

> :information_source: The PHPCSUtils package includes the [Composer PHPCS plugin].
>
> This plugin will automatically register PHPCSUtils (and your own external standard) with PHP_CodeSniffer, so you and your users don't have to worry about this anymore.
>
> :warning: Note: if your end-user installation instructions include instructions on adding a Composer PHPCS plugin or on manually registering your standard with PHPCS using the `--config-set installed_paths` command, you can remove those instructions as they are no longer needed.
>
> :information_source: As of Composer 2.2, Composer will [ask for permission](https://blog.packagist.com/composer-2-2/#more-secure-plugin-execution) to allow the Composer PHPCS plugin to execute code. For the plugin to be functional, permission needs to be granted.
> This can be done ahead of time by instructing your users to run the following command before installing your standard:
>
> ```bash
> composer config allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
> ```

#### Running your unit tests

If your unit tests use the PHP_CodeSniffer native unit test suite, all is good.

If you have your own unit test suite to test your sniffs, make sure to load the Composer `vendor/autoload.php` file in your PHPUnit bootstrap file or _as_ the PHPUnit bootstrap file.

If you intend to use the test utilities provided in the `PHPCSUtils/TestUtils` directory, make sure you also load the `vendor/phpcsstandards/phpcsutils/phpcsutils-autoload.php` file in your PHPUnit bootstrap file.


### Non-Composer based integration

In this case, more than anything, you will need to update the non-Composer installation instructions for your end-users.

To use a non-Composer based installation for your sniff development environment, the same instructions would apply.

Your installation instructions for a non-Composer based installation will probably look similar to this:

> * Install [PHP_CodeSniffer] via [your preferred method](https://github.com/PHPCSStandards/PHP_CodeSniffer#installation).
> * Register the path to PHPCS in your system `$PATH` environment variable to make the `phpcs` command available from anywhere in your file system.
> * Download the \[latest _YourStandardName_ release\] and unzip/untar it into an arbitrary directory.
>     You can also choose to clone the repository using git.
> * Add the path to the directory in which you placed your copy of the _YourStandardName_ repo to the PHP_CodeSniffer configuration using the below command:
>
>    ```bash
>    phpcs --config-set installed_paths /path/to/YourStandardName
>    ```
>
>    **Warning**: :warning: The `installed_paths` command overwrites any previously set `installed_paths`. If you have previously set `installed_paths` for other external standards, run `phpcs --config-show` first and then run the `installed_paths` command with all the paths you need separated by comma's, i.e.:
>
>    ```bash
>    phpcs --config-set installed_paths /path/1,/path/2,/path/3
>    ```

For things to continue working when you add PHPCSUtils to your standard, you need to replace the last bullet with this:

> * **Next, download the [latest PHPCSUtils release][phpcsutils-releases] and unzip/untar it into an arbitrary directory.**
>     You can also choose to clone the repository using git.
> * Add the path to the **directories** in which you placed your copy of the _YourStandardName_ repo **and the PHPCSUtils repo** to the PHP_CodeSniffer configuration using the below command:
>
>    ```bash
>    phpcs --config-set installed_paths /path/to/YourStandardName,/path/to/PHPCSUtils
>    ```
>
>    **Warning**: :warning: The `installed_paths` command overwrites any previously set `installed_paths`. If you have previously set `installed_paths` for other external standards, run `phpcs --config-show` first and then run the `installed_paths` command with all the paths you need separated by comma's, i.e.:
>
>    ```bash
>    phpcs --config-set installed_paths /path/1,/path/2,/path/3
>    ```

#### Running your unit tests

To support non-Composer based installs for running your sniff unit tests, you will need to adjust the PHPUnit `bootstrap.php` file to allow for passing an environment variable pointing to your PHPCSUtils installation.

<details>
  <summary><b>Example bootstrap code using a <code>PHPCSUtils_DIR</code> environment variable</b></summary>

```php
// Get the PHPCS dir from an environment variable.
$phpcsUtilDir = getenv('PHPCSUtils_DIR');

// This may be a Composer install.
if ($phpcsUtilDir === false && file_exists(__DIR__ . '/vendor/autoload.php')) {
    $vendorDir    = __DIR__ . '/vendor';
    $phpcsUtilDir = $vendorDir . '/phpcsstandards/phpcsutils';

    // Load the Composer autoload file.
    require_once $vendorDir . '/autoload.php';

    // This snippet is only needed when you use the PHPCSUtils TestUtils.
    if (file_exists($phpcsUtilDir . '/phpcsutils-autoload.php')) {
        require_once $phpcsUtilDir . '/phpcsutils-autoload.php';
    }

} elseif ($phpcsUtilDir !== false) {
    $phpcsUtilDir = realpath($phpcsUtilDir);

    require_once $phpcsUtilDir . '/phpcsutils-autoload.php';
} else {
    echo 'Uh oh... can\'t find PHPCSUtils.

If you use Composer, please run `composer install`.
Otherwise, make sure you set a `PHPCSUtils_DIR` environment variable in your phpunit.xml file
pointing to the PHPCS directory.
';

    die(1);
}
```

</details>

Once that's done, you will need to make a small tweak to your own dev environment to get the unit tests running for a non-Composer based install:

* Copy your project's `phpunit.xml.dist` file to `phpunit.xml`.
* Add the following to the `phpunit.xml` file within the `<phpunit>` tags, replacing `/path/to/PHPCSUtils` with the path in which you installed PHPCSUtils on your local system:

    ```xml
    <php>
        <env name="PHPCSUtils_DIR" value="/path/to/PHPCSUtils"/>
    </php>
    ```


## Frequently Asked Questions

<div class="faq">

### Q: How does this all work without an external standard needing to register an autoloader?

A: As PHPCSUtils is registered with PHPCS as an external standard and PHPCSUtils complies with the naming requirements of PHPCS, the PHPCS native autoloader will automatically take care of loading the classes used from PHPCSUtils.

### Q: Does using PHPCSUtils have any effect on the PHPCS native sniffs?

A: No. PHPCSUtils will only work for those sniffs which explicitly use the PHPCSUtils functionality.

If your standard includes both PHPCS native sniffs as well as your own sniffs, your own sniffs can benefit from the back-compat layer offered by PHPCSUtils, as well as from the additional utility functions. However, the PHPCS native sniffs will not receive those benefits, as PHPCS itself does not use PHPCSUtils.

### Q: Do the utilities work with javascript/CSS files?

A: JS/CSS support will be removed from `PHP_CodeSniffer` in PHPCS 4.x.
While at this time, some of the utilities _may_ work with JS/CSS files, PHPCSUtils does not offer formal support for JS/CSS sniffing with `PHP_CodeSniffer` and will stop any existing support once PHPCS 4.x has been released.

### Q: Are all file encodings supported?

A: No. The UTF-8 file encoding is the only officially supported encoding. Support for other encodings may incidentally work, but is not officially supported.

> **It is recommended to advise your users to save their files as UTF-8 encoded for the best results.**

Note: `PHP_CodeSniffer` 3.x defaults to UTF-8 as the expected file encoding.

</div>


## Potential Support Questions from your End-Users

<div class="faq">

### Q: A user reports a fatal "class not found" error for a class from PHPCSUtils

1. Check that the version of PHPCSUtils the user has installed complies with the minimum version of PHPCSUtils your standard requires. If not, they will need to upgrade.
2. If the version is correct, this indicates that the end-user does not have PHPCSUtils installed and/or registered with PHP_CodeSniffer.
    - Please review your standard's installation instructions to make sure that PHPCSUtils will be installed when those are followed.
    - Inform the user to install PHPCSUtils and register it with PHP_CodeSniffer.

---

> :bulb: **Pro-tip**: if you want to prevent the fatal error and show a _friendlier_ error message instead, add `<rule ref="PHPCSUtils"/>` to your standard's `ruleset.xml` file.
>
> With that in place, `PHP_CodeSniffer` will show a _"ERROR: the "PHPCSUtils" coding standard is not installed."_ message if PHPCSUtils is missing as soon as the ruleset loading is finished.

---

> :bulb: **Pro-tip**: provide upgrade instructions for your end-users. For Composer-based installs, those should look like this:
>
> ```bash
> composer update your/cs-package --with-[all-]dependencies
> ```
>
> That way, when the user updates your coding standards package, they will automatically also update PHPCSUtils.

---

</div>


## Contributing

Contributions to this project are welcome. Clone the repo, branch off from `develop`, make your changes, commit them and send in a pull request.

If you are unsure whether the changes you are proposing would be welcome, please open an issue first to discuss your proposal.


## License

This code is released under the [GNU Lesser General Public License (LGPLv3)](LICENSE).


[PHP_CodeSniffer]:       https://github.com/PHPCSStandards/PHP_CodeSniffer
[Composer PHPCS plugin]: https://github.com/PHPCSStandards/composer-installer
[phpcsutils-repo]:       https://github.com/PHPCSStandards/PHPCSUtils
[phpcsutils-web]:        https://phpcsutils.com/
[phpcsutils-packagist]:  https://packagist.org/packages/phpcsstandards/phpcsutils
[phpcsutils-releases]:   https://github.com/PHPCSStandards/PHPCSUtils/releases
[phpcsutils-tests-gha]:  https://github.com/PHPCSStandards/PHPCSUtils/actions/workflows/test.yml
