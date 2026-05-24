<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHPCSUtils\Utils\Namespaces;

/**
 * Disallow having more than one namespace declaration in a file.
 *
 * @since 1.0.0
 */
final class OneDeclarationPerFileSniff implements Sniff
{

    /**
     * Current file being scanned.
     *
     * @since 1.0.0
     *
     * @var string
     */
    private $currentFile;

    /**
     * Stack pointer to the first namespace declaration seen in the file.
     *
     * @since 1.0.0
     *
     * @var int|false
     */
    private $declarationSeen = false;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.0.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [\T_NAMESPACE];
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
        $fileName = $phpcsFile->getFilename();
        if ($this->currentFile !== $fileName) {
            // Reset the properties for each new file.
            $this->currentFile     = $fileName;
            $this->declarationSeen = false;
        }

        if (Namespaces::isDeclaration($phpcsFile, $stackPtr) === false) {
            // Namespace operator, not a declaration; or live coding/parse error.
            return;
        }

        if ($this->declarationSeen === false) {
            // This is the first namespace declaration in the file.
            $this->declarationSeen = $stackPtr;
            return;
        }

        $tokens = $phpcsFile->getTokens();

        // OK, so this is a file with multiple namespace declarations.
        $phpcsFile->addError(
            'There should be only one namespace declaration per file. The first declaration was found on line %d',
            $stackPtr,
            'MultipleFound',
            [$tokens[$this->declarationSeen]['line']]
        );
    }
}
