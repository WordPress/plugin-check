<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\Attributes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\AttributeBlock;

/**
 * Forbid for an attribute instantiation to have parentheses, except when
 * parameters are being passed.
 *
 * @since 1.5.0
 */
final class DisallowAttributeParenthesesSniff implements Sniff
{

    /**
     * Name of the metric.
     *
     * @since 1.5.0
     *
     * @var string
     */
    const METRIC_NAME = 'Attribute instantiation with parentheses';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.5.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [\T_ATTRIBUTE];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @since 1.5.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $instantiations = AttributeBlock::getAttributes($phpcsFile, $stackPtr);
        if (empty($instantiations)) {
            return;
        }

        $tokens = $phpcsFile->getTokens();

        foreach ($instantiations as $attribute) {
            $nextNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, ($attribute['name_token'] + 1), null, true);

            // Note: no need to check for `false` as there will always be something after, if only the attribute closer.
            if ($tokens[$nextNonEmpty]['code'] !== \T_OPEN_PARENTHESIS) {
                // No parentheses found.
                $phpcsFile->recordMetric($attribute['name_token'], self::METRIC_NAME, 'no');
                continue;
            }

            if (isset($tokens[$nextNonEmpty]['parenthesis_closer']) === false) {
                /*
                 * Incomplete set of parentheses. Ignore.
                 * Shouldn't be possible as PHPCS won't have matched the attribute opener with the closer in that case.
                 */
                // @codeCoverageIgnoreStart
                $phpcsFile->recordMetric($attribute['name_token'], self::METRIC_NAME, 'yes');
                continue;
                // @codeCoverageIgnoreEnd
            }

            $opener    = $nextNonEmpty;
            $closer    = $tokens[$opener]['parenthesis_closer'];
            $hasParams = $phpcsFile->findNext(Tokens::$emptyTokens, ($opener + 1), $closer, true);
            if ($hasParams !== false) {
                // There is something between the parentheses. Ignore.
                $phpcsFile->recordMetric($attribute['name_token'], self::METRIC_NAME, 'yes, with parameter(s)');
                continue;
            }

            $phpcsFile->recordMetric($attribute['name_token'], self::METRIC_NAME, 'yes');

            $fix = $phpcsFile->addFixableError(
                'Parentheses not allowed when instantiating an attribute class without passing parameters',
                $opener,
                'Found'
            );

            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();

                for ($i = $opener; $i <= $closer; $i++) {
                    if (isset(Tokens::$commentTokens[$tokens[$i]['code']]) === true) {
                        continue;
                    }

                    $phpcsFile->fixer->replaceToken($i, '');
                }

                $phpcsFile->fixer->endChangeset();
            }
        }
    }
}
