/**
 * Color Chart for WooCommerce – frontend behaviour.
 * Filter by colour family, search, re-sort, AJAX add to cart.
 * Without JavaScript the table and the per-row forms still work.
 */
( function ( root, factory ) {
	var api = factory();
	if ( typeof module === 'object' && module.exports ) {
		module.exports = api; // Node (unit tests).
	} else {
		root.WooColorChart = api;
		if ( root.document ) {
			if ( root.document.readyState === 'loading' ) {
				root.document.addEventListener( 'DOMContentLoaded', function () { api.initAll( root.document ); } );
			} else {
				api.initAll( root.document );
			}
		}
	}
}( typeof self !== 'undefined' ? self : this, function () {
	'use strict';

	function normalize( s ) {
		return String( s || '' )
			.toLowerCase()
			.normalize( 'NFD' )
			.replace( /[̀-ͯ]/g, '' )
			.trim();
	}

	/**
	 * @param {{family:string,name:string,sku:string}} row
	 * @param {{family:string,query:string}} state
	 */
	function matches( row, state ) {
		if ( state.family && row.family !== state.family ) {
			return false;
		}
		var q = normalize( state.query );
		if ( ! q ) {
			return true;
		}
		return normalize( row.name ).indexOf( q ) !== -1 || normalize( row.sku ).indexOf( q ) !== -1;
	}

	var collator = typeof Intl !== 'undefined' ? new Intl.Collator( undefined, { numeric: true, sensitivity: 'base' } ) : null;

	function compare( a, b, by ) {
		if ( by === 'name' || by === 'sku' ) {
			var x = String( a[ by ] || '' );
			var y = String( b[ by ] || '' );
			return collator ? collator.compare( x, y ) : ( x < y ? -1 : x > y ? 1 : 0 );
		}
		return ( parseFloat( a.hue ) || 0 ) - ( parseFloat( b.hue ) || 0 );
	}

	function rowData( tr ) {
		return {
			el: tr,
			family: tr.getAttribute( 'data-family' ) || '',
			name: tr.getAttribute( 'data-name' ) || '',
			sku: tr.getAttribute( 'data-sku' ) || '',
			hue: tr.getAttribute( 'data-hue' ) || '9999'
		};
	}

	function init( chart ) {
		if ( chart.__wcc ) {
			return;
		}
		chart.__wcc = true;
		var cfg = ( typeof window !== 'undefined' && window.wccConfig ) || { i18n: {} };
		var tbody = chart.querySelector( 'tbody' );
		var rows = Array.prototype.map.call( chart.querySelectorAll( '.wcc-row' ), rowData );
		var empty = chart.querySelector( '.wcc-empty' );
		var live = chart.querySelector( '.wcc-live' );
		var state = { family: '', query: '' };

		function apply() {
			var visible = 0;
			rows.forEach( function ( r ) {
				var ok = matches( r, state );
				r.el.hidden = ! ok;
				if ( ok ) {
					visible++;
				}
			} );
			if ( empty ) {
				empty.hidden = visible > 0;
				empty.textContent = cfg.i18n.noMatch || '';
			}
		}

		chart.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest( '[data-wcc-family]' );
			if ( ! btn ) {
				return;
			}
			state.family = btn.getAttribute( 'data-wcc-family' );
			chart.querySelectorAll( '[data-wcc-family]' ).forEach( function ( b ) {
				var on = b === btn;
				b.classList.toggle( 'is-active', on );
				b.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
			} );
			apply();
		} );

		var search = chart.querySelector( '[data-wcc-search]' );
		if ( search ) {
			search.addEventListener( 'input', function () {
				state.query = search.value;
				apply();
			} );
		}

		var sort = chart.querySelector( '[data-wcc-sort]' );
		if ( sort ) {
			sort.addEventListener( 'change', function () {
				rows.sort( function ( a, b ) { return compare( a, b, sort.value ); } );
				rows.forEach( function ( r ) { tbody.appendChild( r.el ); } );
			} );
		}

		chart.addEventListener( 'submit', function ( e ) {
			var form = e.target.closest( '.wcc-cart' );
			if ( ! form || ! cfg.ajaxUrl || typeof fetch === 'undefined' ) {
				return; // Fallback: normal POST to the product page.
			}
			e.preventDefault();
			var button = form.querySelector( '.wcc-add' );
			var label = button.textContent;
			var data = new FormData( form );
			data.append( 'action', 'wcc_add_to_cart' );
			data.append( 'nonce', cfg.nonce );
			button.disabled = true;
			button.textContent = cfg.i18n.adding || '…';

			fetch( cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data } )
				.then( function ( res ) { return res.json(); } )
				.then( function ( json ) {
					if ( json && json.fragments ) {
						button.textContent = '✓';
						if ( live ) {
							live.textContent = cfg.i18n.added;
						}
						// Let WooCommerce themes refresh the mini cart.
						if ( window.jQuery ) {
							window.jQuery( document.body ).trigger( 'added_to_cart', [ json.fragments, json.cart_hash, window.jQuery( button ) ] );
						}
					} else {
						var msg = ( json && json.data && json.data.message ) || cfg.i18n.error;
						button.textContent = label;
						if ( live ) {
							live.textContent = msg;
						}
						window.alert( msg ); // eslint-disable-line no-alert
					}
				} )
				.catch( function () {
					form.submit(); // Network/JSON problem: fall back to classic submit.
				} )
				.finally( function () {
					button.disabled = false;
					setTimeout( function () { button.textContent = label; }, 1500 );
				} );
		} );

		apply();
	}

	function initAll( doc ) {
		Array.prototype.forEach.call( doc.querySelectorAll( '.wcc' ), init );
	}

	return { matches: matches, compare: compare, normalize: normalize, init: init, initAll: initAll };
} ) );
