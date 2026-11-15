/**
 * Block editor UI for the Color Chart block (no build step required).
 * Rendering happens server-side (ServerSideRender) so editor and frontend match.
 */
( function ( wp ) {
	if ( ! wp || ! wp.blocks ) {
		return;
	}
	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var c = wp.components;
	var ServerSideRender = wp.serverSideRender;

	wp.blocks.registerBlockType( 'woo-color-chart/color-chart', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						c.PanelBody,
						{ title: __( 'Colour chart', 'woo-color-chart' ) },
						el( c.TextControl, {
							label: __( 'Product ID (0 = current product)', 'woo-color-chart' ),
							type: 'number',
							value: a.productId,
							onChange: function ( v ) { set( { productId: parseInt( v, 10 ) || 0 } ); }
						} ),
						el( c.SelectControl, {
							label: __( 'Default sort', 'woo-color-chart' ),
							value: a.sort,
							options: [
								{ label: __( 'By colour (hue)', 'woo-color-chart' ), value: 'hue' },
								{ label: __( 'By name', 'woo-color-chart' ), value: 'name' },
								{ label: __( 'By code / SKU', 'woo-color-chart' ), value: 'sku' },
								{ label: __( 'WooCommerce order', 'woo-color-chart' ), value: 'menu' }
							],
							onChange: function ( v ) { set( { sort: v } ); }
						} ),
						el( c.ToggleControl, {
							label: __( 'Colour family filter', 'woo-color-chart' ),
							checked: a.showFilter,
							onChange: function ( v ) { set( { showFilter: v } ); }
						} ),
						el( c.ToggleControl, {
							label: __( 'Search field', 'woo-color-chart' ),
							checked: a.showSearch,
							onChange: function ( v ) { set( { showSearch: v } ); }
						} ),
						el( c.ToggleControl, {
							label: __( 'Stock column', 'woo-color-chart' ),
							checked: a.showStock,
							onChange: function ( v ) { set( { showStock: v } ); }
						} )
					)
				),
				el( ServerSideRender, { block: 'woo-color-chart/color-chart', attributes: a } )
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp );
