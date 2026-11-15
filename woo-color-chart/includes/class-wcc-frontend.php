<?php
/**
 * Frontend: data collection, shortcode, block render, AJAX add to cart.
 *
 * @package WooColorChart
 */

defined( 'ABSPATH' ) || exit;

/**
 * Frontend output.
 */
class WCC_Frontend {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_shortcode( 'color_chart', array( __CLASS__, 'shortcode' ) );
		add_action( 'init', array( __CLASS__, 'register_assets' ) );
		add_action( 'init', array( __CLASS__, 'register_block' ) );
		add_action( 'woocommerce_after_single_product_summary', array( __CLASS__, 'auto_insert' ), 5 );
		add_action( 'wp_ajax_wcc_add_to_cart', array( __CLASS__, 'ajax_add_to_cart' ) );
		add_action( 'wp_ajax_nopriv_wcc_add_to_cart', array( __CLASS__, 'ajax_add_to_cart' ) );
	}

	/**
	 * Translated family labels.
	 *
	 * @return array<string,string>
	 */
	public static function family_labels() {
		return array(
			'red'       => __( 'Red', 'woo-color-chart' ),
			'orange'    => __( 'Orange', 'woo-color-chart' ),
			'yellow'    => __( 'Yellow', 'woo-color-chart' ),
			'green'     => __( 'Green', 'woo-color-chart' ),
			'turquoise' => __( 'Turquoise', 'woo-color-chart' ),
			'blue'      => __( 'Blue', 'woo-color-chart' ),
			'violet'    => __( 'Violet', 'woo-color-chart' ),
			'pink'      => __( 'Pink', 'woo-color-chart' ),
			'brown'     => __( 'Brown', 'woo-color-chart' ),
			'grey'      => __( 'Grey', 'woo-color-chart' ),
			'black'     => __( 'Black', 'woo-color-chart' ),
			'white'     => __( 'White', 'woo-color-chart' ),
		);
	}

	/**
	 * Register (not enqueue) frontend assets.
	 */
	public static function register_assets() {
		wp_register_style( 'woo-color-chart', WCC_URL . 'assets/css/color-chart.css', array(), WCC_VERSION );
		wp_register_script( 'woo-color-chart', WCC_URL . 'assets/js/color-chart.js', array(), WCC_VERSION, true );
		wp_localize_script(
			'woo-color-chart',
			'wccConfig',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wcc_add_to_cart' ),
				'i18n'    => array(
					'added'   => __( 'Added to cart', 'woo-color-chart' ),
					'error'   => __( 'Could not add to cart.', 'woo-color-chart' ),
					'adding'  => __( 'Adding…', 'woo-color-chart' ),
					'noMatch' => __( 'No colours match your filter.', 'woo-color-chart' ),
				),
			)
		);
	}

	/**
	 * Register the Gutenberg block (server-side rendered, editor script without build step).
	 */
	public static function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}
		wp_register_script(
			'woo-color-chart-block-editor',
			WCC_URL . 'blocks/color-chart/editor.js',
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render', 'wp-i18n' ),
			WCC_VERSION,
			true
		);
		register_block_type(
			WCC_DIR . 'blocks/color-chart',
			array( 'render_callback' => array( __CLASS__, 'render_block' ) )
		);
	}

	/**
	 * Block render callback.
	 *
	 * @param array $attrs Block attributes.
	 * @return string
	 */
	public static function render_block( $attrs ) {
		return self::render(
			array(
				'id'     => isset( $attrs['productId'] ) ? (int) $attrs['productId'] : 0,
				'sort'   => isset( $attrs['sort'] ) ? $attrs['sort'] : 'hue',
				'filter' => ! empty( $attrs['showFilter'] ) ? 'yes' : 'no',
				'search' => ! empty( $attrs['showSearch'] ) ? 'yes' : 'no',
				'stock'  => ! empty( $attrs['showStock'] ) ? 'yes' : 'no',
			)
		);
	}

	/**
	 * Shortcode [color_chart id="123" sort="hue|name|sku|menu" filter="yes" search="yes" stock="yes"].
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'     => 0,
				'sort'   => 'hue',
				'filter' => 'yes',
				'search' => 'yes',
				'stock'  => 'yes',
			),
			$atts,
			'color_chart'
		);
		return self::render( $atts );
	}

	/**
	 * Auto insert on single product page if enabled for the product.
	 */
	public static function auto_insert() {
		global $product;
		if ( $product instanceof WC_Product && $product->is_type( 'variable' ) && 'yes' === $product->get_meta( '_wcc_auto' ) ) {
			echo self::render( array( 'id' => $product->get_id() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in render().
		}
	}

	/**
	 * Find the attribute that holds the colour (e.g. pa_farbe, pa_color, "Colour").
	 *
	 * @param WC_Product_Variable $product Product.
	 * @return string Attribute key as used in variation attributes (sanitized name), or ''.
	 */
	public static function color_attribute( $product ) {
		$keys = array();
		foreach ( $product->get_variation_attributes() as $name => $options ) {
			$keys[] = $name;
		}
		$found = '';
		foreach ( $keys as $name ) {
			if ( preg_match( '/colou?r|farbe|couleur|colore|kleur|shade|ton/i', $name ) ) {
				$found = $name;
				break;
			}
		}
		if ( '' === $found && ! empty( $keys ) ) {
			$found = $keys[0];
		}
		/**
		 * Filter the attribute used as colour attribute.
		 *
		 * @param string              $found   Attribute name (e.g. "pa_farbe").
		 * @param WC_Product_Variable $product Product.
		 */
		return (string) apply_filters( 'wcc_color_attribute', $found, $product );
	}

	/**
	 * Collect table rows for a variable product.
	 *
	 * @param WC_Product_Variable $product Product.
	 * @return array
	 */
	public static function get_rows( $product ) {
		$color_attr = self::color_attribute( $product );
		$attr_key   = 'attribute_' . sanitize_title( $color_attr );
		$is_tax     = taxonomy_exists( $color_attr );
		$threshold  = (int) get_option( 'woocommerce_notify_low_stock_amount', 2 );
		$rows       = array();

		foreach ( $product->get_children() as $child_id ) {
			$variation = wc_get_product( $child_id );
			if ( ! $variation || ! $variation->exists() || ! $variation->variation_is_visible() ) {
				continue;
			}
			$attributes = $variation->get_variation_attributes(); // attribute_pa_x => slug.
			$value      = isset( $attributes[ $attr_key ] ) ? $attributes[ $attr_key ] : '';
			$name       = $value;
			$hex        = WCC_Color::normalize_hex( $variation->get_meta( '_wcc_hex' ) );

			if ( $is_tax && '' !== $value ) {
				$term = get_term_by( 'slug', $value, $color_attr );
				if ( $term ) {
					$name = $term->name;
					if ( '' === $hex ) {
						$hex = WCC_Color::normalize_hex( get_term_meta( $term->term_id, 'wcc_hex', true ) );
					}
				}
			}
			if ( '' === $name ) {
				$name = wc_get_formatted_variation( $variation, true, false, false );
			}

			$has_any = in_array( '', $attributes, true );
			$qty     = $variation->managing_stock() ? (int) $variation->get_stock_quantity() : null;
			$status  = $variation->get_stock_status();
			if ( 'instock' === $status && null !== $qty && $qty <= $threshold ) {
				$status = 'lowstock';
			}

			$rows[] = array(
				'id'          => $variation->get_id(),
				'name'        => $name,
				'sku'         => $variation->get_sku(),
				'hex'         => $hex,
				'family'      => WCC_Color::family( $hex ),
				'hue'         => WCC_Color::hue_sort_key( $hex ),
				'stock'       => $status,
				'qty'         => $qty,
				'price_html'  => $variation->get_price_html(),
				'purchasable' => $variation->is_purchasable() && $variation->is_in_stock() && ! $has_any,
				'attributes'  => $attributes,
				'max_qty'     => $variation->get_max_purchase_quantity(),
			);
		}
		/**
		 * Filter chart rows before sorting.
		 *
		 * @param array               $rows    Rows.
		 * @param WC_Product_Variable $product Product.
		 */
		return apply_filters( 'wcc_rows', $rows, $product );
	}

	/**
	 * Render the chart.
	 *
	 * @param array $args id, sort, filter, search, stock.
	 * @return string
	 */
	public static function render( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'id'     => 0,
				'sort'   => 'hue',
				'filter' => 'yes',
				'search' => 'yes',
				'stock'  => 'yes',
			)
		);
		$id = (int) $args['id'];
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$product = $id ? wc_get_product( $id ) : null;
		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return current_user_can( 'edit_products' )
				? '<p class="wcc-notice">' . esc_html__( 'Colour chart: please choose a variable product.', 'woo-color-chart' ) . '</p>'
				: '';
		}

		$sort = in_array( $args['sort'], array( 'hue', 'name', 'sku', 'menu' ), true ) ? $args['sort'] : 'hue';
		$rows = WCC_Color::sort_rows( self::get_rows( $product ), $sort );
		if ( empty( $rows ) ) {
			return '';
		}

		wp_enqueue_style( 'woo-color-chart' );
		wp_enqueue_script( 'woo-color-chart' );

		$labels      = self::family_labels();
		$present     = array_values( array_unique( array_filter( wp_list_pluck( $rows, 'family' ) ) ) );
		$present     = array_values( array_intersect( WCC_Color::FAMILIES, $present ) );
		$show_stock  = 'yes' === $args['stock'];
		$stock_label = array(
			'instock'     => __( 'In stock', 'woo-color-chart' ),
			'lowstock'    => __( 'Low stock', 'woo-color-chart' ),
			'outofstock'  => __( 'Out of stock', 'woo-color-chart' ),
			'onbackorder' => __( 'On backorder', 'woo-color-chart' ),
		);
		$action      = $product->get_permalink();

		ob_start();
		?>
		<div class="wcc" data-product="<?php echo esc_attr( $product->get_id() ); ?>">
			<div class="wcc-toolbar">
				<?php if ( 'yes' === $args['search'] ) : ?>
					<label class="wcc-search">
						<span class="screen-reader-text"><?php esc_html_e( 'Search colour name or code', 'woo-color-chart' ); ?></span>
						<input type="search" placeholder="<?php esc_attr_e( 'Search colour or code…', 'woo-color-chart' ); ?>" data-wcc-search />
					</label>
				<?php endif; ?>
				<label class="wcc-sort">
					<span><?php esc_html_e( 'Sort', 'woo-color-chart' ); ?></span>
					<select data-wcc-sort>
						<option value="hue" <?php selected( $sort, 'hue' ); ?>><?php esc_html_e( 'By colour', 'woo-color-chart' ); ?></option>
						<option value="name" <?php selected( $sort, 'name' ); ?>><?php esc_html_e( 'By name', 'woo-color-chart' ); ?></option>
						<option value="sku" <?php selected( $sort, 'sku' ); ?>><?php esc_html_e( 'By code', 'woo-color-chart' ); ?></option>
					</select>
				</label>
				<?php if ( 'yes' === $args['filter'] && count( $present ) > 1 ) : ?>
					<div class="wcc-families" role="group" aria-label="<?php esc_attr_e( 'Filter by colour family', 'woo-color-chart' ); ?>">
						<button type="button" class="wcc-family is-active" data-wcc-family="" aria-pressed="true"><?php esc_html_e( 'All', 'woo-color-chart' ); ?></button>
						<?php foreach ( $present as $fam ) : ?>
							<button type="button" class="wcc-family wcc-family--<?php echo esc_attr( $fam ); ?>" data-wcc-family="<?php echo esc_attr( $fam ); ?>" aria-pressed="false"><?php echo esc_html( $labels[ $fam ] ); ?></button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<table class="wcc-table">
				<thead>
					<tr>
						<th scope="col" class="wcc-col-swatch"><span class="screen-reader-text"><?php esc_html_e( 'Colour', 'woo-color-chart' ); ?></span></th>
						<th scope="col"><?php esc_html_e( 'Name', 'woo-color-chart' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Code', 'woo-color-chart' ); ?></th>
						<?php if ( $show_stock ) : ?>
							<th scope="col"><?php esc_html_e( 'Stock', 'woo-color-chart' ); ?></th>
						<?php endif; ?>
						<th scope="col"><?php esc_html_e( 'Price', 'woo-color-chart' ); ?></th>
						<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Add to cart', 'woo-color-chart' ); ?></span></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<tr class="wcc-row wcc-stock--<?php echo esc_attr( $row['stock'] ); ?>"
						data-family="<?php echo esc_attr( $row['family'] ); ?>"
						data-hue="<?php echo esc_attr( $row['hue'] ); ?>"
						data-name="<?php echo esc_attr( $row['name'] ); ?>"
						data-sku="<?php echo esc_attr( $row['sku'] ); ?>">
						<td class="wcc-col-swatch">
							<span class="wcc-swatch<?php echo '' === $row['hex'] ? ' wcc-swatch--empty' : ''; ?>"
								<?php if ( '' !== $row['hex'] ) : ?>
									style="background-color:<?php echo esc_attr( $row['hex'] ); ?>;color:<?php echo esc_attr( WCC_Color::contrast_text( $row['hex'] ) ); ?>"
								<?php endif; ?>
								title="<?php echo esc_attr( $row['hex'] ); ?>"></span>
						</td>
						<td class="wcc-col-name"><?php echo esc_html( $row['name'] ); ?></td>
						<td class="wcc-col-sku"><?php echo esc_html( $row['sku'] ); ?></td>
						<?php if ( $show_stock ) : ?>
							<td class="wcc-col-stock"><span class="wcc-stock"><?php echo esc_html( $stock_label[ $row['stock'] ] ?? $row['stock'] ); ?></span></td>
						<?php endif; ?>
						<td class="wcc-col-price"><?php echo wp_kses_post( $row['price_html'] ); ?></td>
						<td class="wcc-col-cart">
							<?php if ( $row['purchasable'] ) : ?>
								<form class="wcc-cart" method="post" action="<?php echo esc_url( $action ); ?>">
									<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" />
									<input type="hidden" name="product_id" value="<?php echo esc_attr( $product->get_id() ); ?>" />
									<input type="hidden" name="variation_id" value="<?php echo esc_attr( $row['id'] ); ?>" />
									<?php foreach ( $row['attributes'] as $key => $val ) : ?>
										<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $val ); ?>" />
									<?php endforeach; ?>
									<input class="wcc-qty" type="number" name="quantity" value="1" min="1" step="1"
										<?php echo $row['max_qty'] > 0 ? 'max="' . esc_attr( $row['max_qty'] ) . '"' : ''; ?>
										aria-label="<?php echo esc_attr( sprintf( /* translators: %s colour name */ __( 'Quantity for %s', 'woo-color-chart' ), $row['name'] ) ); ?>" />
									<button type="submit" class="button wcc-add"><?php esc_html_e( 'Add', 'woo-color-chart' ); ?></button>
								</form>
							<?php else : ?>
								<span class="wcc-unavailable">&ndash;</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p class="wcc-empty" hidden></p>
			<div class="wcc-live screen-reader-text" aria-live="polite"></div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * AJAX add to cart (progressive enhancement of the per-row form).
	 */
	public static function ajax_add_to_cart() {
		check_ajax_referer( 'wcc_add_to_cart', 'nonce' );

		$product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
		$quantity     = isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : 1;
		$variation    = $variation_id ? wc_get_product( $variation_id ) : null;

		if ( ! $variation || ! $variation->is_type( 'variation' ) || $variation->get_parent_id() !== $product_id || $quantity < 1 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product.', 'woo-color-chart' ) ), 400 );
		}

		// Attributes are always taken from the stored variation, never from the request.
		$attributes = $variation->get_variation_attributes();

		if ( WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $attributes ) ) {
			wc_clear_notices();
			do_action( 'woocommerce_ajax_added_to_cart', $product_id );
			WC_AJAX::get_refreshed_fragments(); // Sends JSON (fragments, cart_hash) and exits.
		}

		$messages = wp_list_pluck( wc_get_notices( 'error' ), 'notice' );
		wc_clear_notices();
		wp_send_json_error( array( 'message' => wp_strip_all_tags( implode( ' ', $messages ) ) ), 400 );
	}
}
