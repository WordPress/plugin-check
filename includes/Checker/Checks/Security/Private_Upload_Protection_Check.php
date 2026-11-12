<?php
/**
 * Class Private_Upload_Protection_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Security;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_File_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Experimental_Check;

/**
 * Advises authors when uploads and server-specific access rules occur together.
 */
class Private_Upload_Protection_Check extends Abstract_File_Check {

	use Amend_Check_Result;
	use Experimental_Check;

	/**
	 * Gets the categories for the check.
	 *
	 * @return array Check categories.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_SECURITY );
	}

	/**
	 * Checks for upload APIs and Apache/IIS-only denial rules without executing PHP.
	 *
	 * This heuristic cannot establish where files are stored or whether they are private.
	 *
	 * @param Check_Result $result Results to amend.
	 * @param array        $files  Absolute paths to plugin files.
	 */
	protected function check_files( Check_Result $result, array $files ) {
		$parser = ( new ParserFactory() )->create( ParserFactory::PREFER_PHP7 );
		$finder = new NodeFinder();
		foreach ( $files as $file ) {
			if ( 'php' !== strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ) ) {
				continue;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$contents = file_get_contents( $file );
			if ( false === $contents ) {
				continue;
			}

			try {
				$nodes = $parser->parse( $contents );
			} catch ( Error $error ) {
				// Syntax errors are handled by other checks; do not infer rules from invalid PHP.
				continue;
			}

			$calls       = $finder->findInstanceOf( $nodes ?? array(), Node\Expr\FuncCall::class );
			$has_uploads = false;
			$writes      = array();
			foreach ( $calls as $call ) {
				if ( ! $call instanceof Node\Expr\FuncCall || $call->isFirstClassCallable() ) {
					continue;
				}
				if ( ! $call->name instanceof Node\Name || $call->name->isQualified() || $call->name->isRelative() ) {
					continue;
				}
				$name = strtolower( $call->name->toString() );
				if ( in_array( $name, array( 'wp_upload_dir', 'wp_get_upload_dir', 'wp_upload_bits', 'wp_handle_upload', 'wp_handle_sideload', 'media_handle_upload', 'media_handle_sideload', '_wp_handle_upload', 'move_uploaded_file' ), true ) ) {
					$has_uploads = true;
				}
				if ( 'file_put_contents' !== $name || ! isset( $call->args[0], $call->args[1] ) || ! $call->args[0] instanceof Node\Arg || ! $call->args[1] instanceof Node\Arg || $call->args[0]->unpack || $call->args[1]->unpack || null !== $call->args[0]->name || null !== $call->args[1]->name ) {
					continue;
				}
				$path = $this->literal_path_suffix( $call->args[0]->value );
				$rule = $this->literal_string( $call->args[1]->value );
				if ( null !== $path && null !== $rule && $this->contains_deny_rule( $rule, basename( $path ) ) ) {
					$writes[] = $call->getStartLine();
				}
			}

			if ( ! $has_uploads ) {
				continue;
			}
			foreach ( $writes as $line ) {
				$this->add_result_warning_for_file(
					$result,
					__( 'Review upload privacy: this PHP file uses upload APIs and writes Apache or IIS access-denial rules. Nginx ignores .htaccess and web.config. If uploads are private, verify that direct URLs are blocked on every supported server, or store files outside the public web root and serve them through an authorized handler. This advisory does not establish that files are exposed.', 'plugin-check' ),
					'potentially_unprotected_private_uploads',
					$file,
					$line,
					0,
					$this->get_documentation_url()
				);
			}
		}
	}

	/**
	 * Reads only literal strings and their concatenations, without evaluating PHP.
	 *
	 * @param Node\Expr $expression Expression to inspect.
	 * @return string|null Known string, or null for dynamic expressions.
	 */
	private function literal_string( Node\Expr $expression ) {
		if ( $expression instanceof Node\Scalar\String_ ) {
			return $expression->value;
		}
		if ( $expression instanceof Node\Expr\BinaryOp\Concat ) {
			$left  = $this->literal_string( $expression->left );
			$right = $this->literal_string( $expression->right );
			if ( null !== $left && null !== $right ) {
				return $left . $right;
			}
		}
		return null;
	}

	/**
	 * Recognizes exact configuration basenames, allowing a dynamic directory prefix.
	 *
	 * @param Node\Expr $expression Filename expression.
	 * @return string|null Literal path or suffix identifying the configuration file.
	 */
	private function literal_path_suffix( Node\Expr $expression ) {
		$path = $this->literal_string( $expression );
		if ( null === $path && $expression instanceof Node\Expr\BinaryOp\Concat ) {
			$path = $this->literal_string( $expression->right );
			// A separator prevents a dynamic prefix from changing the basename.
			if ( null === $path || '/' !== substr( $path, 0, 1 ) ) {
				return null;
			}
		}
		return null !== $path && in_array( basename( $path ), array( '.htaccess', 'web.config' ), true ) ? $path : null;
	}

	/**
	 * Recognizes unscoped Apache denial lines or broad IIS authorization denial.
	 *
	 * @param string $contents Literal configuration text.
	 * @param string $filename Configuration basename.
	 * @return bool Whether a supported access-denial rule was found.
	 */
	private function contains_deny_rule( $contents, $filename ) {
		if ( '.htaccess' === $filename ) {
			// Ignore comments before removing file-scoped rules, including unclosed sections.
			$contents = preg_replace( '/^[ \t]*#.*$/m', '', $contents );
			$contents = preg_replace( '~<Files(?:Match)?\b[^>]*>.*?(?:</Files(?:Match)?\s*>|\z)~is', '', $contents );
			return (bool) preg_match( '/^[ \t]*(?:Deny[ \t]+from[ \t]+all|Require[ \t]+all[ \t]+denied)[ \t]*(?:#.*)?\r?$/im', $contents );
		}

		$contents = preg_replace( '~<!--.*?(?:-->|\z)~s', '', $contents );
		// Scoped IIS rules (for example PHP-only locations) do not establish directory privacy.
		$contents = preg_replace( '~<location\b[^>]*>.*?(?:</location\s*>|\z)~is', '', $contents );
		if ( ! preg_match_all( '~<authorization\b[^>]*>(.*?)</authorization\s*>~is', $contents, $sections ) ) {
			return false;
		}
		foreach ( $sections[1] as $section ) {
			if ( ! preg_match_all( '~<(deny|add)\b[^>]*/>~i', $section, $rules, PREG_SET_ORDER ) ) {
				continue;
			}
			foreach ( $rules as $match ) {
				$rule = $match[0];
				if ( 'add' === strtolower( $match[1] ) && ! preg_match( '~\saccessType\s*=\s*([\'"])Deny\1~i', $rule ) ) {
					continue;
				}
				if ( preg_match( '~\susers\s*=\s*([\'"])[*?]\1~i', $rule ) && ! preg_match( '~\s(?:roles|verbs)\s*=~i', $rule ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Gets a description of the check.
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return __( 'Advises authors to verify private upload protection across web servers.', 'plugin-check' );
	}

	/**
	 * Gets documentation explaining the limitations of server-specific rules.
	 *
	 * @return string Documentation URL.
	 */
	public function get_documentation_url(): string {
		return 'https://github.com/WordPress/plugin-check/blob/trunk/docs/checks.md#private-upload-protection-experimental';
	}
}
