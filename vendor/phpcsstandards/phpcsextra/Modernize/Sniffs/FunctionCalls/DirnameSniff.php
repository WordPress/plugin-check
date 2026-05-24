<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Modernize\Sniffs\FunctionCalls;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\BackCompat\Helper;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\Context;
use PHPCSUtils\Utils\PassedParameters;

/**
 * Detect `dirname(__FILE__)` and nested uses of `dirname()`.
 *
 * @since 1.0.0
 */
final class DirnameSniff implements Sniff
{

    /**
     * PHP version as configured or 0 if unknown.
     *
     * @since 1.1.1
     *
     * @var int
     */
    private $phpVersion;

    /**
     * Registers the tokens that this sniff wants to listen for.
     *
     * @since 1.0.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [
            \T_STRING,
            \T_NAME_FULLY_QUALIFIED,
        ];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        if (isset($this->phpVersion) === false || \defined('PHP_CODESNIFFER_IN_TESTS')) {
            // Set default value to prevent this code from running every time the sniff is triggered.
            $this->phpVersion = 0;

            $phpVersion = Helper::getConfigData('php_version');
            if ($phpVersion !== null) {
                $this->phpVersion = (int) $phpVersion;
            }
        }

        if ($this->phpVersion !== 0 && $this->phpVersion < 50300) {
            // PHP version too low, nothing to do.
            return;
        }

        $tokens   = $phpcsFile->getTokens();
        $contents = $tokens[$stackPtr]['content'];
        if ($tokens[$stackPtr]['code'] === \T_NAME_FULLY_QUALIFIED) {
            $contents = \ltrim($contents, '\\');
        }

        if (\strtolower($contents) !== 'dirname') {
            // Not our target.
            return;
        }

        $nextNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
        if ($nextNonEmpty === false
            || $tokens[$nextNonEmpty]['code'] !== \T_OPEN_PARENTHESIS
            || isset($tokens[$nextNonEmpty]['parenthesis_owner']) === true
        ) {
            // Not our target.
            return;
        }

        if (isset($tokens[$nextNonEmpty]['parenthesis_closer']) === false) {
            // Live coding or parse error, ignore.
            return;
        }

        if (Context::inAttribute($phpcsFile, $stackPtr) === true) {
            // Class instantiation in attribute, not function call.
            return;
        }

        // Check if it is really a function call to the global function.
        $prevNonEmpty = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);

        if (isset(Collections::objectOperators()[$tokens[$prevNonEmpty]['code']]) === true
            || $tokens[$prevNonEmpty]['code'] === \T_NEW
        ) {
            // Method call, class instantiation or other "not our target".
            return;
        }

        if ($tokens[$prevNonEmpty]['code'] === \T_NS_SEPARATOR) {
            $prevPrevToken = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($prevNonEmpty - 1), null, true);
            if ($tokens[$prevPrevToken]['code'] === \T_STRING
                || $tokens[$prevPrevToken]['code'] === \T_NAMESPACE
            ) {
                // Namespaced function.
                return;
            }
        }

        /*
         * As of here, we can be pretty sure this is a function call to the global function.
         */
        $opener = $nextNonEmpty;
        $closer = $tokens[$nextNonEmpty]['parenthesis_closer'];

        $parameters = PassedParameters::getParameters($phpcsFile, $stackPtr);
        $paramCount = \count($parameters);
        if (empty($parameters) || $paramCount > 2) {
            // No parameters or too many parameter.
            return;
        }

        $pathParam = PassedParameters::getParameterFromStack($parameters, 1, 'path');
        if ($pathParam === false) {
            // If the path parameter doesn't exist, there's nothing to do.
            return;
        }

        $levelsParam = PassedParameters::getParameterFromStack($parameters, 2, 'levels');
        if ($levelsParam === false && $paramCount === 2) {
            // There must be a typo in the param name or an otherwise stray parameter. Ignore.
            return;
        }

        /*
         * PHP 5.3+: Detect use of dirname(__FILE__).
         */
        if (\strtoupper($pathParam['clean']) === '__FILE__') {
            $levelsValue = false;

            // Determine if the issue is auto-fixable.
            $hasComment = $phpcsFile->findNext(Tokens::$commentTokens, ($opener + 1), $closer);
            $fixable    = ($hasComment === false);

            if ($fixable === true) {
                $levelsValue = $this->getLevelsValue($phpcsFile, $levelsParam);
                if ($levelsParam !== false && $levelsValue === false) {
                    // Can't autofix if we don't know the value of the $levels parameter.
                    $fixable = false;
                }
            }

            $error = 'Use the __DIR__ constant instead of calling dirname(__FILE__) (PHP >= 5.3)';
            $code  = 'FileConstant';

            // Throw the error.
            if ($fixable === false) {
                $phpcsFile->addError($error, $stackPtr, $code);
                return;
            }

            $fix = $phpcsFile->addFixableError($error, $stackPtr, $code);
            if ($fix === true) {
                if ($levelsParam === false || $levelsValue === 1) {
                    // No $levels or $levels set to 1: we can replace the complete function call.
                    $phpcsFile->fixer->beginChangeset();

                    $phpcsFile->fixer->replaceToken($stackPtr, '__DIR__');

                    for ($i = ($stackPtr + 1); $i <= $closer; $i++) {
                        $phpcsFile->fixer->replaceToken($i, '');
                    }

                    // Remove potential leading \.
                    if ($tokens[$prevNonEmpty]['code'] === \T_NS_SEPARATOR) {
                        $phpcsFile->fixer->replaceToken($prevNonEmpty, '');
                    }

                    $phpcsFile->fixer->endChangeset();
                } else {
                    // We can replace the $path parameter and will need to adjust the $levels parameter.
                    $filePtr   = $phpcsFile->findNext(\T_FILE, $pathParam['start'], ($pathParam['end'] + 1));
                    $levelsPtr = $phpcsFile->findNext(\T_LNUMBER, $levelsParam['start'], ($levelsParam['end'] + 1));

                    $phpcsFile->fixer->beginChangeset();
                    $phpcsFile->fixer->replaceToken($filePtr, '__DIR__');
                    $phpcsFile->fixer->replaceToken($levelsPtr, ($levelsValue - 1));
                    $phpcsFile->fixer->endChangeset();
                }
            }

            return;
        }

        /*
         * PHP 7.0+: Detect use of nested calls to dirname().
         */
        if ($this->phpVersion !== 0 && $this->phpVersion < 70000) {
            // No need to check for this issue if the PHP version would not allow for it anyway.
            return;
        }

        if (\preg_match('`^\s*\\\\?dirname\s*\(`i', $pathParam['clean']) !== 1) {
            return;
        }

        /*
         * Check if there is something _behind_ the nested dirname() call within the same parameter.
         *
         * Note: the findNext() calls are safe and will always match the dirname() function call
         * as otherwise the above regex wouldn't have matched.
         */
        $innerDirnamePtr = $phpcsFile->findNext($this->register(), $pathParam['start'], ($pathParam['end'] + 1));
        $innerOpener     = $phpcsFile->findNext(\T_OPEN_PARENTHESIS, ($innerDirnamePtr + 1), ($pathParam['end'] + 1));
        if (isset($tokens[$innerOpener]['parenthesis_closer']) === false) {
            // Shouldn't be possible.
            return; // @codeCoverageIgnore
        }

        $innerCloser = $tokens[$innerOpener]['parenthesis_closer'];
        if ($innerCloser !== $pathParam['end']) {
            $hasContentAfter = $phpcsFile->findNext(
                Tokens::$emptyTokens,
                ($innerCloser + 1),
                ($pathParam['end'] + 1),
                true
            );
            if ($hasContentAfter !== false) {
                // Matched code like: `dirname(dirname($file) . 'something')`. Ignore.
                return;
            }
        }

        /*
         * Determine if this is an auto-fixable error.
         */

        // Step 1: Are there comments ? If so, not auto-fixable as we don't want to remove comments.
        $fixable          = true;
        $outerLevelsValue = false;
        $innerParameters  = [];
        $innerLevelsParam = false;
        $innerLevelsValue = false;

        for ($i = ($opener + 1); $i < $closer; $i++) {
            if (isset(Tokens::$commentTokens[$tokens[$i]['code']])) {
                $fixable = false;
                break;
            }

            if ($tokens[$i]['code'] === \T_OPEN_PARENTHESIS
                && isset($tokens[$i]['parenthesis_closer'])
            ) {
                // Skip over everything within the nested dirname() function call.
                $i = $tokens[$i]['parenthesis_closer'];
            }
        }

        // Step 2: Does the `$levels` parameter exist for the outer dirname() call and if so, is it usable ?
        if ($fixable === true) {
            $outerLevelsValue = $this->getLevelsValue($phpcsFile, $levelsParam);
            if ($levelsParam !== false && $outerLevelsValue === false) {
                // Can't autofix if we don't know the value of the $levels parameter.
                $fixable = false;
            }
        }

        // Step 3: Does the `$levels` parameter exist for the inner dirname() call and if so, is it usable ?
        if ($fixable === true) {
            $innerParameters  = PassedParameters::getParameters($phpcsFile, $innerDirnamePtr);
            $innerLevelsParam = PassedParameters::getParameterFromStack($innerParameters, 2, 'levels');
            $innerLevelsValue = $this->getLevelsValue($phpcsFile, $innerLevelsParam);
            if ($innerLevelsParam !== false && $innerLevelsValue === false) {
                // Can't autofix if we don't know the value of the $levels parameter.
                $fixable = false;
            }
        }

        /*
         * Throw the error.
         */
        $error  = 'Pass the $levels parameter to the dirname() call instead of using nested dirname() calls';
        $error .= ' (PHP >= 7.0)';
        $code   = 'Nested';

        if ($fixable === false) {
            $phpcsFile->addError($error, $stackPtr, $code);
            return;
        }

        $fix = $phpcsFile->addFixableError($error, $stackPtr, $code);
        if ($fix === false) {
            return;
        }

        /*
         * Fix the error.
         */
        $phpcsFile->fixer->beginChangeset();

        // Remove the info in the _outer_ param call.
        for ($i = $opener; $i < $innerOpener; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        for ($i = ($innerCloser + 1); $i <= $closer; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        if ($innerLevelsParam !== false) {
            // Inner $levels parameter already exists, just adjust the value.
            $innerLevelsPtr = $phpcsFile->findNext(
                \T_LNUMBER,
                $innerLevelsParam['start'],
                ($innerLevelsParam['end'] + 1)
            );
            $phpcsFile->fixer->replaceToken($innerLevelsPtr, ($innerLevelsValue + $outerLevelsValue));
        } else {
            // Inner $levels parameter does not exist yet. We need to add it.
            $content = ', ';

            $prevBeforeCloser = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($innerCloser - 1), null, true);
            if ($tokens[$prevBeforeCloser]['code'] === \T_COMMA) {
                // Trailing comma found, no need to add the comma.
                $content = ' ';
            }

            $innerPathParam = PassedParameters::getParameterFromStack($innerParameters, 1, 'path');
            if (isset($innerPathParam['name_token']) === true) {
                // Non-named param cannot follow named param, so add param name.
                $content .= 'levels: ';
            }

            $content .= ($innerLevelsValue + $outerLevelsValue);
            $phpcsFile->fixer->addContentBefore($innerCloser, $content);
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Determine the value of the $levels parameter passed to dirname().
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File     $phpcsFile   The file being scanned.
     * @param array<string, int|string>|false $levelsParam The information about the parameter as retrieved
     *                                                     via PassedParameters::getParameterFromStack().
     *
     * @return int|false Integer levels value or FALSE if the levels value couldn't be determined.
     */
    private function getLevelsValue($phpcsFile, $levelsParam)
    {
        if ($levelsParam === false) {
            return 1;
        }

        $ignore   = Tokens::$emptyTokens;
        $ignore[] = \T_LNUMBER;

        $hasNonNumber = $phpcsFile->findNext($ignore, $levelsParam['start'], ($levelsParam['end'] + 1), true);
        if ($hasNonNumber !== false) {
            return false;
        }

        return (int) $levelsParam['clean'];
    }
}
