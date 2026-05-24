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
 * Require that an attribute instantiation has parentheses, i.e. `#[MyAttribute()]`.
 *
 * @since 1.5.0
 */
final class RequireAttributeParenthesesSniff implements Sniff
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
            if ($tokens[$nextNonEmpty]['code'] === \T_OPEN_PARENTHESIS) {
                // Parentheses found.
                $phpcsFile->recordMetric($attribute['name_token'], self::METRIC_NAME, 'yes');
                continue;
            }

            $phpcsFile->recordMetric($attribute['name_token'], self::METRIC_NAME, 'no');

            $fix = $phpcsFile->addFixableError(
                'Parentheses required when instantiating an attribute class.',
                $attribute['name_token'],
                'Missing'
            );

            if ($fix === true) {
                $phpcsFile->fixer->addContent($attribute['name_token'], '()');
            }
        }
    }
}
