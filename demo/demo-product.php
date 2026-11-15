<?php
/**
 * Creates a variable demo product with colour attribute terms + hex values.
 * Run via: wp eval-file demo/demo-product.php
 * Colour names/hex values are illustrative demo data, not official manufacturer values.
 */
$colors = array(
	array( 'Shock White', '#f4f4f0', 'DEMO-1000', 40 ),
	array( 'Signal Yellow', '#ffd500', 'DEMO-1020', 12 ),
	array( 'Orange', '#f28c00', 'DEMO-2040', 2 ),
	array( 'Traffic Red', '#d0101b', 'DEMO-3000', 25 ),
	array( 'Bordeaux', '#6d0f1f', 'DEMO-3120', 0 ),
	array( 'Pink', '#f39bc3', 'DEMO-4010', 8 ),
	array( 'Purple', '#6a2c91', 'DEMO-4200', 15 ),
	array( 'Ultramarine', '#1f3e9e', 'DEMO-5000', 30 ),
	array( 'Light Blue', '#62b6e6', 'DEMO-5110', 6 ),
	array( 'Turquoise', '#00a3a6', 'DEMO-6020', 9 ),
	array( 'Grass Green', '#3d9b35', 'DEMO-6100', 18 ),
	array( 'Olive', '#6b6b2a', 'DEMO-6200', 4 ),
	array( 'Chocolate', '#5a3a22', 'DEMO-8010', 7 ),
	array( 'Sand', '#c9a66b', 'DEMO-8100', 11 ),
	array( 'Grey', '#8c8c8c', 'DEMO-7000', 20 ),
	array( 'Black', '#111111', 'DEMO-9000', 50 ),
);

// Global attribute "Farbe" (pa_farbe).
$attr_id = wc_attribute_taxonomy_id_by_name( 'farbe' );
if ( ! $attr_id ) {
	$attr_id = wc_create_attribute( array( 'name' => 'Farbe', 'slug' => 'farbe', 'type' => 'select' ) );
}
$tax = 'pa_farbe';
if ( ! taxonomy_exists( $tax ) ) {
	register_taxonomy( $tax, array( 'product' ), array( 'hierarchical' => false ) );
}

$term_ids = array();
foreach ( $colors as $c ) {
	$t = term_exists( $c[0], $tax );
	if ( ! $t ) {
		$t = wp_insert_term( $c[0], $tax );
	}
	$term_id = (int) ( is_array( $t ) ? $t['term_id'] : $t );
	update_term_meta( $term_id, 'wcc_hex', $c[1] );
	$term_ids[] = $term_id;
}

$product = new WC_Product_Variable();
$product->set_name( 'Demo Spraydose 400ml' );
$product->set_slug( 'demo-spray-400ml' );
$product->set_status( 'publish' );
$product->set_description( 'Demo product for Color Chart for WooCommerce. Colours and codes are sample data.' );
$attribute = new WC_Product_Attribute();
$attribute->set_id( $attr_id );
$attribute->set_name( $tax );
$attribute->set_options( $term_ids );
$attribute->set_visible( true );
$attribute->set_variation( true );
$product->set_attributes( array( $attribute ) );
$product->update_meta_data( '_wcc_auto', 'yes' );
$product_id = $product->save();

foreach ( $colors as $c ) {
	$term = get_term_by( 'name', $c[0], $tax );
	$v    = new WC_Product_Variation();
	$v->set_parent_id( $product_id );
	$v->set_attributes( array( $tax => $term->slug ) );
	$v->set_regular_price( '7.90' );
	$v->set_sku( $c[2] );
	$v->set_manage_stock( true );
	$v->set_stock_quantity( $c[3] );
	$v->save();
}
WC_Product_Variable::sync( $product_id );

// A page that uses the shortcode as well.
wp_insert_post(
	array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Farbkarte',
		'post_name'    => 'farbkarte',
		'post_content' => '[color_chart id="' . $product_id . '"]',
	)
);
WP_CLI::success( "Demo product $product_id created." );
