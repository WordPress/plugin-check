<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Helpers;

use PHP_CodeSniffer\Tokenizers\Tokenizer;

/**
 * Dummy tokenizer class to allow for accessing the replaceTabsInToken() method.
 *
 * @codeCoverageIgnore
 *
 * @since 1.0.0
 */
final class DummyTokenizer extends Tokenizer
{

    /**
     * Initialise and (don't) run the tokenizer.
     *
     * @param string                         $content The content to tokenize,
     * @param \PHP_CodeSniffer\Config | null $config  The config data for the run.
     * @param string                         $eolChar The EOL char used in the content.
     *
     * @return void
     */
    public function __construct($content, $config, $eolChar = '\n')
    {
        $this->eolChar = $eolChar;
        $this->config  = $config;
    }

    /**
     * Creates an array of tokens when given some content.
     *
     * @param string $code The code to tokenize.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function tokenize($code)
    {
        return [];
    }

    /**
     * Performs additional processing after main tokenizing.
     *
     * @return void
     */
    protected function processAdditional()
    {
    }
}
