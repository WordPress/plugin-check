[Back to overview](./README.md)

# wp plugin check

Runs plugin check.

## OPTIONS
```
<plugin>
: The plugin to check. Plugin name.

[--checks=<checks>]
: Only runs checks provided as an argument in comma-separated values, e.g. i18n_usage, late_escaping. Otherwise runs all checks.

[--exclude-checks=<checks>]
: Exclude checks provided as an argument in comma-separated values, e.g. i18n_usage, late_escaping.
Applies after evaluating `--checks`.

[--ignore-codes=<codes>]
: Ignore error codes provided as an argument in comma-separated values.

[--format=<format>]
: Format to display the results. Options are table, csv, json, ctrf, strict-table, strict-csv, strict-json, and strict-ctrf. The default will be a table.
---
default: table
options:
  - table
  - csv
  - json
  - ctrf
  - strict-table
  - strict-csv
  - strict-json
  - strict-ctrf
---

[--categories]
: Limit displayed results to include only specific categories Checks.

[--fields=<fields>]
: Limit displayed results to a subset of fields provided.

[--ignore-warnings]
: Limit displayed results to exclude warnings.

[--ignore-errors]
: Limit displayed results to exclude errors.

[--include-experimental]
: Include experimental checks.

[--exclude-directories=<directories>]
: Additional directories to exclude from checks.
By default, `.git`, `vendor`, `vendor_prefixed`, `vendor-prefixed` and `node_modules` directories are excluded.

[--exclude-files=<files>]
: Additional files to exclude from checks.

[--severity=<severity>]
: Severity level.

[--error-severity=<error-severity>]
: Error severity level.

[--warning-severity=<warning-severity>]
: Warning severity level.

[--include-low-severity-errors]
: Include errors with lower severity than the threshold as other type.

[--include-low-severity-warnings]
: Include warnings with lower severity than the threshold as other type.

[--slug=<slug>]
: Slug to override the default.

[--mode=<mode>]
: Mode to run the checks in. Options are 'new' (default) or 'update'.
---
default: new
options:
  - new
  - update
---
```
## EXAMPLES
```
wp plugin check akismet
wp plugin check akismet --checks=late_escaping
wp plugin check akismet --format=json
wp plugin check akismet --format=ctrf
wp plugin check akismet --mode=update
```

# wp plugin list-checks

Lists the available checks for plugins.

## OPTIONS
```
[--fields=<fields>]
: Limit displayed results to a subset of fields provided.

[--format=<format>]
: Format to display the results. Options are table, csv, and json. The default will be a table.
---
default: table
options:
  - table
  - csv
  - json
---

[--categories]
: Limit displayed results to include only specific categories.

[--include-experimental]
: Include experimental checks.
```
## EXAMPLES
```
wp plugin list-checks
wp plugin list-checks --format=json
```

# wp plugin list-check-categories

Lists the available check categories for plugins.

## OPTIONS
```
[--fields=<fields>]
: Limit displayed results to a subset of fields provided.

[--format=<format>]
: Format to display the results. Options are table, csv, and json. The default will be a table.
---
default: table
options:
  - table
  - csv
  - json
---
```
## EXAMPLES
```
wp plugin list-check-categories
wp plugin list-check-categories --format=json
```
