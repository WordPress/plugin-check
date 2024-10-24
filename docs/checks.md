[Back to overview](./README.md)

# Available Checks

| Check | Category | Description | Documentation |
| --- | --- | --- | --- |
| i18n_usage | general, plugin_repo | Checks for various internationalization best practices. | [Learn more](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/) |
| code_obfuscation | plugin_repo | Detects the usage of code obfuscation tools. | [Learn more](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) |
| file_type | plugin_repo | Detects the usage of hidden and compressed files, VCS directories, application files and badly named files. | [Learn more](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) |
| plugin_header_fields | plugin_repo | Checks adherence to the Headers requirements. | [Learn more](https://developer.wordpress.org/plugins/plugin-basics/header-requirements/) |
| late_escaping | security, plugin_repo | Checks that all output is escaped before being sent to the browser. | [Learn more](https://developer.wordpress.org/apis/security/escaping/) |
| plugin_updater | plugin_repo | Prevents altering WordPress update routines or using custom updaters, which are not allowed on WordPress.org. | [Learn more](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) |
| plugin_review_phpcs | plugin_repo | Runs PHP_CodeSniffer to detect certain best practices plugins should follow for submission on WordPress.org. | [Learn more](https://developer.wordpress.org/plugins/plugin-basics/best-practices/) |
| direct_db_queries | security, plugin_repo | Checks the usage of direct database queries, which should be avoided. | [Learn more](https://developer.wordpress.org/apis/database/) |
| performant_wp_query_params | performance | Checks for potentially slow database queries when using <code>WP_Query</code> | [Learn more](https://developer.wordpress.org/apis/database/) |
| enqueued_scripts_in_footer | performance | Checks whether a loading strategy is explicitly set for JavaScript files, as loading scripts in the footer is usually desired. | [Learn more](https://developer.wordpress.org/plugins/) |
| enqueued_resources | plugin_repo, performance | Checks whether scripts and styles are properly enqueued using the recommended way. | [Learn more](https://developer.wordpress.org/plugins/) |
| plugin_readme | plugin_repo | Checks adherence to the <code>readme.txt</code> requirements. | [Learn more](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/) |
| localhost | plugin_repo | Detects the usage of Localhost/127.0.0.1 in the plugin. | [Learn more](https://make.wordpress.org/plugins/handbook/performing-reviews/review-checklist/) |
| no_unfiltered_uploads | plugin_repo | Detects disallowed usage of <code>ALLOW_UNFILTERED_UPLOADS</code>. | [Learn more](https://make.wordpress.org/plugins/handbook/performing-reviews/review-checklist/) |
| trademarks | plugin_repo | Checks the usage of trademarks or other projects in the plugin slug. | [Learn more](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) |
| offloading_files | plugin_repo | Prevents using remote services that are not necessary. | [Learn more](https://developer.wordpress.org/plugins/wordpress-org/common-issues/#calling-files-remotely) |
| image_functions | performance | Checks whether images are inserted using recommended functions. | [Learn more](https://developer.wordpress.org/plugins/) |
| enqueued_scripts_size | performance | Checks whether the cumulative size of all scripts enqueued on a page exceeds 293 KB. | [Learn more](https://developer.wordpress.org/plugins/) |
| enqueued_styles_size | performance | Checks whether the cumulative size of all stylesheets enqueued on a page exceeds 293 KB. | [Learn more](https://developer.wordpress.org/plugins/) |
| enqueued_styles_scope | performance | Checks whether any stylesheets are loaded on all pages, which is usually not desirable and can lead to performance issues. | [Learn more](https://developer.wordpress.org/plugins/) |
| enqueued_scripts_scope | performance | Checks whether any scripts are loaded on all pages, which is usually not desirable and can lead to performance issues. | [Learn more](https://developer.wordpress.org/plugins/) |
| non_blocking_scripts | performance | Checks whether scripts and styles are enqueued using a recommended loading strategy. | [Learn more](https://developer.wordpress.org/plugins/) |