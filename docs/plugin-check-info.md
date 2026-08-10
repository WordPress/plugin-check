[Back to overview](./README.md)

# Plugin Check manifest

Plugin authors can add `plugin-check-info.json` to plugin root to identify bundled third-party code.

```json
{
    "third_parties": [
        "vendor/phpseclib",
        "libraries/legacy"
    ]
}
```

Plugin Check keeps errors from declared paths, but hides warning-level findings for those paths. This reduces recommendations intended for plugin authors, such as replacing a library's native PHP function with a WordPress wrapper, without hiding possible errors. Findings outside declared paths remain unchanged.

Manifest is committed with plugin code, so reviewers can inspect declarations. Missing, malformed, or invalid manifest entries are ignored. Paths are relative to plugin root and use `/` separators. Entries match their declared path and files below it, not similarly named paths.

## Visibility

Suppressed warnings are reported, not silently dropped. In CLI table output, Plugin Check shows a notice with the number of suppressed warnings. The AJAX check response includes the `suppressed_warnings` count.

## Opting out

You can disable manifest-based suppression to show all warnings, including from declared third-party paths:

- **CLI**: `wp plugin check <plugin> --ignore-third-party-warnings`.
- **REST/AJAX**: pass `ignore-third-party-warnings=1` in the check request body.
- **Programmatic**: return `true` from the `wp_plugin_check_ignore_third_party_warnings` filter after the runner is created:

```php
add_filter( 'wp_plugin_check_ignore_third_party_warnings', '__return_true' );
```
