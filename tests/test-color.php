<?php
/**
 * Plain-PHP unit tests for WCC_Color (no WordPress needed).
 * Run: php tests/test-color.php
 */
define( 'WCC_TESTING', true );
require __DIR__ . '/../woo-color-chart/includes/class-wcc-color.php';

$fail = 0;
$n    = 0;
function check( $label, $got, $want ) {
	global $fail, $n;
	$n++;
	if ( $got !== $want ) {
		$fail++;
		echo "FAIL $label: got " . var_export( $got, true ) . ', want ' . var_export( $want, true ) . "\n";
	}
}

check( 'hex short', WCC_Color::normalize_hex( 'F00' ), '#ff0000' );
check( 'hex hash/space', WCC_Color::normalize_hex( ' #00FF00 ' ), '#00ff00' );
check( 'hex invalid', WCC_Color::normalize_hex( 'red' ), '' );
check( 'hex xss', WCC_Color::normalize_hex( '#fff;background:url(x)' ), '' );
check( 'hex null', WCC_Color::normalize_hex( null ), '' );

$hsl = WCC_Color::hex_to_hsl( '#ff0000' );
check( 'hsl red h', $hsl['h'], 0.0 );
check( 'hsl red s', $hsl['s'], 1.0 );
check( 'hsl blue h', WCC_Color::hex_to_hsl( '#0000ff' )['h'], 240.0 );
check( 'hsl invalid', WCC_Color::hex_to_hsl( 'zz' ), null );

$families = array(
	'#d0101b' => 'red', '#f28c00' => 'orange', '#ffd500' => 'yellow', '#3d9b35' => 'green',
	'#00a3a6' => 'turquoise', '#1f3e9e' => 'blue', '#6a2c91' => 'violet', '#f39bc3' => 'pink',
	'#5a3a22' => 'brown', '#c9a66b' => 'brown', '#8c8c8c' => 'grey', '#111111' => 'black',
	'#f4f4f0' => 'white', '#ffffff' => 'white', '#000000' => 'black', '#6d0f1f' => 'red',
	'#62b6e6' => 'blue',
);
foreach ( $families as $hex => $want ) {
	check( "family $hex", WCC_Color::family( $hex ), $want );
}
check( 'family invalid', WCC_Color::family( '' ), '' );

$rows   = array(
	array( 'hex' => '#111111', 'name' => 'Black', 'sku' => 'A-10' ),
	array( 'hex' => '#1f3e9e', 'name' => 'Blue', 'sku' => 'A-2' ),
	array( 'hex' => '', 'name' => 'Unknown', 'sku' => 'A-1' ),
	array( 'hex' => '#d0101b', 'name' => 'Red', 'sku' => 'A-3' ),
	array( 'hex' => '#ffffff', 'name' => 'White', 'sku' => 'A-20' ),
	array( 'hex' => '#ffd500', 'name' => 'Yellow', 'sku' => 'A-4' ),
);
check( 'sort hue', array_column( WCC_Color::sort_rows( $rows, 'hue' ), 'name' ), array( 'Red', 'Yellow', 'Blue', 'White', 'Black', 'Unknown' ) );
check( 'sort sku natural', array_column( WCC_Color::sort_rows( $rows, 'sku' ), 'sku' ), array( 'A-1', 'A-2', 'A-3', 'A-4', 'A-10', 'A-20' ) );
check( 'sort menu', array_column( WCC_Color::sort_rows( $rows, 'menu' ), 'name' ), array_column( $rows, 'name' ) );

check( 'contrast yellow', WCC_Color::contrast_text( '#ffd500' ), '#000000' );
check( 'contrast blue', WCC_Color::contrast_text( '#1f3e9e' ), '#ffffff' );

echo ( $fail ? "$fail of $n tests FAILED\n" : "All $n PHP tests passed\n" );
exit( $fail ? 1 : 0 );
