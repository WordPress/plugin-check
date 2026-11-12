[Back to overview](./README.md)

# Available Checks

| Check | Category | Description | Documentation |
| --- | --- | --- | --- |
| i18n_usage | general, plugin_repo | Checks for various internationalization best practices. | [Learn more](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/) |
| code_obfuscation | plugin_repo | Detects the usage of code obfuscation tools. | [Learn more](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) |
| plugin_content | plugin_repo | Detects content that does not comply with the WordPress.org plugin guidelines. | [Learn more](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) |
| private_upload_protection | security | Experimental advisory for upload API calls and generated server-specific denial rules in the same PHP file. Requires `--include-experimental`. | [Learn more](#private-upload-protection-experimental) |
| direct_file_access | security, plugin_repo | Checks that plugin files include proper security validation using the ABSPATH constant to prevent direct file access. | [Learn more](https://developer.wordpress.org/plugins/plugin-basics/best-practices/#file-security) |
| file_type | plugin_repo | Detects the usage of hidden and compressed files, VCS directories, application files, badly named files, AI development directories (.cursor, .claude, .aider, .continue, .windsurf, .ai, .github), and unexpected markdown files in plugin root. | [Learn more](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) |
| plugin_header_fields | plugin_repo | Checks adherence to the Headers requirements. | [Learn more](https://developer.wordpress.org/plugins/plugin-basics/header-requirements/) |
| late_escaping | security, plugin_repo | Checks that all output is escaped before being sent to the browser. | [Learn more](https://developer.wordpress.org/apis/security/escaping/) |
| safe_redirect | security, plugin_repo | Checks that redirects use wp_safe_redirect() instead of wp_redirect() for security. | [Learn more](https://developer.wordpress.org/reference/functions/wp_safe_redirect/) |
| plugin_updater | plugin_repo | Prevents altering WordPress update routines or using custom updaters, which are not allowed on WordPress.org. | [Learn more](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) |
| plugin_uninstall | plugin_repo | Checks related to plugin uninstallation. | [Learn more](https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/#method-2-uninstall-php) |
| external_admin_menu_links | plugin_repo | Detects external URLs used in top-level WordPress admin menu, which disrupts the expected user experience. | [Learn more](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/#11-plugins-should-not-hijack-the-admin) |
| menu_image_icon | plugin_repo | Detects the use of raster image files as admin menu icons, which do not adapt to the WordPress admin color schemes. Use a dashicon or an SVG data: URI instead. | [Learn more](https://developer.wordpress.org/resource/dashicons/) |
| wp_functions_compatibility | plugin_repo | Checks whether WordPress functions used by the plugin are compatible with the declared minimum supported WordPress version ("Requires at least"). | [Learn more](https://developer.wordpress.org/plugins/plugin-basics/header-requirements/#header-fields) |
| plugin_review_phpcs | plugin_repo | Runs PHP_CodeSniffer to detect certain best practices plugins should follow for submission on WordPress.org, including heredoc usage detection. | [Learn more](https://developer.wordpress.org/plugins/plugin-basics/best-practices/) |
| direct_db_queries | security, plugin_repo | Checks the usage of direct database queries, which should be avoided. | [Learn more](https://developer.wordpress.org/apis/database/) |
| direct_db | security, plugin_repo | Checks the escaping in direct database queries. | [Learn more](https://developer.wordpress.org/apis/database/) |
| performant_wp_query_params | performance | Checks for potentially slow database queries when using <code>WP_Query</code> | [Learn more](https://developer.wordpress.org/apis/database/) |
| enqueued_scripts_in_footer | performance | Checks whether a loading strategy is explicitly set for JavaScript files, as loading scripts in the footer is usually desired. | [Learn more](https://developer.wordpress.org/plugins/) |
| enqueued_resources | plugin_repo, performance | Checks whether scripts and styles are properly enqueued using the recommended way. | [Learn more](https://developer.wordpress.org/plugins/) |
| plugin_readme | plugin_repo | Checks adherence to the <code>readme.txt</code> requirements. | [Learn more](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/) |
| localhost | plugin_repo | Detects the usage of Localhost/127.0.0.1 in the plugin. | [Learn more](https://make.wordpress.org/plugins/handbook/performing-reviews/review-checklist/) |
| minified_files | plugin_repo | Detects minified PHP files and tokenization errors. | [Learn more](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/#the-code) |
| no_unfiltered_uploads | plugin_repo | Detects disallowed usage of <code>ALLOW_UNFILTERED_UPLOADS</code>. | [Learn more](https://make.wordpress.org/plugins/handbook/performing-reviews/review-checklist/) |
| trademarks | plugin_repo | Checks the usage of trademarks or other projects in the plugin slug. | [Learn more](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) |
| offloading_files | plugin_repo | Prevents using remote services that are not necessary. | [Learn more](https://developer.wordpress.org/plugins/wordpress-org/common-issues/#calling-files-remotely) |
| write_file | plugin_repo | Detects if plugins save data in the plugin folder instead of using the uploads directory or database. | [Learn more](https://developer.wordpress.org/plugins/wordpress-org/common-issues/#saving-data-in-the-plugin-folder) |
| setting_sanitization | plugin_repo | Ensures sanitization in register_setting(). | [Learn more](https://developer.wordpress.org/reference/functions/register_setting/) |
| prefixing | plugin_repo | Checks plugin for unique prefixing for everything the plugin defines in the public namespace. | [Learn more](https://developer.wordpress.org/plugins/plugin-basics/best-practices/) |
| enqueued_scripts_size | performance | Checks whether the cumulative size of all scripts enqueued on a page exceeds 293 KB. | [Learn more](https://developer.wordpress.org/plugins/) |
| enqueued_styles_size | performance | Checks whether the cumulative size of all stylesheets enqueued on a page exceeds 293 KB. | [Learn more](https://developer.wordpress.org/plugins/) |
| enqueued_styles_scope | performance | Checks whether any stylesheets are loaded on all pages, which is usually not desirable and can lead to performance issues. | [Learn more](https://developer.wordpress.org/plugins/) |
| enqueued_scripts_scope | performance | Checks whether any scripts are loaded on all pages, which is usually not desirable and can lead to performance issues. | [Learn more](https://developer.wordpress.org/plugins/) |
| non_blocking_scripts | performance | Checks whether scripts and styles are enqueued using a recommended loading strategy. | [Learn more](https://developer.wordpress.org/plugins/) |
| ai_provider | general | Recommends the WordPress AI Client when a plugin integrates directly with a third-party AI provider. | [Learn more](https://developer.wordpress.org/plugins/) |
| react_usage | general, plugin_repo | Detects React usage that breaks when WordPress upgrades to React 19. | [Learn more](https://react.dev/blog/2024/04/25/react-19-upgrade-guide) |

## Results and severity

The check name in the table is a **check slug**. When a check finds a problem, it emits a separate **result code**. Result codes are the values accepted by `--ignore-codes` and are included in the CLI output alongside the result type and severity.

Each result has a type (`ERROR` or `WARNING`) and a severity from 1 (lowest) to 10 (highest). A check can emit more than one result code, and the same code can be emitted for multiple files. Unless a check overrides it, a result uses the default severity of 5.

For example, the `code_obfuscation` check reports the following result for supported obfuscation tools:

| Check | Result code | Type | Severity | Detected tools |
| --- | --- | --- | --- | --- |
| code_obfuscation | `obfuscated_code_detected` | ERROR | 7 | Zend Guard, Source Guardian, ionCube |

To see the result codes and severity values produced for a plugin, run:

```bash
wp plugin check <plugin> --format=csv --fields=code,type,severity,message
```

Use the check slug with `--checks` or `--exclude-checks`; use the result code with `--ignore-codes`. This distinction is important when filtering a check without suppressing a particular finding across other checks.

## Notes

### Escaping widget wrapper output

The `late_escaping` check expects all output to be escaped before it is sent to the browser. This also applies to widget display arguments such as `before_widget`, `after_widget`, `before_title`, and `after_title`.

Classic widget examples often echo these values directly because themes provide the wrapper markup. When a plugin outputs them, use an escaping function that allows expected HTML, such as `wp_kses_post()`. Escape widget text according to the content it allows, such as `esc_html()` for plain text titles or `wp_kses_post()` for titles that intentionally allow limited markup:

```php
echo wp_kses_post( $args['before_widget'] );
echo wp_kses_post( $args['before_title'] );
echo esc_html( $title );
echo wp_kses_post( $args['after_title'] );
echo wp_kses_post( $args['after_widget'] );
```

### Private upload protection (experimental)

Run this opt-in advisory with:

```bash
wp plugin check <plugin> --checks=private_upload_protection --include-experimental
```

`potentially_unprotected_private_uploads` is a warning for manual review, not a
confirmed vulnerability. It identifies a PHP file that both calls a supported
upload API and calls `file_put_contents()` with a recognizable configuration
filename and literal access-denial content. Results point to the write call and
link to this guidance. The check does not execute plugin code.

Supported upload calls include `wp_upload_dir()`, `wp_get_upload_dir()`,
`wp_upload_bits()`, `wp_handle_upload()`, `wp_handle_sideload()`,
`media_handle_upload()`, `media_handle_sideload()`, `_wp_handle_upload()`, and
`move_uploaded_file()`. Global and fully qualified calls are recognized; method
calls, declarations, and explicitly namespaced calls are excluded.

The filename must have the exact basename `.htaccess` or `web.config`. A dynamic
directory prefix followed by a literal slash-prefixed path is supported. Content
must be a literal string or a concatenation of literal strings within that same
write call. Apache recognition covers standalone `Deny from all` and
`Require all denied` lines, ignoring comment lines and `<Files>`/`<FilesMatch>`
sections. IIS recognition covers broad or anonymous-user denial inside an
`authorization` section, excluding XML comments and `location` sections.

Apache and IIS configuration files do not provide protection on Nginx. If the
files are intended to be private, verify unauthenticated direct-URL requests on
every supported server. Check authorization for each file, including access by
another logged-in user. Consider storage outside the public web root with an
authorized download handler. Public uploads or independently configured server
protection may require no change.

Limitations:

- Same-file proximity does not prove that the upload and rule-writing operations
  are related, that either operation executes or succeeds, or that files are private.
  Directory APIs may merely read a path or URL. Review the actual destination and
  deployment configuration before deciding whether action is needed.
- Bundled configuration files and cross-file relationships are deliberately ignored.
- Variable-held filenames/content, interpolated strings, custom writers, `fopen()`/
  `fwrite()`, filesystem wrappers, named arguments, and dynamic function calls are
  not resolved. Aliased imports and local functions that shadow global API names
  are not resolved either.
- Apache rewrite-based denial (such as `RewriteRule .* - [F]`), scoped denial, and
  complex IIS rules are outside this heuristic. It does not model server rule
  precedence or inherited configuration. PHP syntax errors are skipped.
- A warning does not prove exposure, and no warning does not prove protection.

The check remains experimental in the `security` category and is excluded from
default runs. It does not enforce a Plugin Directory requirement.
