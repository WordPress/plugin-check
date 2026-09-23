## Sanitization Issues

A sanitization issue is user input data that is not sanitized before being stored or used.

Using the case as a reference, check the code to see if the case in question has been properly sanitized.

Details:
- Data from `$_POST`, `$_GET`, `$_REQUEST`, `$_SERVER`, `$_COOKIE` must be sanitized.
- Common sanitization functions: `sanitize_text_field()`, `sanitize_email()`, `sanitize_file_name()`, `sanitize_title()`, `sanitize_url()`, `absint()`, `intval()`, `wp_kses()`, `wp_kses_post()`.
- Type casting (`(int)`, `(float)`, `(bool)`) counts as sanitization for the respective types.
- `isset()` and `empty()` are NOT sanitization functions.
- `wp_unslash()` is NOT a sanitization function by itself.
- If the data is passed directly to a function that handles its own sanitization (e.g., `update_option()` with a registered sanitize callback), it may not be an issue.
- If the data is only used in a comparison (e.g., `if ( $_GET['action'] === 'delete' )`), the risk is lower but sanitization is still recommended.
- Array access on superglobals should also be sanitized.
- Passwords, tokens, secrets, signatures, and API keys are generally NOT eligible to be sanitized, as they can legitimately consist of any combination of characters. Sanitizing them can silently change their value (e.g., stripping characters), breaking authentication or comparisons. This is not an issue as long as the value is only stored (e.g., hashed with `wp_hash_password()`) or compared (e.g., with `wp_check_password()`, `hash_equals()`) and never displayed or used unsafely (e.g., in an unprepared SQL query or unescaped output).
- Look at the array index key (e.g., `password`, `pwd`, `pass`, `token`, `secret`, `signature`, `api_key`, or similar) as well as how the value is used to determine whether it falls into this category.
