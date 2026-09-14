<?php
/**
 * Plugin Name:       Quiet Update Emails
 * Plugin URI:        https://github.com/mattgregory-dev/wp-plugin-quiet-updates
 * Description:       Silences the routine WordPress update mail you choose to silence, and keeps the rest.
 * Version:           3.1.1
 * Requires at least: 6.9
 * Requires PHP:      7.4
 * Author:            Matthew Gregory
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       quiet-updates
 *
 * Across several sites the routine "everything updated fine" mail arrives often
 * enough that it stops being read, which is what makes the one that matters easy
 * to miss. This silences the categories chosen under Settings > Quiet Updates and
 * leaves the others alone.
 *
 * @package QuietUpdates
 */

defined( 'ABSPATH' ) || exit;

const QUIET_UPDATES_VERSION = '3.1.1';

// plugins_url() needs a file inside the plugin; __FILE__ here is that file.
define( 'QUIET_UPDATES_FILE', __FILE__ );

require_once plugin_dir_path( __FILE__ ) . 'includes/settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/filters.php';

if ( is_admin() ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/admin-page.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/status-panel.php';
}

register_activation_hook( __FILE__, 'quiet_updates_activate' );

/*
 * `plugins_loaded`, not `init`. An automatic update runs from wp-cron.php, and
 * a filter hooked on `init` is a race against the cron request's own event
 * dispatch — it would apply on most requests and silently miss the one that
 * sends the mail.
 */
add_action( 'plugins_loaded', 'quiet_updates_register_filters' );
