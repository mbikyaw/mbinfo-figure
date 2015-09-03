<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/Users/mbikyaw/PhpstormProjects/wordpress-develop/tests/phpunit';
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/mbinfo-figure.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

global $MBINFO_TEST_DATA;
$json = file_get_contents(dirname( __FILE__ ) . "/../credentials.json");
$MBINFO_TEST_DATA = json_decode($json);

