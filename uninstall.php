<?php
/**
 * Remove everything this plugin stored.
 *
 * WordPress will not report a row left behind, so the only way to know an
 * uninstall is clean is to look in the database afterward.
 *
 * @package QuietUpdates
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'quiet_updates_settings' );

// A site that was multisite when the option was written keeps a network copy.
delete_site_option( 'quiet_updates_settings' );
