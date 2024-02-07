[Back to overview](./README.md)

# Releasing a New Version of Plugin

This project uses a [GitHub Action workflow](../.github/workflows/deploy.yml) for automatically deploying
stable releases of Plugin Check to the [WordPress plugin directory](https://wordpress.org/plugins/plugin-check/), so there is no manual build step involved.

Follow these steps:

1. Bump the `Version` field in the main plugin file's header.
2. Bump `WP_PLUGIN_CHECK_VERSION` in the same file.
3. Bump the `Stable tag` field in the `readme.txt` file.
4. Update the changelog in `readme.txt`.
5. Commit this to the default branch.
6. On GitHub, go to "Releases" and create a new release.
7. Once published, this release will be automatically deployed to the plugin directory.

## Building the Plugin

If you would like to manually replicate the build process locally, you can do so
using the [`wp dist-archive` WP-CLI command](https://github.com/wp-cli/dist-archive-command/).
In your terminal, you can run this in the directory where you checked out this repository:

```
# Ensure PHPCS is installed, as it is required for some of the checks.
composer install --no-dev
# Build the ZIP file.
wp dist-archive . /path/to/write/the/plugin-check.zip
```

Note: you might first need to install the WP-CLI command if it's not yet available:

```
wp package install wp-cli/dist-archive-command
```

