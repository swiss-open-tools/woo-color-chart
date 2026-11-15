<?php
/**
 * Admin UI: hex field per variation, hex field per attribute term,
 * per-product switch to show the chart automatically.
 *
 * @package WooColorChart
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin hooks.
 */
class WCC_Admin {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_product_after_variable_attributes', array( __CLASS__, 'variation_field' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( __CLASS__, 'save_variation_field' ), 10, 2 );
		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'product_fields' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_product_fields' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_term_fields' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	/**
	 * Load WP colour picker on product and term screens.
	 *
	 * @param string $hook Current admin page.
	 */
	public static function enqueue( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php', 'edit-tags.php', 'term.php' ), true ) ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script(
			'wp-color-picker',
			'jQuery(function($){function i(c){$(c).find(".wcc-hex").not(".wp-color-picker").wpColorPicker();}i(document);$(document).on("woocommerce_variations_loaded woocommerce_variations_added",function(){i(document);});$(document).on("change",".wcc-hex",function(){$(this).closest(".woocommerce_variation").addClass("variation-needs-update");$("button.save-variation-changes").prop("disabled",false);});});'
		);
	}

	/**
	 * Hex field inside each variation panel.
	 *
	 * @param int     $loop           Index.
	 * @param array   $variation_data Data.
	 * @param WP_Post $variation      Variation post.
	 */
	public static function variation_field( $loop, $variation_data, $variation ) {
		$value = get_post_meta( $variation->ID, '_wcc_hex', true );
		printf(
			'<p class="form-row form-row-full"><label for="wcc_hex_%1$d">%2$s</label><br><input type="text" class="wcc-hex" id="wcc_hex_%1$d" name="wcc_hex[%1$d]" value="%3$s" placeholder="#ff0000" /><br><small>%4$s</small></p>',
			(int) $loop,
			esc_html__( 'Colour chart: hex colour', 'woo-color-chart' ),
			esc_attr( $value ),
			esc_html__( 'Leave empty to use the colour stored on the attribute term.', 'woo-color-chart' )
		);
	}

	/**
	 * Save variation hex.
	 *
	 * @param int $variation_id Variation ID.
	 * @param int $loop         Index.
	 */
	public static function save_variation_field( $variation_id, $loop ) {
		// Nonce is verified by WooCommerce (save-variations action) before this hook fires.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['wcc_hex'][ $loop ] ) || ! current_user_can( 'edit_product', $variation_id ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$hex = WCC_Color::normalize_hex( sanitize_text_field( wp_unslash( $_POST['wcc_hex'][ $loop ] ) ) );
		if ( '' === $hex ) {
			delete_post_meta( $variation_id, '_wcc_hex' );
		} else {
			update_post_meta( $variation_id, '_wcc_hex', $hex );
		}
	}

	/**
	 * Product level options (General tab).
	 */
	public static function product_fields() {
		echo '<div class="options_group show_if_variable">';
		woocommerce_wp_checkbox(
			array(
				'id'          => '_wcc_auto',
				'label'       => __( 'Colour chart', 'woo-color-chart' ),
				'description' => __( 'Show the colour chart automatically below the product summary.', 'woo-color-chart' ),
			)
		);
		echo '</div>';
	}

	/**
	 * Save product level options.
	 *
	 * @param int $post_id Product ID.
	 */
	public static function save_product_fields( $post_id ) {
		// Nonce verified by WooCommerce core before woocommerce_process_product_meta.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_post_meta( $post_id, '_wcc_auto', isset( $_POST['_wcc_auto'] ) ? 'yes' : 'no' );
	}

	/**
	 * Add a hex field to every global product attribute term (pa_*).
	 */
	public static function register_term_fields() {
		if ( ! function_exists( 'wc_get_attribute_taxonomy_names' ) ) {
			return;
		}
		foreach ( wc_get_attribute_taxonomy_names() as $tax ) {
			add_action( "{$tax}_add_form_fields", array( __CLASS__, 'term_add_field' ) );
			add_action( "{$tax}_edit_form_fields", array( __CLASS__, 'term_edit_field' ) );
			add_action( "created_{$tax}", array( __CLASS__, 'save_term_field' ) );
			add_action( "edited_{$tax}", array( __CLASS__, 'save_term_field' ) );
		}
	}

	/**
	 * Field on "add term" screen.
	 */
	public static function term_add_field() {
		wp_nonce_field( 'wcc_term_hex', 'wcc_term_nonce' );
		printf(
			'<div class="form-field"><label for="wcc_term_hex">%s</label><input type="text" class="wcc-hex" id="wcc_term_hex" name="wcc_term_hex" value="" /></div>',
			esc_html__( 'Colour chart: hex colour', 'woo-color-chart' )
		);
	}

	/**
	 * Field on "edit term" screen.
	 *
	 * @param WP_Term $term Term.
	 */
	public static function term_edit_field( $term ) {
		wp_nonce_field( 'wcc_term_hex', 'wcc_term_nonce' );
		printf(
			'<tr class="form-field"><th scope="row"><label for="wcc_term_hex">%s</label></th><td><input type="text" class="wcc-hex" id="wcc_term_hex" name="wcc_term_hex" value="%s" /></td></tr>',
			esc_html__( 'Colour chart: hex colour', 'woo-color-chart' ),
			esc_attr( get_term_meta( $term->term_id, 'wcc_hex', true ) )
		);
	}

	/**
	 * Save term hex.
	 *
	 * @param int $term_id Term ID.
	 */
	public static function save_term_field( $term_id ) {
		if ( ! isset( $_POST['wcc_term_nonce'], $_POST['wcc_term_hex'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wcc_term_nonce'] ) ), 'wcc_term_hex' )
			|| ! current_user_can( 'manage_product_terms' ) ) {
			return;
		}
		$hex = WCC_Color::normalize_hex( sanitize_text_field( wp_unslash( $_POST['wcc_term_hex'] ) ) );
		if ( '' === $hex ) {
			delete_term_meta( $term_id, 'wcc_hex' );
		} else {
			update_term_meta( $term_id, 'wcc_hex', $hex );
		}
	}
}
