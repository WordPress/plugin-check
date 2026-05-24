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
use PHPCSUtils\Utils\GetTokensAsString;
use PHPCSUtils\Utils\UseStatements;

/**
 * Disallow group use statements which combine imports for namespace/OO, functions
 * and/or constants in one statement.
 *
 * Note: the fixer will use a semi-standardized format for group use statements.
 * If there are more specific requirements for the formatting of group use statements,
 * the ruleset configurator should ensure that additional sniffs are included in the
 * ruleset to enforce the required format.
 *
 * @since 1.1.0
 */
final class DisallowMixedGroupUseSniff implements Sniff
{

    /**
     * Name of the "Use import source" metric.
     *
     * @since 1.1.0
     *
     * @var string
     */
    const METRIC_NAME = 'Import use statement type';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.1.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [\T_USE];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @since 1.1.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        if (UseStatements::isImportUse($phpcsFile, $stackPtr) === false) {
            // Closure or trait use statement. Bow out.
            return;
        }

        $useStatements = UseStatements::splitImportUseStatement($phpcsFile, $stackPtr);

        $ooCount       = \count($useStatements['name']);
        $functionCount = \count($useStatements['function']);
        $constantCount = \count($useStatements['const']);
        $totalCount    = $ooCount + $functionCount + $constantCount;

        if ($totalCount === 0) {
            // There must have been a parse error. Bow out.
            return;
        }

        // End of statement will always be found, otherwise the import statement parsing would have failed.
        $endOfStatement = $phpcsFile->findNext([\T_SEMICOLON, \T_CLOSE_TAG], ($stackPtr + 1));
        $groupStart     = $phpcsFile->findNext(\T_OPEN_USE_GROUP, ($stackPtr + 1), $endOfStatement);

        if ($groupStart === false) {
            // Not a group use statement. Just record the metric.
            if ($totalCount === 1) {
                $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'single import');
            } else {
                $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'multi import');
            }

            return;
        }

        if ($totalCount === 1
            || ($ooCount !== 0 && $functionCount === 0 && $constantCount === 0)
            || ($ooCount === 0 && $functionCount !== 0 && $constantCount === 0)
            || ($ooCount === 0 && $functionCount === 0 && $constantCount !== 0)
        ) {
            // Not a *mixed* group use statement.
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'group use, single type');
            return;
        }

        $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'group use, multi type');

        // Build up the error message.
        $foundPhrases = [];
        if ($ooCount > 1) {
            $foundPhrases[] = \sprintf('%d namespaces/OO names', $ooCount);
        } elseif ($ooCount === 1) {
            $foundPhrases[] = \sprintf('%d namespace/OO name', $ooCount);
        }

        if ($functionCount > 1) {
            $foundPhrases[] = \sprintf('%d functions', $functionCount);
        } elseif ($functionCount === 1) {
            $foundPhrases[] = \sprintf('%d function', $functionCount);
        }

        if ($constantCount > 1) {
            $foundPhrases[] = \sprintf('%d constants', $constantCount);
        } elseif ($constantCount === 1) {
            $foundPhrases[] = \sprintf('%d constant', $constantCount);
        }

        if (\count($foundPhrases) === 2) {
            $found = \implode(' and ', $foundPhrases);
        } else {
            $found  = \array_shift($foundPhrases) . ', ';
            $found .= \implode(' and ', $foundPhrases);
        }

        $error = 'Group use statements should import one type of construct.'
            . ' Mixed group use statement found importing %s.';
        $code  = 'Found';
        $data  = [$found];

        $hasComment = $phpcsFile->findNext(Tokens::$commentTokens, ($stackPtr + 1), $endOfStatement);
        if ($hasComment !== false) {
            // Don't attempt to auto-fix is there are comments or PHPCS annotations in the statement.
            $phpcsFile->addError($error, $stackPtr, $code, $data);
            return;
        }

        $fix = $phpcsFile->addFixableError($error, $stackPtr, $code, $data);

        if ($fix === false) {
            return;
        }

        /*
         * Fix it.
         *
         * This fixer complies with the following (arbitrary) requirements:
         * - It will re-use the original base "group" name, i.e. the part before \{,
         *   though it will remove a potential leading backslash from it.
         * - It takes aliases into account, but only when something is aliased to a different name.
         *   Aliases re-using the original name will be removed.
         * - The fix will not add a trailing comma after the last group use sub-statement.
         *   This is a PHP 7.2+ feature.
         *   If a standard wants to enforce trailing commas, they should use a separate sniff for that.
         * - If there is only 1 statement of a certain type, the replacement will be a single
         *   import use statement, not a group use statement.
         * - If any of the partial use statements within a group contain leading backslashes,
         *   the sniff will not correct for this. This is a parse error and the fixed version will
         *   still contain a parse error.
         */

        $phpcsFile->fixer->beginChangeset();

        // Ensure that a potential close PHP tag ending the statement is not removed.
        $tokens     = $phpcsFile->getTokens();
        $endRemoval = $endOfStatement;
        if ($tokens[$endOfStatement]['code'] !== \T_SEMICOLON) {
            $endRemoval = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($endOfStatement - 1), null, true);
        }

        // Remove old statement with the exception of the `use` keyword.
        for ($i = ($stackPtr + 1); $i <= $endRemoval; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        // Build up the new use import statements.
        $newStatements = [];

        $useIndent    = \str_repeat(' ', ($tokens[$stackPtr]['column'] - 1));
        $insideIndent = $useIndent . \str_repeat(' ', 4);

        $baseGroupName = \ltrim(GetTokensAsString::noEmpties($phpcsFile, ($stackPtr + 1), ($groupStart - 1)), '\\');

        foreach ($useStatements as $type => $statements) {
            $count = \count($statements);
            if ($count === 0) {
                continue;
            }

            $typeName = $type . ' ';
            if ($type === 'name') {
                $typeName = '';
            }

            if ($count === 1) {
                $qualifiedName = \reset($statements);
                $alias         = \key($statements);

                $newStatement = 'use ' . $typeName . $qualifiedName;

                $unqualifiedName = \ltrim(\substr($qualifiedName, (int) \strrpos($qualifiedName, '\\')), '\\');
                if ($unqualifiedName !== $alias) {
                    $newStatement .= ' as ' . $alias;
                }

                $newStatement .= ';';

                $newStatements[] = $newStatement;
                continue;
            }

            // Multiple statements, add a single-type group use statement.
            $newStatement = 'use ' . $typeName . $baseGroupName . '{' . $phpcsFile->eolChar;

            foreach ($statements as $alias => $qualifiedName) {
                $partialName   = \str_replace($baseGroupName, '', $qualifiedName);
                $newStatement .= $insideIndent . $partialName;

                $unqualifiedName = \ltrim(\substr($partialName, (int) \strrpos($partialName, '\\')), '\\');
                if ($unqualifiedName !== $alias) {
                    $newStatement .= ' as ' . $alias;
                }

                $newStatement .= ',' . $phpcsFile->eolChar;
            }

            // Remove trailing comma after last statement as that's PHP 7.2+.
            $newStatement = \rtrim($newStatement, ',' . $phpcsFile->eolChar);

            $newStatement   .= $phpcsFile->eolChar . $useIndent . '};';
            $newStatements[] = $newStatement;
        }

        $replacement = \implode($phpcsFile->eolChar . $useIndent, $newStatements);

        $phpcsFile->fixer->replaceToken($stackPtr, $replacement);

        $phpcsFile->fixer->endChangeset();
    }
}
