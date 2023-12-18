[Back to overview](./README.md)

# wp plugin check

Runs plugin check.

## Options

| Argument                                    | Description                                                                                                                                     |
| ------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| **\<plugin\>**                              | The plugin to check. Plugin name.                                                                                                               |
| **[--checks=\<checks\>]**                   | Only runs checks provided as an argument in comma-separated values, e.g. `i18n_usage,late_escaping`. Otherwise runs all checks.                 |
| **[--exclude-checks=\<checks\>]**           | Exclude checks provided as an argument in comma-separated values, e.g. `i18n_usage,late_escaping`. Applies after evaluating `--checks`.         |
| **[--format=\<format\>]**                   | Format to display the results. Default: `table`. Options: `table`, `csv`, `json`                                                                |
| **[--categories]**                          | Limit displayed results to include only specific categories Checks.                                                                             |
| **[--fields=\<fields\>]**                   | Limit displayed results to a subset of fields provided.                                                                                         |
| **[--ignore-warnings]**                     | Limit displayed results to exclude warnings.                                                                                                    |
| **[--ignore-errors]**                       | Limit displayed results to exclude errors.                                                                                                      |
| **[--include-experimental]**                | Include experimental checks.                                                                                                                    |
| **[--exclude-directories=\<directories\>]** | Additional directories to exclude from checks. By default, `.git`, `vendor` and `node_modules` directories are excluded.                        |

## Examples

`wp plugin check akismet`

Runs plugin checks with default argument values.

`wp plugin check akismet --checks=late_escaping`

Runs plugin checks for `late_escaping` only.

`wp plugin check akismet --format=json`

Runs plugin checks and generates report in `JSON` format.
