# Test PR #1292 — Personal Data Exporter Check (Experimental)

## Install
1. Download `plugin-check-pr-1292.zip`
2. WP Admin → Plugins → Add New → Upload Plugin
3. Upload the zip, click Install Now, then Activate

## What was changed

Reviewer (AndriusBurba) identified 3 issues + 2 minor in PR #1292. This build addresses all of them:

1. **`$wpdb` regex bug** — replaced regex-over-text with `token_get_all()` scanner. `\b\$wpdb` no longer relevant; tokens match `$wpdb->insert/update/replace` directly.
2. **False positives in comments and test files** — token scanner skips `T_COMMENT` / `T_DOC_COMMENT`. Path-anchored `tests/` exclusion now only filters tests *inside the plugin's own directory*, not the host project.
3. **Noisy on stable default** — check is now `Experimental_Check`. Opt in with `wp plugin-check check --include-experimental` or the corresponding admin toggle.
4. **`@since` tags** — bumped from `1.3.0` to `2.0.0` (trunk is 2.0.0).
5. **`docs/checks.md`** — new row added for `personal_data_exporter`.

Plus a 4th PHPUnit test for the new `$wpdb->insert` scenario, and a new testdata plugin `test-plugin-personal-data-exporter-with-wpdb-insert`.

## Test

1. **Plugin with `update_user_meta()` only** → expect `missing_personal_data_exporter` warning.
2. **Plugin with `update_user_meta()` AND `add_filter('wp_privacy_personal_data_exporters', ...)`** → expect no warning.
3. **Plugin with `$wpdb->insert()` only** → expect `missing_personal_data_exporter` warning.
4. **Plugin with no personal-data calls** → expect no warning.
5. **Plugin with `// update_user_meta()` in a comment only** → expect no warning.
6. **Plugin with `update_user_meta()` inside its own `tests/` subdir** → expect no warning.
7. **Default `wp plugin-check` run** → this check is excluded (experimental).
8. **`wp plugin-check check --include-experimental`** → this check runs.

Requirements: WP 6.3+, PHP 7.4+
