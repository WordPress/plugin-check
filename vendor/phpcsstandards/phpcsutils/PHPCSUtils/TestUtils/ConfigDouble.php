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
use PHPCSUtils\BackCompat\Helper;
use ReflectionProperty;

/**
 * Config class for use in the tests.
 *
 * The PHP_CodeSniffer Config class contains a number of static properties.
 * As the value of these static properties will be retained between instantiations of the class,
 * config values set in one test can influence the results for another test, which makes tests unstable.
 *
 * This class is a "double" of the Config class which prevents this from happening.
 * In _most_ cases, tests should be using this class instead of the "normal" Config,
 * with the exception of select tests for the PHPCS Config class itself.
 *
 * @since 1.1.0
 */
final class ConfigDouble extends Config
{

    /**
     * The PHPCS version the tests are being run on.
     *
     * @since 1.1.0
     *
     * @var string
     */
    private $phpcsVersion = '0';

    /**
     * Whether or not the setting of a standard should be skipped.
     *
     * @since 1.1.0
     *
     * @var bool
     */
    private $skipSettingStandard = false;

    /**
     * Creates a clean Config object and populates it with command line values.
     *
     * @since 1.1.0
     *
     * @param array<string> $cliArgs                An array of values gathered from CLI args.
     * @param bool          $skipSettingStandard    Whether to skip setting a standard to prevent
     *                                              the Config class trying to auto-discover a ruleset file.
     *                                              Should only be set to `true` for tests which actually test
     *                                              the ruleset auto-discovery.
     *                                              Note: there is no need to set this to `true` when a standard
     *                                              is being passed via the `$cliArgs`. Those settings will always
     *                                              respected.
     *                                              Defaults to `false`. Will result in the standard being set
     *                                              to "PSR1" if not provided via `$cliArgs`.
     * @param bool          $skipSettingReportWidth Whether to skip setting a report-width to prevent
     *                                              the Config class trying to auto-discover the screen width.
     *                                              Should only be set to `true` for tests which actually test
     *                                              the screen width auto-discovery.
     *                                              Note: there is no need to set this to `true` when a report-width
     *                                              is being passed via the `$cliArgs`. Those settings will always
     *                                              respected.
     *                                              Defaults to `false`. Will result in the reportWidth being set
     *                                              to "80" if not provided via `$cliArgs`.
     *
     * @return void
     */
    public function __construct(array $cliArgs = [], $skipSettingStandard = false, $skipSettingReportWidth = false)
    {
        $this->skipSettingStandard = $skipSettingStandard;
        $this->phpcsVersion        = Helper::getVersion();

        $this->resetSelectProperties();
        $this->preventReadingCodeSnifferConfFile();

        parent::__construct($cliArgs);

        if ($skipSettingReportWidth !== true) {
            $this->preventAutoDiscoveryScreenWidth();
        }
    }

    /**
     * Ensures the static properties in the Config class are reset to their default values
     * when the ConfigDouble is no longer used.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function __destruct()
    {
        $this->setStaticConfigProperty('overriddenDefaults', []);
        $this->setStaticConfigProperty('executablePaths', []);
        $this->setStaticConfigProperty('configData', null);
        $this->setStaticConfigProperty('configDataFile', null);
    }

    /**
     * Sets the command line values and optionally prevents a file system search for a custom ruleset.
     *
     * {@internal Note: `array` type declaration can't be added as the parent class does not have a type declaration
     * for the parameter in the original method.}
     *
     * @since 1.1.0
     *
     * @param array<string> $args An array of command line arguments to set.
     *
     * @return void
     */
    public function setCommandLineValues($args)
    {
        parent::setCommandLineValues($args);

        if ($this->skipSettingStandard !== true) {
            $this->preventSearchingForRuleset();
        }
    }

    /**
     * Reset a few properties on the Config class to their default values.
     *
     * @since 1.1.0
     *
     * @return void
     */
    private function resetSelectProperties()
    {
        $this->setStaticConfigProperty('overriddenDefaults', []);
        $this->setStaticConfigProperty('executablePaths', []);
    }

    /**
     * Prevent the values in a potentially available user-specific `CodeSniffer.conf` file
     * from influencing the tests.
     *
     * This also prevents some file system calls which can influence the test runtime.
     *
     * @since 1.1.0
     *
     * @return void
     */
    private function preventReadingCodeSnifferConfFile()
    {
        $this->setStaticConfigProperty('configData', []);
        $this->setStaticConfigProperty('configDataFile', '');
    }

    /**
     * Prevent searching for a custom ruleset by setting a standard, but only if the test
     * being run doesn't set a standard itself.
     *
     * This also prevents some file system calls which can influence the test runtime.
     *
     * The standard being set is the smallest one available so the ruleset initialization
     * will be the fastest possible.
     *
     * @since 1.1.0
     *
     * @return void
     */
    private function preventSearchingForRuleset()
    {
        $overriddenDefaults = $this->getStaticConfigProperty('overriddenDefaults');
        if (isset($overriddenDefaults['standards']) === false) {
            $this->standards                 = ['PSR1'];
            $overriddenDefaults['standards'] = true;
        }

        self::setStaticConfigProperty('overriddenDefaults', $overriddenDefaults);
    }

    /**
     * Prevent a call to stty to figure out the screen width, but only if the test being run
     * doesn't set a report width itself.
     *
     * @since 1.1.0
     *
     * @return void
     */
    private function preventAutoDiscoveryScreenWidth()
    {
        $settings = $this->getSettings();
        if ($settings['reportWidth'] === 'auto') {
            $this->reportWidth = self::DEFAULT_REPORT_WIDTH;
        }
    }

    /**
     * Helper function to retrieve the value of a private static property on the Config class.
     *
     * Note: As of PHPCS 4.0, the "overriddenDefaults" property is no longer static, but this method
     * will still handle this property.
     *
     * @since 1.1.0
     *
     * @param string $name The name of the property to retrieve.
     *
     * @return mixed
     */
    public function getStaticConfigProperty($name)
    {
        $property = new ReflectionProperty('PHP_CodeSniffer\Config', $name);
        (\PHP_VERSION_ID < 80100) && $property->setAccessible(true);

        if ($name === 'overriddenDefaults' && \version_compare($this->phpcsVersion, '3.99.99', '>')) {
            return $property->getValue($this);
        }

        return $property->getValue();
    }

    /**
     * Helper function to set the value of a private static property on the Config class.
     *
     * Note: As of PHPCS 4.0, the "overriddenDefaults" property is no longer static, but this method
     * will still handle this property.
     *
     * @since 1.1.0
     *
     * @param string $name  The name of the property to set.
     * @param mixed  $value The value to set the property to.
     *
     * @return void
     */
    public function setStaticConfigProperty($name, $value)
    {
        $property = new ReflectionProperty('PHP_CodeSniffer\Config', $name);
        (\PHP_VERSION_ID < 80100) && $property->setAccessible(true);

        if ($name === 'overriddenDefaults' && \version_compare($this->phpcsVersion, '3.99.99', '>')) {
            $property->setValue($this, $value);
        } else {
            $property->setValue(null, $value);
        }

        (\PHP_VERSION_ID < 80100) && $property->setAccessible(false);
    }
}
