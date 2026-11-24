/**
 * Copies text to the clipboard using browser APIs.
 *
 * This is a custom helper and not a bundled copy of a third-party package.
 */
export function copyToClipboard( text ) {
	return navigator.clipboard.writeText( text );
}
