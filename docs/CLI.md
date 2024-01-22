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
: Additional directories to exclude from checks
By default, `.git`, `vendor` and `node_modules` directories are excluded.
```
## EXAMPLES
```
wp plugin check akismet
wp plugin check akismet --checks=late_escaping
wp plugin check akismet --format=json
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

