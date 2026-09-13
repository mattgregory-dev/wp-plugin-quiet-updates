<?php
/**
 * Plugin Name:       Quiet Update Emails
 * Plugin URI:        https://github.com/mattgregory-dev/quiet-updates
 * Description:       Silences routine WordPress update mail and the periodic admin-email check, while letting failures through.
 * Version:           2.0.0
 * Requires at least: 5.5
 * Requires PHP:      7.4
 * Author:            mattgregory-dev
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       quiet-updates
 *
 * Across several sites the routine "everything updated fine" mail arrives often
 * enough that it stops being read, which is what makes the one that matters easy
 * to miss. Successes are silenced here; failures are not, so the inbox goes back
 * to meaning something went wrong.
 *
 * There are no settings. Every choice this plugin makes is the one that would be
 * set anyway, and a settings screen would only add a way to half-configure it.
 *
 * @package QuietUpdates
 */

defined( 'ABSPATH' ) || exit;

/**
 * Core auto-update results.
 *
 * Core passes the outcome as $type: 'success', 'fail' or 'critical'. Only the
 * first is noise — 'critical' means the site may be down and is the single
 * message worth being interrupted for, so returning false unconditionally here
 * would throw away the alarm along with the chatter.
 *
 * @param bool   $send Whether to send the email.
 * @param string $type One of 'success', 'fail', 'critical'.
 * @return bool
 */
function quiet_updates_core_email( $send, $type ) {
	return 'success' !== $type;
}
add_filter( 'auto_core_update_send_email', 'quiet_updates_core_email', 10, 2 );

/**
 * Plugin and theme auto-update digests.
 *
 * These arrive as one digest covering every item updated in a run, so the
 * decision is per run rather than per item: send only when at least one item
 * failed. Core tests each outcome with `true === $update_result->result`, so the
 * same comparison is used here — anything else, a WP_Error included, counts as
 * a failure.
 *
 * @param bool  $enabled        Whether notifications are enabled.
 * @param array $update_results Results for this run.
 * @return bool
 */
function quiet_updates_bulk_email( $enabled, $update_results ) {
	foreach ( (array) $update_results as $result ) {
		if ( ! isset( $result->result ) || true !== $result->result ) {
			return true;
		}
	}

	return false;
}
add_filter( 'auto_plugin_update_send_email', 'quiet_updates_bulk_email', 10, 2 );
add_filter( 'auto_theme_update_send_email', 'quiet_updates_bulk_email', 10, 2 );

/**
 * The "WordPress x.y is available" nudge, and the debug mail that only goes out
 * on development versions. Neither carries anything a failure email would not
 * already say, and the dashboard still shows an available update.
 */
add_filter( 'send_core_update_notification_email', '__return_false' );
add_filter( 'automatic_updates_send_debug_email', '__return_false' );

/**
 * The periodic "is this still the right admin email?" screen.
 *
 * WordPress interrupts a login with it every six months. Returning 0 disables
 * the check; the address stays editable under Settings → General.
 */
add_filter( 'admin_email_check_interval', '__return_zero' );
