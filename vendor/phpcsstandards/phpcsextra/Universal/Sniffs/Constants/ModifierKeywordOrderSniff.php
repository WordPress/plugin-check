<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2022 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\Constants;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHPCSUtils\Utils\Constants;
use PHPCSUtils\Utils\Scopes;

/**
 * Standardize the modifier keyword order for OO constant declarations.
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
    const METRIC_NAME = 'OO constant modifier keyword order';

    /**
     * Order preference: final visibility.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const FINAL_VISIBILITY = 'final visibility';

    /**
     * Order preference: visibility final.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const VISIBILITY_FINAL = 'visibility final';

    /**
     * Preferred order for the modifier keywords.
     *
     * Accepted values:
     * - "final visibility".
     * - or "visibility final".
     *
     * Defaults to "final visibility".
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $order = self::FINAL_VISIBILITY;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.0.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [\T_CONST];
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
        if (Scopes::isOOConstant($phpcsFile, $stackPtr) === false) {
            return;
        }

        $constantInfo = Constants::getProperties($phpcsFile, $stackPtr);

        if ($constantInfo['final_token'] === false || $constantInfo['scope_token'] === false) {
            /*
             * Either no modifier keywords found at all; or only one type of modifier
             * keyword (final or visibility) declared, but not both. No ordering needed.
             */
            return;
        }

        $finalPtr      = $constantInfo['final_token'];
        $visibilityPtr = $constantInfo['scope_token'];

        if ($visibilityPtr < $finalPtr) {
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, self::VISIBILITY_FINAL);
        } else {
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, self::FINAL_VISIBILITY);
        }

        $message = 'OO constant modifier keywords are not in the correct order. Expected: "%s", found: "%s"';

        switch ($this->order) {
            case self::VISIBILITY_FINAL:
                if ($visibilityPtr < $finalPtr) {
                    // Order is correct. Nothing to do.
                    return;
                }

                $this->handleError($phpcsFile, $finalPtr, $visibilityPtr);
                break;

            case self::FINAL_VISIBILITY:
            default:
                if ($finalPtr < $visibilityPtr) {
                    // Order is correct. Nothing to do.
                    return;
                }

                $this->handleError($phpcsFile, $visibilityPtr, $finalPtr);
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

        $message = 'Constant modifier keywords are not in the correct order. Expected: "%s", found: "%s"';
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
