[Back to overview](./README.md)

# Available Checks

| Check | Category | Description | Documentation |
| --- | --- | --- | --- |
| i18n_usage | general, plugin_repo | Checks for various internationalization best practices. | [Learn more](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/) |
| code_obfuscation | plugin_repo | Detects the usage of code obfuscation tools. | [Learn more](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) |
| plugin_content | plugin_repo | Detects content that does not comply with the WordPress.org plugin guidelines. | [Learn more](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) |
| trialware | plugin_repo | Uses AI to detect trialware and locked built-in features. | [Learn more](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) |
| direct_file_access | security, plugin_repo | Checks that plugin files include proper security validation using the ABSPATH constant to prevent direct file access. | [Learn more](https://developer.wordpress.org/plugins/plugin-basics/best-practices/#file-security) |
| file_type | plugin_repo | Detects the usage of hidden and compressed files, VCS directories, application files, badly named files, AI development directories (.cursor, .claude, .aider, .continue, .windsurf, .ai, .github), and unexpected markdown files in plugin root. | [Learn more](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) |
| plugin_header_fields | plugin_repo | Checks adherence to the Headers requirements, including validation of "Tested up to" header matching between plugin file and readme.txt. | [Learn more](https://developer.wordpress.org/plugins/plugin-basics/header-requirements/) |
| late_escaping | security, plugin_repo | Checks that all output is escaped before being sent to the browser. | [Learn more](https://developer.wordpress.org/apis/security/escaping/) |
| safe_redirect | security, plugin_repo | Checks that redirects use wp_safe_redirect() instead of wp_redirect() for security. | [Learn more](https://developer.wordpress.org/reference/functions/wp_safe_redirect/) |
| plugin_updater | plugin_repo | Prevents altering WordPress update routines or using custom updaters, which are not allowed on WordPress.org. | [Learn more](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) |
| plugin_uninstall | plugin_repo | Checks related to plugin uninstallation. | [Learn more](https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/#method-2-uninstall-php) |
| external_admin_menu_links | plugin_repo | Detects external URLs used in top-level WordPress admin menu, which disrupts the expected user experience. | [Learn more](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/#11-plugins-should-not-hijack-the-admin) |
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
| setting_sanitization | plugin_repo | Ensures sanitization in register_setting(). | [Learn more](https://developer.wordpress.org/reference/functions/register_setting/) |
| prefixing | plugin_repo | Checks plugin for unique prefixing for everything the plugin defines in the public namespace. | [Learn more](https://developer.wordpress.org/plugins/plugin-basics/best-practices/) |
| enqueued_scripts_size | performance | Checks whether the cumulative size of all scripts enqueued on a page exceeds 293 KB. | [Learn more](https://developer.wordpress.org/plugins/) |
| enqueued_styles_size | performance | Checks whether the cumulative size of all stylesheets enqueued on a page exceeds 293 KB. | [Learn more](https://developer.wordpress.org/plugins/) |
| enqueued_styles_scope | performance | Checks whether any stylesheets are loaded on all pages, which is usually not desirable and can lead to performance issues. | [Learn more](https://developer.wordpress.org/plugins/) |
| enqueued_scripts_scope | performance | Checks whether any scripts are loaded on all pages, which is usually not desirable and can lead to performance issues. | [Learn more](https://developer.wordpress.org/plugins/) |
| non_blocking_scripts | performance | Checks whether scripts and styles are enqueued using a recommended loading strategy. | [Learn more](https://developer.wordpress.org/plugins/) |
