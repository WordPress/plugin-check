<?php
/**
 * PHPCSUtils, utility functions and classes for PHP_CodeSniffer sniff developers.
 *
 * @package   PHPCSUtils
 * @copyright 2019-2020 PHPCSUtils Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSUtils
 */

namespace PHPCSUtils\TestUtils;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Exceptions\TokenizerException;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Ruleset;
use PHPCSUtils\BackCompat\Helper;
use PHPCSUtils\Exceptions\TestFileNotFound;
use PHPCSUtils\Exceptions\TestMarkerNotFound;
use PHPCSUtils\Exceptions\TestTargetNotFound;
use PHPCSUtils\TestUtils\ConfigDouble;
use PHPCSUtils\TestUtils\RulesetDouble;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * Base class for use when testing utility methods for PHP_CodeSniffer.
 *
 * This class is compatible with PHP_CodeSniffer 3.x and 4.x.
 *
 * This class is compatible with {@link https://phpunit.de/ PHPUnit} 4.5 - 11.x providing the PHPCSUtils
 * autoload file is included in the test bootstrap. For more information about that, please consult
 * the project's {@link https://github.com/PHPCSStandards/PHPCSUtils/blob/develop/README.md README}.
 *
 * To allow for testing of tab vs space content, the `tabWidth` is set to `4` by default.
 *
 * Typical usage:
 *
 * Test case file `path/to/ClassUnderTestUnitTest.inc`:
 * ```php
 * <?php
 *
 * /* testTestCaseDescription * /
 * const BAR = false;
 * ```
 *
 * Test file `path/to/ClassUnderTestUnitTest.php`:
 * ```php
 * <?php
 *
 * use PHPCSUtils\TestUtils\UtilityMethodTestCase;
 * use YourStandard\ClassUnderTest;
 *
 * class ClassUnderTestUnitTest extends UtilityMethodTestCase {
 *
 *     /**
 *      * Testing utility method MyMethod.
 *      *
 *      * @dataProvider dataMyMethod
 *      *
 *      * @covers \YourStandard\ClassUnderTest::MyMethod
 *      *
 *      * @param string $commentString The comment which prefaces the target token in the test file.
 *      * @param string $expected      The expected return value.
 *      *
 *      * @return void
 *      * /
 *     public function testMyMethod($commentString, $expected)
 *     {
 *         $stackPtr = $this->getTargetToken($commentString, [\T_TOKEN_CONSTANT, \T_ANOTHER_TOKEN]);
 *         $class    = new ClassUnderTest();
 *         $result   = $class->MyMethod(self::$phpcsFile, $stackPtr);
 *         // Or for static utility methods:
 *         $result   = ClassUnderTest::MyMethod(self::$phpcsFile, $stackPtr);
 *
 *         $this->assertSame($expected, $result);
 *     }
 *
 *     /**
 *      * Data Provider.
 *      *
 *      * @see ClassUnderTestUnitTest::testMyMethod() For the array format.
 *      *
 *      * @return array
 *      * /
 *     public static function dataMyMethod()
 *     {
 *         return array(
 *             array('/* testTestCaseDescription * /', false),
 *         );
 *     }
 * }
 * ```
 *
 * Note:
 * - Remove the space between the comment closers `* /` for a working example.
 * - Each test case separator comment MUST start with `/* test`.
 *   This is to allow the {@see UtilityMethodTestCase::getTargetToken()} method to
 *   distinquish between the test separation comments and comments which may be part
 *   of the test case.
 * - The test case file and unit test file should be placed in the same directory.
 * - For working examples using this abstract class, have a look at the unit tests
 *   for the PHPCSUtils utility functions themselves.
 *
 * @since 1.0.0
 * @since 1.0.7 Compatible with PHPUnit 10.
 * @since 1.1.0 Compatible with PHPUnit 11.
 * @since 1.1.0 Compatible with PHP_CodeSniffer 4.0.0.
 */
abstract class UtilityMethodTestCase extends TestCase
{

    /**
     * The PHPCS version the tests are being run on.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected static $phpcsVersion = '0';

    /**
     * The file extension of the test case file (without leading dot).
     *
     * This allows concrete test classes to overrule the default `"inc"` with, for instance,
     * `"js"` or `"css"` when applicable.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected static $fileExtension = 'inc';

    /**
     * Full path to the test case file associated with the concrete test class.
     *
     * Optional. If left empty, the case file will be presumed to be in
     * the same directory and named the same as the test class, but with an
     * `"inc"` file extension.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected static $caseFile = '';

    /**
     * The tab width setting to use when tokenizing the file.
     *
     * This allows for test case files to use a different tab width than the default.
     *
     * @since 1.0.0
     *
     * @var int
     */
    protected static $tabWidth = 4;

    /**
     * The \PHP_CodeSniffer\Files\File object containing the parsed contents of the test case file.
     *
     * @since 1.0.0
     *
     * @var \PHP_CodeSniffer\Files\File|null
     */
    protected static $phpcsFile;

    /**
     * Set the name of a sniff to pass to PHPCS to limit the run (and force it to record errors).
     *
     * Normally, this propery won't need to be overloaded, but for utility methods which record
     * violations and contain fixers, setting a dummy sniff name equal to the sniff name passed
     * in the error code for `addError()`/`addWarning()` during the test, will allow for testing
     * the recording of these violations, as well as testing the fixer.
     *
     * @since 1.0.0
     *
     * @var array<string>
     */
    protected static $selectedSniff = ['Dummy.Dummy.Dummy'];

    /**
     * Initialize PHPCS & tokenize the test case file.
     *
     * The test case file for a unit test class has to be in the same directory
     * directory and use the same file name as the test class, using the `.inc` extension
     * or be explicitly set using the {@see UtilityMethodTestCase::$fileExtension}/
     * {@see UtilityMethodTestCase::$caseFile} properties.
     *
     * Note: This is a PHPUnit cross-version compatible {@see \PHPUnit\Framework\TestCase::setUpBeforeClass()}
     * method.
     *
     * @since 1.0.0
     *
     * @beforeClass
     *
     * @return void
     */
    #[BeforeClass]
    public static function setUpTestFile()
    {
        parent::setUpBeforeClass();

        self::$phpcsVersion = Helper::getVersion();

        $caseFile = static::$caseFile;
        if (\is_string($caseFile) === false || $caseFile === '') {
            $testClass = \get_called_class();
            $testFile  = (new ReflectionClass($testClass))->getFileName();
            $caseFile  = \substr($testFile, 0, -3) . static::$fileExtension;
        }

        $config = new ConfigDouble();

        /*
         * Limiting the run to just one (dummy) sniff will make it, yet again, slightly faster.
         */
        $config->sniffs = static::$selectedSniff;

        // Disable caching.
        $config->cache = false;

        // Also set a tab-width to enable testing tab-replaced vs `orig_content`.
        $config->tabWidth = static::$tabWidth;

        $ruleset = new RulesetDouble($config);

        self::$phpcsFile = self::parseFile($caseFile, $ruleset, $config);
    }

    /**
     * Create a File object.
     *
     * The file will only be parsed, not processed.
     *
     * This helper method can also be used to create a secondary file object using the same sniff objects
     * as used for the original test case file.
     * To do so, pass `self::$phpcsFile->ruleset` for the $ruleset and `self::$phpcsFile->config` for the $config.
     *
     * @param string                   $caseFile The absolute path to the file.
     * @param \PHP_CodeSniffer\Ruleset $ruleset  The ruleset for the run.
     * @param \PHP_CodeSniffer\Config  $config   The config data for the run.
     *
     * @return \PHP_CodeSniffer\Files\File
     */
    protected static function parseFile($caseFile, Ruleset $ruleset, Config $config)
    {
        if (\is_readable($caseFile) === false) {
            parent::fail("Test case file missing. Expected case file location: $caseFile");
        }

        $file = new LocalFile($caseFile, $ruleset, $config);

        // Only tokenize the file, do not process it.
        try {
            $file->parse();
        } catch (TokenizerException $e) {
            // PHPCS 3.5.0 and higher. This is handled below.
        }

        // Fail the test if the file failed to tokenize.
        if ($file->numTokens === 0) {
            parent::fail("Tokenizing of the test case file failed for case file: $caseFile");
        }

        return $file;
    }

    /**
     * Skip JS and CSS related tests on PHPCS 4.x.
     *
     * PHPCS 4.x drops support for the JS and CSS tokenizers.
     * This method takes care of automatically skipping tests involving JS/CSS case files
     * when the tests are being run with PHPCS 4.x.
     *
     * Note: This is a PHPUnit cross-version compatible {@see \PHPUnit\Framework\TestCase::setUp()}
     * method.
     *
     * @since 1.0.0
     *
     * @before
     *
     * @return void
     */
    #[Before]
    public function skipJSCSSTestsOnPHPCS4()
    {
        if (static::$fileExtension !== 'js' && static::$fileExtension !== 'css') {
            return;
        }

        if (\version_compare(self::$phpcsVersion, '3.99.99', '<=')) {
            return;
        }

        $this->markTestSkipped('JS and CSS support has been removed in PHPCS 4.');
    }

    /**
     * Clean up after finished test by resetting all static properties to their default values.
     *
     * Note: This is a PHPUnit cross-version compatible {@see \PHPUnit\Framework\TestCase::tearDownAfterClass()}
     * method.
     *
     * @since 1.0.0
     *
     * @afterClass
     *
     * @return void
     */
    #[AfterClass]
    public static function resetTestFile()
    {
        /*
         * Explicitly trigger __destruct() on the ConfigDouble to reset the Config statics.
         * The explicit method call prevents potential stray test-local references to the $config object
         * preventing the destructor from running the clean up (which without stray references would be
         * automagically triggered when `self::$phpcsFile` is reset, but we can't definitively rely on that).
         */
        if (isset(self::$phpcsFile)) {
            self::$phpcsFile->config->__destruct();
        }

        self::$phpcsVersion  = '0';
        self::$fileExtension = 'inc';
        self::$caseFile      = '';
        self::$tabWidth      = 4;
        self::$phpcsFile     = null;
        self::$selectedSniff = ['Dummy.Dummy.Dummy'];
    }

    /**
     * Helper function to set the value of a private static property on the PHPCS Config class.
     *
     * @since      1.0.9
     * @deprecated 1.1.0 Use the `PHPCSUtils\TestUtils\ConfigDouble::setStaticConfigProperty()` method instead.
     *
     * @codeCoverageIgnore
     *
     * @param string $name  The name of the property to set.
     * @param mixed  $value The value to set the property to.
     *
     * @return void
     */
    public static function setStaticConfigProperty($name, $value)
    {
        $property = new ReflectionProperty('PHP_CodeSniffer\Config', $name);
        (\PHP_VERSION_ID < 80100) && $property->setAccessible(true);
        $property->setValue(null, $value);
        (\PHP_VERSION_ID < 80100) && $property->setAccessible(false);
    }

    /**
     * Check whether or not the PHP 8.0 identifier name tokens will be in use.
     *
     * The expected token positions/token counts for certain tokens will differ depending
     * on whether the PHP 8.0 identifier name tokenization is used or the PHP < 8.0
     * identifier name tokenization.
     *
     * Tests can use this method to determine which flavour of tokenization to expect and
     * to set test expectations accordingly.
     *
     * @codeCoverageIgnore Nothing to test.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public static function usesPhp8NameTokens()
    {
        return \version_compare(Helper::getVersion(), '3.99.99', '>=');
    }

    /**
     * Test QA: verify that a test case file does not contain any duplicate test markers.
     *
     * When a test case file contains a lot of test cases, it is easy to overlook that a test marker name
     * is already in use.
     * A test wouldn't necessarily fail on this, but would not be testing what is intended to be tested as
     * it would be verifying token properties for the wrong token.
     *
     * This test safeguards against this.
     *
     * @since 1.1.0
     *
     * @coversNothing
     *
     * @return void
     */
    public function testTestMarkersAreUnique()
    {
        $this->assertTestMarkersAreUnique(self::$phpcsFile);
    }

    /**
     * Assertion to verify that a test case file does not contain any duplicate test markers.
     *
     * @since 1.1.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file to validate.
     *
     * @return void
     */
    public static function assertTestMarkersAreUnique(File $phpcsFile)
    {
        $tokens = $phpcsFile->getTokens();

        // Collect all marker comments in the file.
        $seenComments = [];
        for ($i = 0; $i < $phpcsFile->numTokens; $i++) {
            if ($tokens[$i]['code'] !== \T_COMMENT) {
                continue;
            }

            if (\stripos($tokens[$i]['content'], '/* test') !== 0) {
                continue;
            }

            $seenComments[] = $tokens[$i]['content'];
        }

        self::assertSame(\array_unique($seenComments), $seenComments, 'Duplicate test markers found.');
    }

    /**
     * Get the token pointer for a target token based on a specific comment.
     *
     * Note: the test delimiter comment MUST start with `/* test` to allow this function to
     * distinguish between comments used *in* a test and test delimiters.
     *
     * @since 1.0.0
     *
     * @param string                       $commentString The complete delimiter comment to look for as a string.
     *                                                    This string should include the comment opener and closer.
     * @param int|string|array<int|string> $tokenType     The type of token(s) to look for.
     * @param string|null                  $tokenContent  Optional. The token content for the target token.
     *
     * @return int
     *
     * @throws \PHPCSUtils\Exceptions\TestMarkerNotFound When the delimiter comment for the test was not found.
     * @throws \PHPCSUtils\Exceptions\TestTargetNotFound When the target token cannot be found.
     */
    public static function getTargetToken($commentString, $tokenType, $tokenContent = null)
    {
        if ((self::$phpcsFile instanceof File) === false) {
            throw new TestFileNotFound();
        }

        $start   = (self::$phpcsFile->numTokens - 1);
        $comment = self::$phpcsFile->findPrevious(
            \T_COMMENT,
            $start,
            null,
            false,
            $commentString
        );

        if ($comment === false) {
            throw TestMarkerNotFound::create($commentString, self::$phpcsFile->getFilename());
        }

        $tokens = self::$phpcsFile->getTokens();
        $end    = ($start + 1);

        // Limit the token finding to between this and the next delimiter comment.
        for ($i = ($comment + 1); $i < $end; $i++) {
            if ($tokens[$i]['code'] !== \T_COMMENT) {
                continue;
            }

            if (\stripos($tokens[$i]['content'], '/* test') === 0) {
                $end = $i;
                break;
            }
        }

        $target = self::$phpcsFile->findNext(
            $tokenType,
            ($comment + 1),
            $end,
            false,
            $tokenContent
        );

        if ($target === false) {
            throw TestTargetNotFound::create($commentString, $tokenContent, self::$phpcsFile->getFilename());
        }

        return $target;
    }

    /**
     * Helper method to tell PHPUnit to expect a PHPCS Exception in a PHPUnit and PHPCS cross-version
     * compatible manner.
     *
     * @since 1.0.0
     *
     * @param string $msg  The expected exception message.
     * @param string $type The PHPCS native exception type to expect. Either 'runtime' or 'tokenizer'.
     *                     Defaults to 'runtime'.
     *
     * @return void
     */
    public function expectPhpcsException($msg, $type = 'runtime')
    {
        $exception = 'PHP_CodeSniffer\Exceptions\RuntimeException';
        if ($type === 'tokenizer') {
            $exception = 'PHP_CodeSniffer\Exceptions\TokenizerException';
        }

        if (\method_exists($this, 'expectException')) {
            // PHPUnit 5+.
            $this->expectException($exception);
            $this->expectExceptionMessage($msg);
        } else {
            // PHPUnit 4.
            $this->setExpectedException($exception, $msg);
        }
    }
}
