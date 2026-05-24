<?php
/**
 * PHPCSUtils, utility functions and classes for PHP_CodeSniffer sniff developers.
 *
 * @package   PHPCSUtils
 * @copyright 2019-2024 PHPCSUtils Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSUtils
 */

namespace PHPCSUtils\TestUtils;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Exceptions\RuntimeException;
use PHP_CodeSniffer\Ruleset;

/**
 * Ruleset class for use in the tests.
 *
 * The PHP_CodeSniffer Ruleset will throw an error when no (valid) sniffs are registered,
 * but for utility method tests, we're not concerned about that.
 *
 * This ruleset double will catch this error and discard it.
 *
 * @since 1.1.0
 */
final class RulesetDouble extends Ruleset
{

    /**
     * Initialise the ruleset that the run will use.
     *
     * @since 1.1.0
     *
     * @param \PHP_CodeSniffer\Config $config The config data for the run.
     *
     * @return void
     */
    public function __construct(Config $config)
    {
        try {
            parent::__construct($config);
        } catch (RuntimeException $e) {
            /*
             * In the UtilityMethodTestCase, we're using a fake sniff name for the tests.
             * As PHPCS 4.0 will check more strictly that sniffs exist and comply with naming conventions,
             * this means, as of PHPCS 4.0, the ruleset creation will end with an error.
             * This error is not something we are concerned about, as we're not testing sniffs,
             * so we should be able to safely ignore it.
             */
            if (\rtrim($e->getMessage()) !== 'ERROR: No sniffs were registered.') {
                // Rethrow the exception to fail the test, as this is not the exception we expected.
                throw $e;
            }
        }
    }
}
