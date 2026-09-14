<?php
/**
 * Stored settings: defaults, sanitizing, and the vocabulary the rest of the
 * plugin reads.
 *
 * @package QuietUpdates
 */

defined( 'ABSPATH' ) || exit;

const QUIET_UPDATES_OPTION = 'quiet_updates_settings';

/**
 * The mode recommended for all three update categories.
 *
 * Not the stored default: activation must change nothing about a site's mail.
 * This is only what the settings screen points at.
 */
const QUIET_UPDATES_RECOMMENDED = 'successes_only';

/**
 * The three ways an update-result email can be treated.
 *
 * @return array<string, string> Value => label.
 */
function quiet_updates_modes() {
	return array(
		'send_all'       => __( 'Send every email (WordPress default)', 'quiet-updates' ),
		'successes_only' => __( 'Silence successes, still send failures', 'quiet-updates' ),
		'silence_all'    => __( 'Silence every email, failures included', 'quiet-updates' ),
	);
}

/**
 * Defaults.
 *
 * Every value here means "do nothing". A plugin that starts suppressing mail
 * the moment it is activated is a surprise, and the mail it suppresses is the
 * kind someone only misses months later.
 *
 * @return array<string, mixed>
 */
function quiet_updates_defaults() {
	return array(
		'core_updates'      => 'send_all',
		'plugin_updates'    => 'send_all',
		'theme_updates'     => 'send_all',
		'version_nudge'     => false,
		'debug_email'       => false,
		'admin_email_check' => false,
	);
}

/**
 * Settings as stored, with any missing key filled from the defaults.
 *
 * Reading through here means a setting added in a later version behaves
 * correctly on a site that saved its options under an earlier one, without an
 * upgrade routine.
 *
 * @return array<string, mixed>
 */
function quiet_updates_settings() {
	$stored = get_option( QUIET_UPDATES_OPTION, array() );

	return wp_parse_args( is_array( $stored ) ? $stored : array(), quiet_updates_defaults() );
}

/**
 * One setting, already defaulted.
 *
 * @param string $key Setting name.
 * @return mixed
 */
function quiet_updates_setting( $key ) {
	$settings = quiet_updates_settings();

	return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
}

/**
 * Sanitize the whole option on save.
 *
 * Anything unrecognized falls back to its default rather than being kept or
 * dropped: a crafted request cannot store a mode the filters do not understand,
 * and an unchecked box arrives as a missing key rather than a false.
 *
 * @param mixed $input Raw submitted value.
 * @return array<string, mixed>
 */
function quiet_updates_sanitize( $input ) {
	$defaults = quiet_updates_defaults();
	$modes    = array_keys( quiet_updates_modes() );
	$clean    = array();

	foreach ( $defaults as $key => $default ) {
		if ( is_bool( $default ) ) {
			$clean[ $key ] = ! empty( $input[ $key ] );
			continue;
		}

		$value         = isset( $input[ $key ] ) ? (string) $input[ $key ] : '';
		$clean[ $key ] = in_array( $value, $modes, true ) ? $value : $default;
	}

	return $clean;
}

/**
 * Write the defaults on activation.
 *
 * `register_setting`'s default only answers a read; it stores nothing. Without
 * this the option row does not exist until the first save, which leaves
 * uninstall with nothing to clean up and no way to tell a fresh install from a
 * configured one.
 */
function quiet_updates_activate() {
	add_option( QUIET_UPDATES_OPTION, quiet_updates_defaults() );
}

/**
 * Register the option with the Settings API.
 */
function quiet_updates_register_setting() {
	register_setting(
		'quiet_updates',
		QUIET_UPDATES_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'quiet_updates_sanitize',
			'default'           => quiet_updates_defaults(),
		)
	);
}
add_action( 'admin_init', 'quiet_updates_register_setting' );
