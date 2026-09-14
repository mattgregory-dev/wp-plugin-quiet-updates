<?php
/**
 * Settings > Quiet Updates.
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
		__( 'Update result emails', 'quiet-updates' ),
		'quiet_updates_results_intro',
		'quiet-updates'
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
		__( 'Notices and nags', 'quiet-updates' ),
		'__return_false',
		'quiet-updates'
	);

	$toggles = array(
		'version_nudge'     => array(
			__( 'New version available', 'quiet-updates' ),
			__( 'Silence the email announcing that a new version of WordPress is available. The Dashboard and Updates screen still show it.', 'quiet-updates' ),
			__( 'Worth knowing: big WordPress updates do not install themselves, and this email is usually how you find out one is waiting. Turn it off and you will need to check the Updates screen yourself.', 'quiet-updates' ),
		),
		'admin_email_check' => array(
			__( 'Admin email confirmation', 'quiet-updates' ),
			__( 'Stop the verification screen that interrupts a login every six months. The address stays editable under Settings › General.', 'quiet-updates' ),
			__( 'Worth knowing: that screen is WordPress checking that your admin email address still works. If you turn it off and the address later goes dead, nothing will tell you. That address is where password resets go, and where the failure emails above are sent.', 'quiet-updates' ),
		),
		'debug_email'       => array(
			__( 'Debug email', 'quiet-updates' ),
			__( 'Silence the automatic-update debug email.', 'quiet-updates' ),
			__( 'Worth knowing: this email only goes out on beta and test builds of WordPress. On a normal site, no email is sent out, and this setting has no effect.', 'quiet-updates' ),
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
				'key'         => $key,
				'label'       => $parts[1],
				'implication' => $parts[2],
			)
		);
	}
}
add_action( 'admin_init', 'quiet_updates_add_fields' );

/**
 * Intro text for the update-results section.
 */
function quiet_updates_results_intro() {
	echo '<p style="max-width:46em">' . esc_html__( 'WordPress emails you after every automatic update, even when everything went fine. "Silence successes" is the one to pick: you stop hearing about the routine ones, and you still get told when an update fails, including the serious kind where the site might be down. "Silence every email" drops those warnings too, so a failed update would come and go without a word.', 'quiet-updates' ) . '</p>';
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
			echo ' <span class="description"><strong>' . esc_html__( 'Recommended', 'quiet-updates' ) . '</strong></span>';
		}

		echo '</label>';
	}
	echo '</fieldset>';
}

/**
 * Checkbox for one toggle.
 *
 * @param array $args Field arguments; expects 'key' and 'label'.
 */
function quiet_updates_render_toggle( $args ) {
	printf(
		'<label><input type="checkbox" name="%1$s[%2$s]" value="1"%3$s> %4$s</label>',
		esc_attr( QUIET_UPDATES_OPTION ),
		esc_attr( $args['key'] ),
		checked( quiet_updates_setting( $args['key'] ), true, false ),
		esc_html( $args['label'] )
	);

	if ( ! empty( $args['implication'] ) ) {
		printf(
			'<p class="description" style="max-width:46em">%s</p>',
			esc_html( $args['implication'] )
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
		<p><?php esc_html_e( 'Nothing is silenced until you turn it on below. This only changes which emails you get. It does not change which updates install.', 'quiet-updates' ); ?></p>
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
