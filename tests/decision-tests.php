<?php
/**
 * Every branch of the two decision functions, against fabricated inputs.
 *
 * The live path needs a real update to run; this does not, so it can be re-run
 * in seconds and at the version floor.
 *
 * Run: docker compose run --rm wpcli wp eval-file decision-tests.php
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'quiet_updates_decide_core' ) ) {
	WP_CLI::error( 'Quiet Updates is not active.' );
}

$GLOBALS['quiet_updates_pass'] = 0;
$GLOBALS['quiet_updates_fail'] = 0;

/**
 * Assert one expectation.
 *
 * @param string $label    What is being checked.
 * @param bool   $actual   Result.
 * @param bool   $expected Expectation.
 */
function quiet_updates_test_check( $label, $actual, $expected ) {
	if ( $actual === $expected ) {
		++$GLOBALS['quiet_updates_pass'];
		WP_CLI::log( sprintf( '  ok    %-56s %s', $label, $actual ? 'send' : 'silence' ) );
	} else {
		++$GLOBALS['quiet_updates_fail'];
		WP_CLI::log( sprintf( '  FAIL  %-56s got %s, wanted %s', $label, $actual ? 'true' : 'false', $expected ? 'true' : 'false' ) );
	}
}

/**
 * A fabricated update result, shaped the way core builds them.
 *
 * @param mixed $result What the item's ->result holds.
 * @return object
 */
function quiet_updates_test_item( $result ) {
	$item         = new stdClass();
	$item->result = $result;
	return $item;
}

$quiet_updates_ok      = quiet_updates_test_item( true );
$quiet_updates_failed  = quiet_updates_test_item( false );
$quiet_updates_errored = quiet_updates_test_item( new WP_Error( 'boom', 'Update failed' ) );
$quiet_updates_missing = new stdClass(); // No ->result at all.

WP_CLI::log( '' );
WP_CLI::log( 'core: send_all leaves WordPress alone' );
foreach ( array( 'success', 'fail', 'critical' ) as $quiet_updates_type ) {
	quiet_updates_test_check( "core / send_all / $quiet_updates_type", quiet_updates_decide_core( true, $quiet_updates_type, 'send_all' ), true );
}

WP_CLI::log( '' );
WP_CLI::log( 'core: successes_only keeps the alarm' );
quiet_updates_test_check( 'core / successes_only / success', quiet_updates_decide_core( true, 'success', 'successes_only' ), false );
quiet_updates_test_check( 'core / successes_only / fail', quiet_updates_decide_core( true, 'fail', 'successes_only' ), true );
quiet_updates_test_check( 'core / successes_only / critical', quiet_updates_decide_core( true, 'critical', 'successes_only' ), true );

WP_CLI::log( '' );
WP_CLI::log( 'core: silence_all is the choice to drop failures too' );
foreach ( array( 'success', 'fail', 'critical' ) as $quiet_updates_type ) {
	quiet_updates_test_check( "core / silence_all / $quiet_updates_type", quiet_updates_decide_core( true, $quiet_updates_type, 'silence_all' ), false );
}

WP_CLI::log( '' );
WP_CLI::log( 'digest: send_all leaves WordPress alone' );
quiet_updates_test_check( 'digest / send_all / all succeeded', quiet_updates_decide_bulk( true, array( $quiet_updates_ok, $quiet_updates_ok ), 'send_all' ), true );
quiet_updates_test_check( 'digest / send_all / already disabled', quiet_updates_decide_bulk( false, array( $quiet_updates_ok ), 'send_all' ), false );

WP_CLI::log( '' );
WP_CLI::log( 'digest: successes_only sends when any item failed' );
quiet_updates_test_check( 'digest / successes_only / all succeeded', quiet_updates_decide_bulk( true, array( $quiet_updates_ok, $quiet_updates_ok ), 'successes_only' ), false );
quiet_updates_test_check( 'digest / successes_only / one false', quiet_updates_decide_bulk( true, array( $quiet_updates_ok, $quiet_updates_failed ), 'successes_only' ), true );
quiet_updates_test_check( 'digest / successes_only / one WP_Error', quiet_updates_decide_bulk( true, array( $quiet_updates_ok, $quiet_updates_errored ), 'successes_only' ), true );
quiet_updates_test_check( 'digest / successes_only / no result property', quiet_updates_decide_bulk( true, array( $quiet_updates_missing ), 'successes_only' ), true );
quiet_updates_test_check( 'digest / successes_only / empty run', quiet_updates_decide_bulk( true, array(), 'successes_only' ), false );
quiet_updates_test_check( 'digest / successes_only / null results', quiet_updates_decide_bulk( true, null, 'successes_only' ), false );

WP_CLI::log( '' );
WP_CLI::log( 'digest: silence_all' );
quiet_updates_test_check( 'digest / silence_all / one failed', quiet_updates_decide_bulk( true, array( $quiet_updates_failed ), 'silence_all' ), false );

WP_CLI::log( '' );
WP_CLI::log( 'sanitizing rejects anything the filters would not understand' );
$quiet_updates_dirty = quiet_updates_sanitize(
	array(
		'core_updates'   => 'silence_everything_forever',
		'plugin_updates' => 'successes_only',
		'version_nudge'  => '1',
	)
);
quiet_updates_test_check( 'sanitize / unknown mode falls back to default', 'send_all' === $quiet_updates_dirty['core_updates'], true );
quiet_updates_test_check( 'sanitize / valid mode survives', 'successes_only' === $quiet_updates_dirty['plugin_updates'], true );
quiet_updates_test_check( 'sanitize / checked box becomes true', true === $quiet_updates_dirty['version_nudge'], true );
quiet_updates_test_check( 'sanitize / absent box becomes false', false === $quiet_updates_dirty['debug_email'], true );
quiet_updates_test_check( 'sanitize / absent mode takes its default', 'send_all' === $quiet_updates_dirty['theme_updates'], true );

WP_CLI::log( '' );
if ( 0 === $GLOBALS['quiet_updates_fail'] ) {
	WP_CLI::success( sprintf( '%d checks passed.', $GLOBALS['quiet_updates_pass'] ) );
} else {
	WP_CLI::error( sprintf( '%d passed, %d FAILED.', $GLOBALS['quiet_updates_pass'], $GLOBALS['quiet_updates_fail'] ) );
}
