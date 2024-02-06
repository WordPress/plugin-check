[Back to overview](./README.md)

# Building the Plugin

This project uses a [GitHub Action workflow](../.github/workflows/deploy.yml) for automatically deploying
stable releases of Plugin Check to the WordPress plugin directory, so there is no manual build step involved.

However, if you would like to replicate the build process locally, you can do so
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

