<?php
/**
 * Uninstall hooks
 */

// If uninstall.php is not called by WordPress, die
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

wp_clear_scheduled_hook( 'wc_ifthen_hourly_cron' );
