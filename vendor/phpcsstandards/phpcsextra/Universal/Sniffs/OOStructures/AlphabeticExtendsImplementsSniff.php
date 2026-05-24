<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\OOStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\ObjectDeclarations;

/**
 * Verifies that the interface names used in a class/enum "implements" statement or an interface "extends" statement,
 * are listed in alphabetic order.
 *
 * @since 1.0.0
 */
final class AlphabeticExtendsImplementsSniff implements Sniff
{

    /**
     * Name of the "Alphabetically ordered" metric.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const METRIC_NAME_ALPHA = 'Interface names in implements/extends ordered alphabetically (%s)';

    /**
     * Name of the "interface count" metric.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const METRIC_NAME_COUNT = 'Number of interfaces being implemented/extended';

    /**
     * The sort order to use for the statement.
     *
     * If all names used are unqualified, the sort order won't make a difference.
     * However, if one or more of the names are partially or fully qualified, the chosen
     * sort order will determine how the sorting between unqualified, partially and
     * fully qualified names is handled.
     *
     * The sniff supports two sort order options:
     * - 'name' : sort by the interface name only (default);
     * - 'full' : sort by the full name as used in the statement (without leading backslash).
     *
     * In both cases, the sorting will be done using natural sort, case-insensitive.
     *
     * Example:
     * <code>
     *   class Foo implements \Vendor\DiffIterator, My\Count, DateTimeInterface {}
     * </code>
     *
     * If sorted using the "name" sort-order, the sniff looks just at the interface name, i.e.
     * `DiffIterator`, `Count` and `DateTimeInterface`, which for this example would mean
     * the correct order would be `My\Count, DateTimeInterface, \Vendor\DiffIterator`.
     *
     * If sorted using the "full" sort-order, the sniff will look at the full name as used
     * in the `implements` statement, without leading backslashes.
     * For the example above, this would mean that the correct order would be:
     * `DateTimeInterface, My\Count, \Vendor\DiffIterator`.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $orderby = 'name';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.0.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return (Collections::ooCanExtend() + Collections::ooCanImplement());
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token in
     *                                               the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        /*
         * Validate the setting.
         */
        if ($this->orderby !== 'full') {
            // Use the default.
            $this->orderby = 'name';
        }
        $metricNameAlpha = \sprintf(self::METRIC_NAME_ALPHA, $this->orderby);

        $tokens = $phpcsFile->getTokens();
        if (isset($tokens[$stackPtr]['scope_opener']) === false) {
            // Parse error or live coding. Ignore.
            return;
        }

        $scopeOpener = $tokens[$stackPtr]['scope_opener'];

        /*
         * Get the names.
         */
        if (isset(Collections::ooCanImplement()[$tokens[$stackPtr]['code']]) === true) {
            $names = ObjectDeclarations::findImplementedInterfaceNames($phpcsFile, $stackPtr);
        } else {
            $names = ObjectDeclarations::findExtendedInterfaceNames($phpcsFile, $stackPtr);
        }

        if (\is_array($names) === false) {
            // Class/interface/enum doesn't extend or implement.
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME_COUNT, 0);
            $phpcsFile->recordMetric($stackPtr, $metricNameAlpha, 'n/a');
            return;
        }

        $count = \count($names);
        $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME_COUNT, $count);

        if ($count < 2) {
            // Nothing to sort.
            $phpcsFile->recordMetric($stackPtr, $metricNameAlpha, 'n/a');
            return;
        }

        /*
         * Check the order.
         */
        if ($this->orderby === 'name') {
            $sorted = $this->sortByName($names);
        } else {
            $sorted = $this->sortByFull($names);
        }

        if ($sorted === $names) {
            // Order is already correct.
            $phpcsFile->recordMetric($stackPtr, $metricNameAlpha, 'yes');
            return;
        }

        $phpcsFile->recordMetric($stackPtr, $metricNameAlpha, 'no');

        /*
         * Throw the error.
         */
        $keyword = \T_IMPLEMENTS;
        if (isset(Collections::ooCanImplement()[$tokens[$stackPtr]['code']]) === false) {
            $keyword = \T_EXTENDS;
        }

        $fixable    = true;
        $keywordPtr = $phpcsFile->findNext($keyword, ($stackPtr + 1), $scopeOpener);
        $hasComment = $phpcsFile->findNext(Tokens::$commentTokens, ($keywordPtr + 1), $scopeOpener);
        if ($hasComment !== false) {
            $fixable = false;
        }

        $error  = "The interface names in a \"%s %s\" statement should be ordered alphabetically.\n";
        $error .= 'Expected: %s; Found: %s';
        $code   = \ucfirst(\strtolower($tokens[$keywordPtr]['content'])) . 'WrongOrder';
        $data   = [
            $tokens[$stackPtr]['content'],
            $tokens[$keywordPtr]['content'],
            \implode(', ', $sorted),
            \implode(', ', $names),
        ];

        if ($fixable === false) {
            $code .= 'WithComments';
            $phpcsFile->addError($error, $keywordPtr, $code, $data);
            return;
        }

        // OK, so we appear to have a fixable error.
        $fix = $phpcsFile->addFixableError($error, $keywordPtr, $code, $data);
        if ($fix === false) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();

        // Remove the complete previous extends/implements part.
        for ($i = ($keywordPtr + 1); $i < $scopeOpener; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->addContent($keywordPtr, ' ' . \implode(', ', $sorted) . ' ');

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Sort an array of potentially mixed qualified and unqualified names by the interface name.
     *
     * @since 1.0.0
     *
     * @param string[] $names Interface names, potentially mixed qualified and unqualified.
     *
     * @return string[]
     */
    protected function sortByName(array $names)
    {
        $getLastName = function ($name) {
            $last = \strrchr($name, '\\');
            if ($last === false) {
                $last = $name;
            } else {
                $last = \substr($last, 1);
            }

            return $last;
        };

        return $this->sortNames($names, $getLastName);
    }

    /**
     * Sort an array of potentially mixed qualified and unqualified names by the full name.
     *
     * @since 1.0.0
     *
     * @param string[] $names Interface names, potentially mixed qualified and unqualified.
     *
     * @return string[]
     */
    protected function sortByFull(array $names)
    {
        $trimLeadingBackslash = function ($name) {
            return \ltrim($name, '\\');
        };

        return $this->sortNames($names, $trimLeadingBackslash);
    }

    /**
     * Sort an array of names.
     *
     * @since 1.0.0
     *
     * @param string[] $names        Interface names, potentially mixed qualified and unqualified.
     * @param callable $prepareNames Function to call to prepare the names before sorting.
     *
     * @return string[]
     */
    private function sortNames(array $names, callable $prepareNames)
    {
        $preppedNames = \array_map($prepareNames, $names);
        $names        = \array_combine($names, $preppedNames);

        \natcasesort($names);

        return \array_keys($names);
    }
}
