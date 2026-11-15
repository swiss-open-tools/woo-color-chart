<?php
/**
 * Smoke test: renders the chart with minimal WordPress/WooCommerce stubs.
 * NOT a replacement for a real WordPress integration test.
 * Run: php tests/smoke-render.php
 */
define( 'ABSPATH', __DIR__ );
define( 'WCC_URL', '/' );
define( 'WCC_VERSION', 'test' );
foreach ( array( '__', 'esc_html__', 'esc_attr__', 'esc_html', 'esc_attr', 'esc_url', 'wp_kses_post' ) as $f ) {
	eval( "function $f( \$s ) { return htmlspecialchars( (string) \$s, ENT_QUOTES ); }" );
}
function esc_html_e( $s ) { echo esc_html( $s ); }
function esc_attr_e( $s ) { echo esc_attr( $s ); }
function sanitize_title( $s ) { return strtolower( $s ); }
function selected( $a, $b ) { echo $a === $b ? ' selected' : ''; }
function apply_filters( $h, $v ) { return $v; }
function wp_parse_args( $a, $d ) { return array_merge( $d, $a ); }
function get_the_ID() { return 0; }
function current_user_can() { return false; }
function wp_enqueue_style() {}
function wp_enqueue_script() {}
function get_option( $k, $d ) { return $d; }
function taxonomy_exists() { return false; }
function wp_list_pluck( $l, $f ) { return array_column( $l, $f ); }
function wc_get_formatted_variation() { return ''; }
class WC_Product {}
class FakeVariation {
	function __construct( public $id, public $color, public $hex, public $sku, public $qty ) {}
	function exists() { return true; }
	function variation_is_visible() { return true; }
	function get_variation_attributes() { return array( 'attribute_farbe' => $this->color ); }
	function get_meta() { return $this->hex; }
	function managing_stock() { return true; }
	function get_stock_quantity() { return $this->qty; }
	function get_stock_status() { return $this->qty > 0 ? 'instock' : 'outofstock'; }
	function get_id() { return $this->id; }
	function get_sku() { return $this->sku; }
	function get_price_html() { return 'CHF 7.90'; }
	function is_purchasable() { return true; }
	function is_in_stock() { return $this->qty > 0; }
	function get_max_purchase_quantity() { return $this->qty; }
}
class FakeProduct extends WC_Product {
	function is_type( $t ) { return 'variable' === $t; }
	function get_id() { return 1; }
	function get_permalink() { return '/p/'; }
	function get_variation_attributes() { return array( 'Farbe' => array() ); }
	function get_children() { return array( 11, 12, 13 ); }
}
$GLOBALS['vars'] = array(
	11 => new FakeVariation( 11, 'Blau "<x>"', '#1f3e9e', 'D-5', 9 ),
	12 => new FakeVariation( 12, 'Rot', 'f00', 'D-3', 1 ),
	13 => new FakeVariation( 13, 'Schwarz', '#111', 'D-9', 0 ),
);
function wc_get_product( $id ) { return 1 === $id ? new FakeProduct() : ( $GLOBALS['vars'][ $id ] ?? null ); }

require __DIR__ . '/../woo-color-chart/includes/class-wcc-color.php';
require __DIR__ . '/../woo-color-chart/includes/class-wcc-frontend.php';

$html = WCC_Frontend::render( array( 'id' => 1 ) );
$ok   = true;
$checks = array(
	'3 rows'            => 3 === substr_count( $html, 'class="wcc-row' ),
	'red first'         => strpos( $html, 'data-name="Rot"' ) < strpos( $html, 'data-name="Blau' ),
	'escaped name'      => false === strpos( $html, '<x>' ),
	'short hex expanded'=> false !== strpos( $html, 'background-color:#ff0000' ),
	'low stock'         => false !== strpos( $html, 'wcc-stock--lowstock' ),
	'out of stock no form' => 2 === substr_count( $html, '<form class="wcc-cart"' ),
	'family filter'     => false !== strpos( $html, 'data-wcc-family="blue"' ),
);
foreach ( $checks as $k => $v ) {
	echo ( $v ? 'ok   ' : 'FAIL ' ) . $k . "\n";
	$ok = $ok && $v;
}
exit( $ok ? 0 : 1 );
