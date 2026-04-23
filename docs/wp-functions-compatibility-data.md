# WordPress Functions Compatibility Data

The `wp_functions_compatibility` check uses a generated dataset:

- `includes/Vars/wp-functions-since.json`

That file maps WordPress function names to the WordPress version where they were introduced (`@since`).
The check compares those versions with the plugin's minimum supported WordPress version (`Requires at least`).

## Regenerate Locally

From this plugin root:

```bash
php tools/generate-wp-function-since-data.php \
  --wordpress-dir=/absolute/path/to/wordpress \
  --output=includes/Vars/wp-functions-since.json
```

Example for this repository's standard local layout:

```bash
php tools/generate-wp-function-since-data.php \
  --wordpress-dir=../../../ \
  --output=includes/Vars/wp-functions-since.json
```

## Automation

The GitHub workflow:

- `.github/workflows/generate-wp-functions-since-data.yml`

regenerates this file on a schedule and via manual dispatch, and opens a pull request when the dataset changes.
