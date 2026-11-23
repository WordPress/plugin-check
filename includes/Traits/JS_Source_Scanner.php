<?php
/**
 * Trait WordPress\Plugin_Check\Traits\JS_Source_Scanner
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Traits;

/**
 * Trait for scanning JavaScript source for code while ignoring comments and string literals.
 *
 * The class using this trait must define the `WORD_CHARACTERS`, `WHITESPACE_CHARACTERS`,
 * `TOKEN_START_CHARACTERS` and `VALUE_EXPECTING_KEYWORDS` constants, because a trait cannot
 * declare constants before PHP 8.2.
 *
 * @since 2.3.0
 */
trait JS_Source_Scanner {

	/**
	 * Blanks out comments and literals in JavaScript contents.
	 *
	 * Characters inside line comments, block comments, quoted strings, and
	 * regular expression literals are replaced with spaces. The length of the
	 * contents and every newline are preserved, so match offsets still map to
	 * the correct line and column in the original file.
	 *
	 * The contents are tokenized rather than matched with a single regular
	 * expression, for two reasons. A regular expression cannot tell a regex
	 * literal from a division operator, so the quote in `/"/` was read as the
	 * start of a string and swallowed the code following it. PCRE also gives up
	 * on the long string literals of a bundled file, which left the contents
	 * unblanked and reported mentions in comments as calls.
	 *
	 * @since 2.3.0
	 *
	 * @param string $contents Contents of the JavaScript file.
	 * @return string The contents with comments and literals blanked out.
	 */
	private function blank_comments_and_strings( $contents ) {
		$length      = strlen( $contents );
		$blanked     = '';
		$copied      = 0;
		$offset      = 0;
		$after_value = false;

		while ( $offset < $length ) {
			// Whitespace does not change which token may come next.
			$offset += strspn( $contents, self::WHITESPACE_CHARACTERS, $offset );

			// Operators and punctuators all expect a value after them.
			$punctuation = strcspn( $contents, self::TOKEN_START_CHARACTERS, $offset );

			if ( $punctuation > 0 ) {
				$after_value = false;
				$offset     += $punctuation;
				continue;
			}

			if ( $offset >= $length ) {
				break;
			}

			// Identifiers, keywords, and numbers are consumed whole, so that the
			// slash in `return/^a$/.test( s )` is not taken for a division.
			$word = strspn( $contents, self::WORD_CHARACTERS, $offset );

			if ( $word > 0 ) {
				$after_value = ! in_array( substr( $contents, $offset, $word ), self::VALUE_EXPECTING_KEYWORDS, true );
				$offset     += $word;
				continue;
			}

			$char = $contents[ $offset ];

			if ( ')' === $char || ']' === $char ) {
				$after_value = true;
				++$offset;
				continue;
			}

			$end = $this->find_blank_end( $contents, $offset, $after_value );

			// A division operator, which leaves nothing to blank.
			if ( false === $end ) {
				++$offset;
				continue;
			}

			$blanked .= substr( $contents, $copied, $offset - $copied );
			$blanked .= preg_replace( '/[^\r\n]/', ' ', substr( $contents, $offset, $end - $offset ) );
			$copied   = $end;
			$offset   = $end;
		}

		return $blanked . substr( $contents, $copied );
	}

	/**
	 * Finds the offset just past the end of the comment or literal at an offset.
	 *
	 * A slash is the ambiguous case. It opens a comment when another slash or a
	 * star follows it, and otherwise divides when a value precedes it and opens
	 * a regular expression when one does not.
	 *
	 * @since 2.3.0
	 *
	 * @param string $contents    Contents being scanned.
	 * @param int    $offset      Offset of the opening character.
	 * @param bool   $after_value Whether a value precedes the offset, updated to
	 *                            describe what now precedes the returned offset.
	 * @return int|false Offset just past the comment or literal, or false when the
	 *                   character is a division operator and blanks nothing.
	 */
	private function find_blank_end( $contents, $offset, &$after_value ) {
		$length = strlen( $contents );
		$char   = $contents[ $offset ];
		$next   = $offset + 1 < $length ? $contents[ $offset + 1 ] : '';

		if ( '/' === $char && '/' === $next ) {
			return $offset + strcspn( $contents, "\r\n", $offset );
		}

		if ( '/' === $char && '*' === $next ) {
			$close = strpos( $contents, '*/', $offset + 2 );

			return false === $close ? $length : $close + 2;
		}

		if ( '/' === $char && $after_value ) {
			$after_value = false;

			return false;
		}

		// Either a quoted string, or a regular expression opened by the slash
		// that the checks above have left as the only reading.
		$after_value = true;

		return $this->find_literal_end( $contents, $offset, $char );
	}

	/**
	 * Finds the offset just past the end of a string or regex literal.
	 *
	 * @since 2.3.0
	 *
	 * @param string $contents  Contents being scanned.
	 * @param int    $start     Offset of the opening delimiter.
	 * @param string $delimiter The delimiter that closes the literal.
	 * @return int Offset just past the literal, or where it is cut short by the end
	 *             of the line or of the contents.
	 */
	private function find_literal_end( $contents, $start, $delimiter ) {
		$length = strlen( $contents );

		// Characters that interrupt the literal: an escape, its own delimiter,
		// and a line break for the literals that may not span lines. In a regular
		// expression a character class opens too, because the slash inside one
		// does not close the literal.
		if ( '/' === $delimiter ) {
			$stops = "\\/[\r\n";
		} elseif ( '`' === $delimiter ) {
			$stops = '\\`';
		} else {
			$stops = '\\' . $delimiter . "\r\n";
		}

		$offset = $start + 1;

		while ( $offset < $length ) {
			$offset += strcspn( $contents, $stops, $offset );

			if ( $offset >= $length ) {
				break;
			}

			$char = $contents[ $offset ];

			if ( '\\' === $char ) {
				$offset += 2;
				continue;
			}

			if ( $delimiter === $char ) {
				return $offset + 1;
			}

			if ( '[' === $char ) {
				$offset = $this->find_character_class_end( $contents, $offset );
				continue;
			}

			// Cut short by the end of the line.
			return $offset;
		}

		return $length;
	}

	/**
	 * Finds the offset just past the end of a regex character class.
	 *
	 * @since 2.3.0
	 *
	 * @param string $contents Contents being scanned.
	 * @param int    $start    Offset of the opening bracket.
	 * @return int Offset just past the character class, or where it is cut short by
	 *             the end of the line or of the contents.
	 */
	private function find_character_class_end( $contents, $start ) {
		$length = strlen( $contents );
		$offset = $start + 1;

		while ( $offset < $length ) {
			$offset += strcspn( $contents, "\\]\r\n", $offset );

			if ( $offset >= $length ) {
				break;
			}

			if ( '\\' === $contents[ $offset ] ) {
				$offset += 2;
				continue;
			}

			// Closed, or cut short by the end of the line.
			return ']' === $contents[ $offset ] ? $offset + 1 : $offset;
		}

		return $length;
	}

	/**
	 * Finds the first occurrence of a pattern and returns its line and column.
	 *
	 * All three line endings are recognized, and a carriage return followed by a
	 * line feed counts once. The line ending of the file being read is what
	 * matters here, which is unrelated to the one native to the machine running
	 * the check.
	 *
	 * @since 2.3.0
	 *
	 * @param string $pattern  The regular expression pattern to search for.
	 * @param string $contents The contents to search.
	 * @return array|false Array with `line` and `column` keys, or false if no match was found.
	 */
	private function find_first_match( $pattern, $contents ) {
		if ( ! preg_match( $pattern, $contents, $matches, PREG_OFFSET_CAPTURE ) ) {
			return false;
		}

		$before = substr( $contents, 0, $matches[0][1] );
		$lines  = preg_split( '/\r\n|\n|\r/', $before );

		return array(
			'line'   => count( $lines ),
			'column' => strlen( (string) end( $lines ) ) + 1,
		);
	}
}
