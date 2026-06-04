# Test PR #1292 — Personal Data Exporter Check (PHPMD Complexity Fix)

## Install
1. Download `plugin-check-pr-1292.zip`
2. WP Admin → Plugins → Add New → Upload Plugin
3. Upload the zip, click Install Now, then Activate

## What was changed
CI PHPMD complexity failure resolved. Extracted token-matching logic into dedicated helpers to reduce NPath complexity below 200 threshold.

1. **Extracted `is_personal_data_function_call()`** — checks `T_STRING` match + global call + `(`.
2. **Extracted `is_wpdb_method_call()`** — checks `$wpdb` + `->` + `T_STRING` method + array match.
3. **Extracted `is_exporter_filter_registration()`** — checks `add_filter` + global call + `(` + string arg + match.

All previous functionality (token scanner, test-exclusion, experimental trait, @since fix, docs row, 4th test) remains intact.

## Verify
1. Run `composer phpmd` — 0 complexity errors.
2. Run `npm run test-php` — 476 tests pass.
3. Run `composer lint` and `composer phpstan` — 0 errors.

## Requirements
WordPress 6.3+ · PHP 7.4+