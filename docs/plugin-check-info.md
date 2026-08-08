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
