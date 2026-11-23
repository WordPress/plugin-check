<?php
/**
 * Namespaced test file for PHPErrorReportingSniff.
 */

namespace My_Plugin;

// GOOD: namespaced const declares \My_Plugin\WP_DEBUG, not global WP_DEBUG.
const WP_DEBUG = true;

// GOOD: reading global WP_DEBUG in a namespaced const value.
const LOCAL_DEBUG = \WP_DEBUG;

// BAD: unqualified define() still defines global WP_DEBUG.
define( 'WP_DEBUG', true );
