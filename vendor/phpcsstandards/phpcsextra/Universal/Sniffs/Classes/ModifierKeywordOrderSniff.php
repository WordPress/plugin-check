<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2022 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHPCSUtils\Utils\ObjectDeclarations;

/**
 * Standardize the modifier keyword order for class declarations.
 *
 * @since 1.0.0
 */
final class ModifierKeywordOrderSniff implements Sniff
{

    /**
     * Name of the metric.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const METRIC_NAME = 'Class modifier keyword order';

    /**
     * Order preference: abstract/final readonly.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const EXTEND_READONLY = 'extendability readonly';

    /**
     * Order preference: readonly abstract/final.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const READONLY_EXTEND = 'readonly extendability';

    /**
     * Preferred order for the modifier keywords.
     *
     * Accepted values:
     * - "extendability readonly".
     * - or "readonly extendability".
     *
     * Defaults to "extendability readonly".
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $order = self::EXTEND_READONLY;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.0.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [\T_CLASS];
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
        $classProp = ObjectDeclarations::getClassProperties($phpcsFile, $stackPtr);

        if ($classProp['readonly_token'] === false
            || ($classProp['final_token'] === false && $classProp['abstract_token'] === false)
        ) {
            /*
             * Either no modifier keywords found at all; or only one type of modifier
             * keyword (abstract/final or readonly) declared, but not both. No ordering needed.
             */
            return;
        }

        if ($classProp['final_token'] !== false && $classProp['abstract_token'] !== false) {
            // Parse error. Ignore.
            return;
        }

        $readonly = $classProp['readonly_token'];

        if ($classProp['final_token'] !== false) {
            $extendability = $classProp['final_token'];
        } else {
            $extendability = $classProp['abstract_token'];
        }

        if ($readonly < $extendability) {
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, self::READONLY_EXTEND);
        } else {
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, self::EXTEND_READONLY);
        }

        $message = 'Class modifier keywords are not in the correct order. Expected: "%s", found: "%s"';

        switch ($this->order) {
            case self::READONLY_EXTEND:
                if ($readonly < $extendability) {
                    // Order is correct. Nothing to do.
                    return;
                }

                $this->handleError($phpcsFile, $extendability, $readonly);
                break;

            case self::EXTEND_READONLY:
            default:
                if ($extendability < $readonly) {
                    // Order is correct. Nothing to do.
                    return;
                }

                $this->handleError($phpcsFile, $readonly, $extendability);
                break;
        }
    }

    /**
     * Throw the error and potentially fix it.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile     The file being scanned.
     * @param int                         $firstKeyword  The position of the first keyword found.
     * @param int                         $secondKeyword The position of the second keyword token.
     *
     * @return void
     */
    private function handleError(File $phpcsFile, $firstKeyword, $secondKeyword)
    {
        $tokens = $phpcsFile->getTokens();

        $message = 'Class modifier keywords are not in the correct order. Expected: "%s", found: "%s"';
        $data    = [
            $tokens[$secondKeyword]['content'] . ' ' . $tokens[$firstKeyword]['content'],
            $tokens[$firstKeyword]['content'] . ' ' . $tokens[$secondKeyword]['content'],
        ];

        $fix = $phpcsFile->addFixableError($message, $firstKeyword, 'Incorrect', $data);

        if ($fix === true) {
            $phpcsFile->fixer->beginChangeset();

            $phpcsFile->fixer->replaceToken($secondKeyword, '');

            // Prevent leaving behind trailing whitespace.
            $i = ($secondKeyword + 1);
            while ($tokens[$i]['code'] === \T_WHITESPACE) {
                $phpcsFile->fixer->replaceToken($i, '');
                ++$i;
            }

            // Use the original token content as the case used for keywords is not the concern of this sniff.
            $phpcsFile->fixer->addContentBefore($firstKeyword, $tokens[$secondKeyword]['content'] . ' ');

            $phpcsFile->fixer->endChangeset();
        }
    }
}
