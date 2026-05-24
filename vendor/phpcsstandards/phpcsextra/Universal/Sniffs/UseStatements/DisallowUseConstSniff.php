<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\UseStatements;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Exceptions\ValueError;
use PHPCSUtils\Utils\Namespaces;
use PHPCSUtils\Utils\UseStatements;

/**
 * Disallow constant import `use` statements.
 *
 * Related sniffs:
 * - `Universal.UseStatements.DisallowUseClass`
 * - `Universal.UseStatements.DisallowUseFunction`
 *
 * @since 1.0.0
 */
final class DisallowUseConstSniff implements Sniff
{

    /**
     * Name of the "Use import source" metric.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const METRIC_NAME_SRC = 'Use import statement source for constant';

    /**
     * Name of the "Use import with/without alias" metric.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const METRIC_NAME_ALIAS = 'Use import statement for constant';

    /**
     * Keep track of which file is being scanned.
     *
     * @since 1.0.0
     *
     * @var string
     */
    private $currentFile = '';

    /**
     * Keep track of the current namespace.
     *
     * @since 1.0.0
     *
     * @var string
     */
    private $currentNamespace = '';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.0.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [
            \T_USE,
            \T_NAMESPACE,
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
        $file = $phpcsFile->getFilename();
        if ($file !== $this->currentFile) {
            // Reset the current namespace for each new file.
            $this->currentFile      = $file;
            $this->currentNamespace = '';
        }

        $tokens = $phpcsFile->getTokens();

        // Get the name of the current namespace.
        if ($tokens[$stackPtr]['code'] === \T_NAMESPACE) {
            $namespaceName = Namespaces::getDeclaredName($phpcsFile, $stackPtr);
            if ($namespaceName !== false) {
                $this->currentNamespace = $namespaceName;
            }

            return;
        }

        // Ok, so this is a T_USE token.
        try {
            $statements = UseStatements::splitImportUseStatement($phpcsFile, $stackPtr);
        } catch (ValueError $e) {
            // Not an import use statement. Bow out.
            return;
        }

        if (empty($statements['const'])) {
            // No import statements for constants found.
            return;
        }

        $endOfStatement = $phpcsFile->findNext([\T_SEMICOLON, \T_CLOSE_TAG], ($stackPtr + 1));

        foreach ($statements['const'] as $alias => $qualifiedName) {
            /*
             * Determine which token to flag.
             *
             * - If there is an alias, the alias should be flagged.
             * - If there isn't an alias, the constant name at the end of the qualified name should be flagged
             *   on PHPCS 3.x and the complete qualified constant name on PHPCS 4.x.
             */
            $reportPtr = $stackPtr;
            do {
                $reportPtr = $phpcsFile->findNext(\T_STRING, ($reportPtr + 1), $endOfStatement, false, $alias);
                if ($reportPtr === false) {
                    // Shouldn't be possible on 3.x, but perfectly possible on 4.x when the class is not aliased.
                    break;
                }

                $next = $phpcsFile->findNext(Tokens::$emptyTokens, ($reportPtr + 1), $endOfStatement, true);
                if ($next !== false && $tokens[$next]['code'] === \T_NS_SEPARATOR) {
                    // Namespace level with same name. Continue searching.
                    continue;
                }

                break;
            } while (true);

            if ($reportPtr === false) {
                // Find the non-aliased name on PHPCS 4.x.
                $reportPtr = $phpcsFile->findNext(
                    [\T_NAME_QUALIFIED],
                    ($stackPtr + 1),
                    $endOfStatement,
                    false,
                    $qualifiedName
                );

                if ($reportPtr === false) {
                    // This may be an imported constant (incorrectly) passed as an FQN.
                    $reportPtr = $phpcsFile->findNext(
                        [\T_NAME_FULLY_QUALIFIED],
                        ($stackPtr + 1),
                        $endOfStatement,
                        false,
                        '\\' . $qualifiedName
                    );

                    if ($reportPtr === false) {
                        // This may be a partial name in a group use statement.
                        $groupStart = $phpcsFile->findNext(\T_OPEN_USE_GROUP, ($stackPtr + 1), $endOfStatement);
                        if ($groupStart === false) {
                            // Shouldn't be possible.
                            continue; // @codeCoverageIgnore
                        }

                        for ($i = ($groupStart + 1); $i < $endOfStatement; $i++) {
                            if ($tokens[$i]['code'] === \T_NAME_QUALIFIED
                                || $tokens[$i]['code'] === \T_NAME_FULLY_QUALIFIED
                            ) {
                                $contentLength = \strlen($tokens[$i]['content']);
                                if (\substr($qualifiedName, -$contentLength) === $tokens[$i]['content']) {
                                    $reportPtr = $i;
                                    break;
                                }
                            }
                        }

                        if ($reportPtr === false) {
                            // Shouldn't be possible.
                            continue; // @codeCoverageIgnore
                        }
                    }
                }
            }

            /*
             * Build the error message and code.
             *
             * Check whether this is a non-namespaced (global) import and check whether this is an
             * import from within the same namespace.
             *
             * Takes incorrect use statements with leading backslash into account.
             * Takes case-INsensitivity of namespaces names into account.
             *
             * The "GlobalNamespace" error code takes precedence over the "SameNamespace" error code
             * in case this is a non-namespaced file.
             */

            $error     = 'Use import statements for constants%s are not allowed.';
            $error    .= ' Found import statement for: "%s"';
            $errorCode = 'Found';
            $data      = [
                '',
                $qualifiedName,
            ];

            $globalNamespace = false;
            $sameNamespace   = false;
            if (\strpos($qualifiedName, '\\') === false) {
                $globalNamespace = true;
                $errorCode       = 'FromGlobalNamespace';
                $data[0]         = ' from the global namespace';

                $phpcsFile->recordMetric($reportPtr, self::METRIC_NAME_SRC, 'global namespace');
            } elseif ($this->currentNamespace !== ''
                && \stripos($qualifiedName, $this->currentNamespace . '\\') === 0
            ) {
                $sameNamespace = true;
                $errorCode     = 'FromSameNamespace';
                $data[0]       = ' from the same namespace';

                $phpcsFile->recordMetric($reportPtr, self::METRIC_NAME_SRC, 'same namespace');
            } else {
                $phpcsFile->recordMetric($reportPtr, self::METRIC_NAME_SRC, 'different namespace');
            }

            $hasAlias = false;
            $lastLeaf = \strtolower(\substr($qualifiedName, -(\strlen($alias) + 1)));
            $aliasLC  = \strtolower($alias);
            if ($lastLeaf !== $aliasLC && $lastLeaf !== '\\' . $aliasLC) {
                $hasAlias   = true;
                $error     .= ' with alias: "%s"';
                $errorCode .= 'WithAlias';
                $data[]     = $alias;

                $phpcsFile->recordMetric($reportPtr, self::METRIC_NAME_ALIAS, 'with alias');
            } else {
                $phpcsFile->recordMetric($reportPtr, self::METRIC_NAME_ALIAS, 'without alias');
            }

            if ($errorCode === 'Found') {
                $errorCode = 'FoundWithoutAlias';
            }

            $phpcsFile->addError($error, $reportPtr, $errorCode, $data);
        }
    }
}
