## Nonce Verification Issues

A nonce verification issue occurs when processing form submissions or AJAX requests without verifying a nonce, or when accessing `$_POST`, `$_GET`, `$_REQUEST` data without prior nonce verification.

Using the case as a reference, check the code to see if nonce verification is properly implemented.

Details:
- Nonce verification functions: `wp_verify_nonce()`, `check_admin_referer()`, `check_ajax_referer()`.
- Nonce verification should happen before processing any user input.
- If the code accesses `$_POST`, `$_GET`, or `$_REQUEST` but is only reading data for display (not processing/saving), it may be acceptable in some contexts.
- AJAX handlers should use `check_ajax_referer()` or `wp_verify_nonce()`.
- Form processing should use `check_admin_referer()` or `wp_verify_nonce()`.
- If the nonce check happens earlier in the same function or in a parent/calling function, it is not an issue.
- REST API endpoints use a different authentication mechanism and do not require nonces.
- If the code is in a REST API callback with a proper `permission_callback`, nonce verification is not required.
- Capability checks (`current_user_can()`) alone are not sufficient — nonces are still needed for form submissions.
