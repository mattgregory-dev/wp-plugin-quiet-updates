<?php
/**
 * The filters, and the decisions behind them.
 *
 * The two decision functions take their mode as an argument rather than reading
 * it, so every branch can be exercised directly without saving an option first.
 *
 * @package QuietUpdates
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether to send a core auto-update email.
 *
 * Core passes the outcome as $type: 'success', 'fail' or 'critical'. Only the
 * first is noise — 'critical' means the site may be down, and is the one
 * message worth being interrupted for.
 *
 * @param bool   $send Whether WordPress would send it.
 * @param string $type One of 'success', 'fail', 'critical'.
 * @param string $mode One of the quiet_updates_modes() keys.
 * @return bool
 */
function quiet_updates_decide_core( $send, $type, $mode ) {
	if ( 'silence_all' === $mode ) {
		return false;
	}

	if ( 'successes_only' === $mode ) {
		return 'success' !== $type;
	}

	return (bool) $send;
}

/**
 * Whether to send a plugin or theme auto-update digest.
 *
 * One digest covers every item in a run, so the decision is per run rather than
 * per item: send when at least one item failed. Core tests each outcome with
 * `true === $result->result`, so the same comparison is used here — anything
 * else, a WP_Error included, counts as a failure.
 *
 * @param bool  $enabled Whether WordPress would send it.
 * @param mixed $results Update results for this run.
 * @param string $mode   One of the quiet_updates_modes() keys.
 * @return bool
 */
function quiet_updates_decide_bulk( $enabled, $results, $mode ) {
	if ( 'silence_all' === $mode ) {
		return false;
	}

	if ( 'successes_only' !== $mode ) {
		return (bool) $enabled;
	}

	foreach ( (array) $results as $result ) {
		if ( ! isset( $result->result ) || true !== $result->result ) {
			return true;
		}
	}

	return false;
}

/**
 * Core auto-update results.
 *
 * @param bool   $send Whether to send the email.
 * @param string $type One of 'success', 'fail', 'critical'.
 * @return bool
 */
function quiet_updates_core_email( $send, $type ) {
	return quiet_updates_decide_core( $send, $type, quiet_updates_setting( 'core_updates' ) );
}

/**
 * Plugin auto-update digest.
 *
 * @param bool  $enabled Whether notifications are enabled.
 * @param mixed $results Results for this run.
 * @return bool
 */
function quiet_updates_plugin_email( $enabled, $results ) {
	return quiet_updates_decide_bulk( $enabled, $results, quiet_updates_setting( 'plugin_updates' ) );
}

/**
 * Theme auto-update digest.
 *
 * @param bool  $enabled Whether notifications are enabled.
 * @param mixed $results Results for this run.
 * @return bool
 */
function quiet_updates_theme_email( $enabled, $results ) {
	return quiet_updates_decide_bulk( $enabled, $results, quiet_updates_setting( 'theme_updates' ) );
}

/**
 * Hook only what the settings ask for.
 *
 * A filter registered to return WordPress's own answer is indistinguishable
 * from no filter at all, except on the hook list a developer reads when
 * something is suppressing mail and they are working out what.
 */
function quiet_updates_register_filters() {
	if ( 'send_all' !== quiet_updates_setting( 'core_updates' ) ) {
		add_filter( 'auto_core_update_send_email', 'quiet_updates_core_email', 10, 2 );
	}

	if ( 'send_all' !== quiet_updates_setting( 'plugin_updates' ) ) {
		add_filter( 'auto_plugin_update_send_email', 'quiet_updates_plugin_email', 10, 2 );
	}

	if ( 'send_all' !== quiet_updates_setting( 'theme_updates' ) ) {
		add_filter( 'auto_theme_update_send_email', 'quiet_updates_theme_email', 10, 2 );
	}

	// The email announcing a new WordPress version. The dashboard still shows it.
	if ( quiet_updates_setting( 'version_nudge' ) ) {
		add_filter( 'send_core_update_notification_email', '__return_false' );
	}

	// Debug mail, which only goes out on development versions anyway.
	if ( quiet_updates_setting( 'debug_email' ) ) {
		add_filter( 'automatic_updates_send_debug_email', '__return_false' );
	}

	/*
	 * The periodic "is this still the right admin email?" screen, which
	 * interrupts a login every six months. Zero disables the check; the address
	 * stays editable under Settings > General.
	 */
	if ( quiet_updates_setting( 'admin_email_check' ) ) {
		add_filter( 'admin_email_check_interval', '__return_zero' );
	}
}
