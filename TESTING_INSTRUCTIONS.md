# Test Issue #1350

## Install
1. Download plugin-fix-1350.zip
2. WP Admin → Plugins → Upload
3. Activate

## Test
1. Run PHPCS on code using `empty()` or `isset()` on superglobals:
   ```php
   function verify() {
       if (empty($_REQUEST['g-recaptcha-response'])) {
           return false;
       }
   }
   ```
   **Expected**: No "Processing form data without nonce verification" warning

2. Run PHPCS on code with wrapper function:
   ```php
   function verifyNonce($key, $action = -1) {
       if(empty($_POST[$key])){
           return false;
       }
       return wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[$key])), $action);
   }
   ```
   **Expected**: No false positive warnings on the `empty()` check

3. Verify real violations still caught:
   ```php
   function bad() {
       $data = $_POST['data'];
   }
   ```
   **Expected**: Error "Processing form data without nonce verification"

## Requirements
- WP 5.2+, PHP 7.4+
