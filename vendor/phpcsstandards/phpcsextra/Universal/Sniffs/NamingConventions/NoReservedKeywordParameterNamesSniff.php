<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\NamingConventions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\FunctionDeclarations;

/**
 * Verifies that parameters in function declarations do not use PHP reserved keywords
 * as this can lead to confusing code when using PHP 8.0+ named parameters in function calls.
 *
 * Note: while parameters (variables) are case-sensitive in PHP, keywords are not,
 * so this sniff checks for the keywords used in parameter names in a
 * case-insensitive manner to make this sniff independent of code style rules
 * regarding the case for parameter names.
 *
 * @link https://www.php.net/manual/en/reserved.keywords.php
 *
 * @since 1.0.0
 */
final class NoReservedKeywordParameterNamesSniff implements Sniff
{

    /**
     * A list of PHP reserved keywords.
     *
     * @since 1.0.0
     *
     * @var array<string, string> Key is the lowercased keyword, value the "proper" cased keyword.
     */
    private $reservedNames = [
        'abstract'      => 'abstract',
        'and'           => 'and',
        'array'         => 'array',
        'as'            => 'as',
        'break'         => 'break',
        'callable'      => 'callable',
        'case'          => 'case',
        'catch'         => 'catch',
        'class'         => 'class',
        'clone'         => 'clone',
        'const'         => 'const',
        'continue'      => 'continue',
        'declare'       => 'declare',
        'default'       => 'default',
        'die'           => 'die',
        'do'            => 'do',
        'echo'          => 'echo',
        'else'          => 'else',
        'elseif'        => 'elseif',
        'empty'         => 'empty',
        'enddeclare'    => 'enddeclare',
        'endfor'        => 'endfor',
        'endforeach'    => 'endforeach',
        'endif'         => 'endif',
        'endswitch'     => 'endswitch',
        'endwhile'      => 'endwhile',
        'enum'          => 'enum',
        'eval'          => 'eval',
        'exit'          => 'exit',
        'extends'       => 'extends',
        'final'         => 'final',
        'finally'       => 'finally',
        'fn'            => 'fn',
        'for'           => 'for',
        'foreach'       => 'foreach',
        'function'      => 'function',
        'global'        => 'global',
        'goto'          => 'goto',
        'if'            => 'if',
        'implements'    => 'implements',
        'include'       => 'include',
        'include_once'  => 'include_once',
        'instanceof'    => 'instanceof',
        'insteadof'     => 'insteadof',
        'interface'     => 'interface',
        'isset'         => 'isset',
        'list'          => 'list',
        'match'         => 'match',
        'namespace'     => 'namespace',
        'new'           => 'new',
        'or'            => 'or',
        'print'         => 'print',
        'private'       => 'private',
        'protected'     => 'protected',
        'public'        => 'public',
        'readonly'      => 'readonly',
        'require'       => 'require',
        'require_once'  => 'require_once',
        'return'        => 'return',
        'static'        => 'static',
        'switch'        => 'switch',
        'throw'         => 'throw',
        'trait'         => 'trait',
        'try'           => 'try',
        'unset'         => 'unset',
        'use'           => 'use',
        'var'           => 'var',
        'while'         => 'while',
        'xor'           => 'xor',
        'yield'         => 'yield',
        '__class__'     => '__CLASS__',
        '__dir__'       => '__DIR__',
        '__file__'      => '__FILE__',
        '__function__'  => '__FUNCTION__',
        '__line__'      => '__LINE__',
        '__method__'    => '__METHOD__',
        '__namespace__' => '__NAMESPACE__',
        '__trait__'     => '__TRAIT__',
        'int'           => 'int',
        'float'         => 'float',
        'bool'          => 'bool',
        'string'        => 'string',
        'true'          => 'true',
        'false'         => 'false',
        'null'          => 'null',
        'void'          => 'void',
        'iterable'      => 'iterable',
        'object'        => 'object',
        'resource'      => 'resource',
        'mixed'         => 'mixed',
        'numeric'       => 'numeric',
        'never'         => 'never',

        /*
         * Not reserved keywords, but equally confusing when used in the context of function calls
         * with named parameters.
         */
        'parent'        => 'parent',
        'self'          => 'self',
    ];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.0.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return Collections::functionDeclarationTokens();
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
        // Get all parameters from method signature.
        $parameters = FunctionDeclarations::getParameters($phpcsFile, $stackPtr);
        if (empty($parameters)) {
            return;
        }

        $message = 'It is recommended not to use reserved keyword "%s" as function parameter name. Found: %s';

        foreach ($parameters as $param) {
            $name   = \ltrim($param['name'], '$');
            $nameLC = \strtolower($name);
            if (isset($this->reservedNames[$nameLC]) === true) {
                $errorCode = $nameLC . 'Found';
                $data      = [
                    $this->reservedNames[$nameLC],
                    $param['name'],
                ];

                $phpcsFile->addWarning($message, $param['token'], $errorCode, $data);
            }
        }
    }
}
