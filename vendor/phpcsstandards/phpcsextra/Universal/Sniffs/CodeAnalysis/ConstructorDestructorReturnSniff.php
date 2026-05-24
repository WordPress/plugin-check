<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\CodeAnalysis;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\BackCompat\BCFile;
use PHPCSUtils\BackCompat\Helper;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\FunctionDeclarations;
use PHPCSUtils\Utils\GetTokensAsString;
use PHPCSUtils\Utils\Namespaces;
use PHPCSUtils\Utils\NamingConventions;
use PHPCSUtils\Utils\ObjectDeclarations;
use PHPCSUtils\Utils\Scopes;

/**
 * Verify that a class constructor/destructor does not return anything, nor has a
 * return type declaration (fatal error).
 *
 * @since 1.0.0
 */
final class ConstructorDestructorReturnSniff implements Sniff
{

    /**
     * PHP version as configured or 0 if unknown.
     *
     * @since 1.1.0
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
        return [\T_FUNCTION];
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

        $scopePtr = Scopes::validDirectScope($phpcsFile, $stackPtr, Tokens::$ooScopeTokens);
        if ($scopePtr === false) {
            // Not an OO method.
            return;
        }

        $functionName   = FunctionDeclarations::getName($phpcsFile, $stackPtr);
        $functionNameLC = \strtolower($functionName);

        if ($functionNameLC === '__construct' || $functionNameLC === '__destruct') {
            $functionType = \sprintf('A "%s()" magic method', $functionNameLC);
        } else {
            // If the PHP version is explicitly set to PHP 8.0 or higher, ignore PHP 4-style constructors.
            if ($this->phpVersion >= 80000) {
                return;
            }

            // This may be a PHP 4-style constructor which should be handled.
            $OOName = ObjectDeclarations::getName($phpcsFile, $scopePtr);

            if (empty($OOName) === true) {
                // Anonymous class or parse error. The function can't be a PHP 4-style constructor.
                return;
            }

            if (NamingConventions::isEqual($functionName, $OOName) === false) {
                // Class and function name not the same, so not a PHP 4-style constructor.
                return;
            }

            if (Namespaces::determineNamespace($phpcsFile, $stackPtr) !== '') {
                /*
                 * Namespaced methods with the same name as the class are treated as
                 * regular methods, so we can bow out if we're in a namespace.
                 *
                 * Note: the exception to this is PHP 5.3.0-5.3.2. This is currently
                 * not dealt with.
                 */
                return;
            }

            $functionType = 'A PHP 4-style constructor';
        }

        /*
         * OK, so now we know for sure that this is a constructor/destructor method.
         */

        // Check for a return type.
        $tokens     = $phpcsFile->getTokens();
        $properties = FunctionDeclarations::getProperties($phpcsFile, $stackPtr);
        if ($properties['return_type'] !== '' && $properties['return_type_token'] !== false) {
            $data = [
                $functionType,
                $properties['return_type'],
            ];

            $fix = $phpcsFile->addFixableError(
                '%s can not declare a return type. Found: %s',
                $properties['return_type_token'],
                'ReturnTypeFound',
                $data
            );

            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();

                $parensCloser = $tokens[$stackPtr]['parenthesis_closer'];
                for ($i = ($parensCloser + 1); $i <= $properties['return_type_end_token']; $i++) {
                    if (isset(Tokens::$commentTokens[$tokens[$i]['code']])) {
                        // Ignore comments and leave them be.
                        continue;
                    }

                    $phpcsFile->fixer->replaceToken($i, '');
                }

                $phpcsFile->fixer->endChangeset();
            }
        }

        if (isset($tokens[$stackPtr]['scope_opener'], $tokens[$stackPtr]['scope_closer']) === false) {
            // Abstract/interface method, live coding or parse error.
            return;
        }

        // Check for a value being returned.
        $current = $tokens[$stackPtr]['scope_opener'];
        $end     = $tokens[$stackPtr]['scope_closer'];

        // Not searching for arrow functions as those have an implicit return, so won't use the `return` keyword.
        $search            = Collections::functionDeclarationTokens();
        $search[\T_RETURN] = \T_RETURN;

        do {
            $current = $phpcsFile->findNext($search, ($current + 1), $end);
            if ($current === false) {
                break;
            }

            if (isset(Collections::functionDeclarationTokens()[$tokens[$current]['code']])
                && isset($tokens[$current]['scope_closer'])
            ) {
                // Skip over nested function/closure declarations.
                $current = $tokens[$current]['scope_closer'];
                continue;
            }

            $next = $phpcsFile->findNext(Tokens::$emptyTokens, ($current + 1), $end, true);
            if ($next === false
                || $tokens[$next]['code'] === \T_SEMICOLON
                || $tokens[$next]['code'] === \T_CLOSE_TAG
            ) {
                // Return statement without value.
                continue;
            }

            $endOfStatement = BCFile::findEndOfStatement($phpcsFile, $next);

            $data = [
                $functionType,
                GetTokensAsString::compact($phpcsFile, $current, $endOfStatement, true),
            ];

            $phpcsFile->addWarning(
                '%s can not return a value. Found: "%s"',
                $current,
                'ReturnValueFound',
                $data
            );
        } while ($current < $end);
    }
}
