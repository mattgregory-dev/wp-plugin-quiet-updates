<?php
/**
 * A read-only summary of what this site actually does with automatic updates.
 *
 * Whether silencing the "success" emails is sensible depends entirely on
 * whether updates are automatic, and WordPress makes that surprisingly hard to
 * read off its own screens. So the answer is stated here, where the choice is
 * being made.
 *
 * Nothing in this file changes an update setting. It reads and prints, and
 * links out to the screen that owns the switch.
 *
 * @package QuietUpdates
 */

defined( 'ABSPATH' ) || exit;

/**
 * What the site does with core updates, and whether the user can change it.
 *
 * The logic mirrors wp-admin/update-core.php, which is the screen this panel
 * sends people to: the constant wins over the stored options, a disabled
 * updater overrides everything, and the filters have the final say on the
 * values while the UI lock is decided separately. Re-deriving it another way
 * would eventually disagree with the screen we are pointing at.
 *
 * @return array {
 *     @type string $state  One of 'all', 'minor', 'none'.
 *     @type bool   $locked Whether a constant or filter takes the choice away.
 * }
 */
function quiet_updates_core_status() {
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

	$updater = new WP_Automatic_Updater();

	$minor = 'enabled' === get_site_option( 'auto_update_core_minor', 'enabled' );
	$major = 'enabled' === get_site_option( 'auto_update_core_major', 'unset' );

	$locked = false;

	if ( defined( 'WP_AUTO_UPDATE_CORE' ) ) {
		if ( false === WP_AUTO_UPDATE_CORE ) {
			$minor = false;
			$major = false;
		} elseif ( true === WP_AUTO_UPDATE_CORE
			|| in_array( WP_AUTO_UPDATE_CORE, array( 'beta', 'rc', 'development', 'branch-development' ), true )
		) {
			$minor = true;
			$major = true;
		} elseif ( 'minor' === WP_AUTO_UPDATE_CORE ) {
			$minor = true;
			$major = false;
		}

		$locked = true;
	}

	// Covers AUTOMATIC_UPDATER_DISABLED, the matching filter, and file-mod locks.
	if ( $updater->is_disabled() ) {
		$minor  = false;
		$major  = false;
		$locked = true;
	}

	if ( has_filter( 'allow_major_auto_core_updates' ) ) {
		$locked = true;
	}

	/** This filter is documented in wp-admin/includes/class-core-upgrader.php */
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- A core hook, applied here to read the same value core does.
	$minor = apply_filters( 'allow_minor_auto_core_updates', $minor );
	/** This filter is documented in wp-admin/includes/class-core-upgrader.php */
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- A core hook, applied here to read the same value core does.
	$major = apply_filters( 'allow_major_auto_core_updates', $major );

	if ( $major ) {
		$state = 'all';
	} elseif ( $minor ) {
		$state = 'minor';
	} else {
		$state = 'none';
	}

	return array(
		'state'  => $state,
		'locked' => $locked,
	);
}

/**
 * How many plugins or themes are set to update themselves.
 *
 * A count, not a list: the Plugins and Themes screens already carry the detail,
 * and repeating it here would be a second copy to keep honest.
 *
 * @param string $type Either 'plugin' or 'theme'.
 * @return array {
 *     @type bool $enabled  Whether auto-updates are available for this type.
 *     @type int  $on       How many are set to update automatically.
 *     @type int  $total    How many could update automatically.
 *     @type int  $no_source How many have no update source at all.
 * }
 */
function quiet_updates_item_status( $type ) {
	$on        = get_site_option( 'plugin' === $type ? 'auto_update_plugins' : 'auto_update_themes', array() );
	$installed = quiet_updates_installed_slugs( $type );
	$eligible  = quiet_updates_updatable_slugs( $type, $installed );

	return array(
		'enabled'   => wp_is_auto_update_enabled_for_type( $type ),
		'on'        => count( array_intersect( (array) $on, $eligible ) ),
		'total'     => count( $eligible ),
		'no_source' => count( $installed ) - count( $eligible ),
	);
}

/**
 * The installed items WordPress has an update source for.
 *
 * Something installed by hand -- a zip from elsewhere, a plugin in development
 * -- appears in no update transient, and its row on the Plugins screen offers
 * no auto-update control at all. Counting those in the denominator makes this
 * panel disagree with that screen by exactly their number.
 *
 * @param string   $type      Either 'plugin' or 'theme'.
 * @param string[] $installed Everything installed of that type.
 * @return string[]
 */
function quiet_updates_updatable_slugs( $type, $installed ) {
	$transient = get_site_transient( 'plugin' === $type ? 'update_plugins' : 'update_themes' );

	$known = array_merge(
		isset( $transient->response ) ? array_keys( (array) $transient->response ) : array(),
		isset( $transient->no_update ) ? array_keys( (array) $transient->no_update ) : array()
	);

	// An empty transient means WordPress has not checked yet, not that nothing
	// can update. Reporting zero there would be worse than counting everything.
	if ( ! $known ) {
		return $installed;
	}

	return array_values( array_intersect( $installed, $known ) );
}

/**
 * The identifiers WordPress stores in the auto-update lists.
 *
 * The stored list is not pruned when something is deleted, so counting it
 * directly can report more items than the site has installed.
 *
 * @param string $type Either 'plugin' or 'theme'.
 * @return string[]
 */
function quiet_updates_installed_slugs( $type ) {
	if ( 'plugin' === $type ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		return array_keys( get_plugins() );
	}

	return array_keys( wp_get_themes() );
}

/**
 * One sentence describing the core setting.
 *
 * @param string $state One of 'all', 'minor', 'none'.
 * @return string
 */
function quiet_updates_core_sentence( $state ) {
	if ( 'all' === $state ) {
		return __( 'WordPress core: installs every release automatically.', 'quiet-updates' );
	}

	if ( 'minor' === $state ) {
		return __( 'WordPress core: installs minor and security updates automatically.', 'quiet-updates' );
	}

	return __( 'WordPress core: does not install anything automatically.', 'quiet-updates' );
}

/**
 * One sentence describing a plugin or theme count.
 *
 * @param string $type   Either 'plugin' or 'theme'.
 * @param array  $status Result of quiet_updates_item_status().
 * @return string
 */
function quiet_updates_item_sentence( $type, $status ) {
	if ( ! $status['enabled'] ) {
		return 'plugin' === $type
			? __( 'Plugins: automatic updates are switched off for this site.', 'quiet-updates' )
			: __( 'Themes: automatic updates are switched off for this site.', 'quiet-updates' );
	}

	if ( 0 === $status['on'] ) {
		return 'plugin' === $type
			? __( 'Plugins: none update automatically.', 'quiet-updates' )
			: __( 'Themes: none update automatically.', 'quiet-updates' );
	}

	if ( 'plugin' === $type ) {
		$sentence = sprintf(
			/* translators: 1: Number set to auto-update, 2: Number that could. */
			__( 'Plugins: %1$d of %2$d update automatically.', 'quiet-updates' ),
			$status['on'],
			$status['total']
		);
	} else {
		$sentence = sprintf(
			/* translators: 1: Number set to auto-update, 2: Number that could. */
			__( 'Themes: %1$d of %2$d update automatically.', 'quiet-updates' ),
			$status['on'],
			$status['total']
		);
	}

	if ( $status['no_source'] > 0 ) {
		$sentence .= ' ' . sprintf(
			/* translators: %d: How many were installed by hand. */
			_n(
				'%d more was installed by hand, so WordPress cannot update it.',
				'%d more were installed by hand, so WordPress cannot update them.',
				$status['no_source'],
				'quiet-updates'
			),
			$status['no_source']
		);
	}

	return $sentence;
}

/**
 * Print one line of the panel.
 *
 * @param string $sentence The sentence itself.
 * @param string $url      Where "change this" goes. Empty prints no link.
 * @param string $link     Link text.
 * @param string $note     Optional trailing note, printed instead of a link.
 */
function quiet_updates_status_line( $sentence, $url = '', $link = '', $note = '' ) {
	echo '<p>' . esc_html( $sentence );

	if ( '' !== $note ) {
		echo ' <span class="description">' . esc_html( $note ) . '</span>';
	} elseif ( '' !== $url ) {
		echo ' <a href="' . esc_url( $url ) . '">' . esc_html( $link ) . '</a>';
	}

	echo '</p>';
}

/**
 * The panel.
 */
function quiet_updates_render_status_panel() {
	$core = quiet_updates_core_status();

	/*
	 * The card draws its own background and border rather than borrowing core's
	 * .card, whose padding and width differ between admin color schemes and
	 * WordPress versions.
	 */
	echo '<div class="quiet-updates-status">';
	echo '<h2>' . esc_html__( 'Your update settings right now', 'quiet-updates' ) . '</h2>';

	/*
	 * A locked line drops its link rather than keeping it: the option on that
	 * screen is grayed out or absent, so sending someone there to change
	 * something they cannot change just relocates the confusion.
	 */
	quiet_updates_status_line(
		quiet_updates_core_sentence( $core['state'] ),
		$core['locked'] ? '' : self_admin_url( 'update-core.php' ),
		__( 'Change this', 'quiet-updates' ),
		$core['locked'] ? __( 'Set by your host or another plugin.', 'quiet-updates' ) : ''
	);

	quiet_updates_status_line(
		quiet_updates_item_sentence( 'plugin', quiet_updates_item_status( 'plugin' ) ),
		self_admin_url( 'plugins.php' ),
		__( 'Manage', 'quiet-updates' )
	);

	quiet_updates_status_line(
		quiet_updates_item_sentence( 'theme', quiet_updates_item_status( 'theme' ) ),
		self_admin_url( 'themes.php' ),
		__( 'Manage', 'quiet-updates' )
	);

	echo '</div>';
}
