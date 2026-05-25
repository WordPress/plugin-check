<?php
/**
 * Plugin Name: Test Plugin Trialware With Errors
 * Description: Test plugin that contains trialware patterns.
 * Version: 1.0.0
 * Author: Test Author
 * License: GPL-2.0+
 *
 * @package test-plugin-trialware-with-errors
 */

defined( 'ABSPATH' ) || exit;

/**
 * Check if user has valid license.
 */
function test_trialware_check_license() {
	if ( ! is_licensed() ) {
		return false;
	}

	return true;
}

/**
 * Check if user is on pro plan.
 */
function test_trialware_is_pro() {
	if ( ! is_pro() ) {
		return false;
	}

	return true;
}

/**
 * Check trial expiration.
 */
function test_trialware_check_trial() {
	if ( trial_expired() ) {
		return false;
	}

	return true;
}

/**
 * Check usage quota.
 */
function test_trialware_check_quota() {
	if ( quota_exceeded() ) {
		return false;
	}

	return true;
}

/**
 * Check payment status.
 */
function test_trialware_check_payment() {
	if ( ! has_paid() ) {
		return false;
	}

	return true;
}
