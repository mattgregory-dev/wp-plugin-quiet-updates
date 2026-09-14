<?php
/**
 * Settings > Quiet Updates.
 *
 * Copy follows the settings-panel copy document: the label says what the
 * setting does, the helper text adds the one thing someone might not have
 * thought of, and the warning about losing failure mail sits next to the option
 * that causes it rather than in the intro.
 *
 * @package QuietUpdates
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add the options page.
 */
function quiet_updates_add_page() {
	add_options_page(
		__( 'Quiet Updates', 'quiet-updates' ),
		__( 'Quiet Updates', 'quiet-updates' ),
		'manage_options',
		'quiet-updates',
		'quiet_updates_render_page'
	);
}
add_action( 'admin_menu', 'quiet_updates_add_page' );

/**
 * Sections and fields.
 */
function quiet_updates_add_fields() {
	add_settings_section(
		'quiet_updates_results',
		__( 'Emails after an update installs', 'quiet-updates' ),
		'quiet_updates_results_intro',
		'quiet-updates',
		array(
			/*
			 * The warning belongs after all three categories rather than inside
			 * one of them: it describes the last option, which any of the three
			 * can be set to.
			 */
			'after_section' => '<p class="description" style="max-width:46em"><strong>'
				. esc_html__( 'Heads up:', 'quiet-updates' ) . '</strong> '
				. esc_html__( '"Silence all of them" means a failed update comes and goes without a word. Only pick that if you check your sites another way.', 'quiet-updates' )
				. '</p>',
		)
	);

	$categories = array(
		'core_updates'   => __( 'WordPress core', 'quiet-updates' ),
		'plugin_updates' => __( 'Plugins', 'quiet-updates' ),
		'theme_updates'  => __( 'Themes', 'quiet-updates' ),
	);

	foreach ( $categories as $key => $label ) {
		add_settings_field(
			$key,
			$label,
			'quiet_updates_render_modes',
			'quiet-updates',
			'quiet_updates_results',
			array( 'key' => $key )
		);
	}

	add_settings_section(
		'quiet_updates_notices',
		__( 'Other update emails', 'quiet-updates' ),
		'quiet_updates_notices_intro',
		'quiet-updates'
	);

	$toggles = array(
		'version_nudge'     => array(
			__( 'New version available', 'quiet-updates' ),
			__( 'Silence the "a new version of WordPress is available" email.', 'quiet-updates' ),
			__( 'You will still see it on your Dashboard and Updates screen. Just know this email is often how people find out an update is waiting, since big WordPress updates do not install themselves unless you have set that up. Turn it off and you will need to check the Updates screen yourself.', 'quiet-updates' ),
		),
		'admin_email_check' => array(
			__( 'Admin email confirmation', 'quiet-updates' ),
			__( 'Stop the screen that interrupts your login every six months to confirm your admin email.', 'quiet-updates' ),
			__( 'That screen is WordPress double-checking your admin address still works. Turn it off and, if that address ever stops working, nothing will warn you. It is the address your password resets and update-failure emails go to, so it is worth keeping reachable.', 'quiet-updates' ),
		),
		'debug_email'       => array(
			__( 'Debug email', 'quiet-updates' ),
			__( 'Silence the automatic-update debug email.', 'quiet-updates' ),
			__( 'This one only goes out on beta and test builds of WordPress. On a normal live site nothing is sent, so this setting does nothing unless you are running a test build.', 'quiet-updates' ),
		),
	);

	foreach ( $toggles as $key => $parts ) {
		add_settings_field(
			$key,
			$parts[0],
			'quiet_updates_render_toggle',
			'quiet-updates',
			'quiet_updates_notices',
			array(
				'key'    => $key,
				'label'  => $parts[1],
				'helper' => $parts[2],
			)
		);
	}
}
add_action( 'admin_init', 'quiet_updates_add_fields' );

/**
 * Intro text for the update-results section.
 */
function quiet_updates_results_intro() {
	echo '<p style="max-width:46em">' . esc_html__( 'When WordPress installs an update for you, it emails to say how it went, even when nothing went wrong. Those "all good" emails pile up without telling you much. Pick "Silence successes" to stop them but still hear about failures.', 'quiet-updates' ) . '</p>';
}

/**
 * Intro text for the notices section.
 */
function quiet_updates_notices_intro() {
	echo '<p style="max-width:46em">' . esc_html__( 'These are not about updates that already ran. They are heads-ups and reminders WordPress sends on its own, like telling you an update is waiting.', 'quiet-updates' ) . '</p>';
}

/**
 * Radio group for one update category.
 *
 * @param array $args Field arguments; expects 'key'.
 */
function quiet_updates_render_modes( $args ) {
	$key     = $args['key'];
	$current = quiet_updates_setting( $key );

	echo '<fieldset>';
	foreach ( quiet_updates_modes() as $value => $label ) {
		printf(
			'<label style="display:block;margin-bottom:.35em"><input type="radio" name="%1$s[%2$s]" value="%3$s"%4$s> %5$s',
			esc_attr( QUIET_UPDATES_OPTION ),
			esc_attr( $key ),
			esc_attr( $value ),
			checked( $current, $value, false ),
			esc_html( $label )
		);

		if ( QUIET_UPDATES_RECOMMENDED === $value ) {
			echo ' <span class="description"><strong>' . esc_html__( '(recommended)', 'quiet-updates' ) . '</strong></span>';
		}

		echo '</label>';
	}
	echo '</fieldset>';
}

/**
 * Checkbox and helper text for one toggle.
 *
 * @param array $args Field arguments; expects 'key', 'label' and 'helper'.
 */
function quiet_updates_render_toggle( $args ) {
	printf(
		'<label><input type="checkbox" name="%1$s[%2$s]" value="1"%3$s> %4$s</label>',
		esc_attr( QUIET_UPDATES_OPTION ),
		esc_attr( $args['key'] ),
		checked( quiet_updates_setting( $args['key'] ), true, false ),
		esc_html( $args['label'] )
	);

	if ( ! empty( $args['helper'] ) ) {
		printf(
			'<p class="description" style="max-width:46em">%s</p>',
			esc_html( $args['helper'] )
		);
	}
}

/**
 * The page itself.
 */
function quiet_updates_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<p style="max-width:46em"><?php esc_html_e( 'This plugin turns off the update emails you do not need and keeps the ones you do. Nothing changes until you pick something below. It only affects email. It never changes which updates install.', 'quiet-updates' ); ?></p>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'quiet_updates' );
			do_settings_sections( 'quiet-updates' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}
