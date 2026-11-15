<?php
/**
 * Pure colour helpers (no WordPress dependency, unit-testable with plain PHP).
 *
 * @package WooColorChart
 */

defined( 'ABSPATH' ) || defined( 'WCC_TESTING' ) || exit;

/**
 * Colour maths: hex normalisation, HSL conversion, colour family, sorting.
 */
class WCC_Color {

	/**
	 * Colour families in display order (used for the filter buttons).
	 *
	 * @var string[]
	 */
	const FAMILIES = array( 'red', 'orange', 'yellow', 'green', 'turquoise', 'blue', 'violet', 'pink', 'brown', 'grey', 'black', 'white' );

	/**
	 * Normalise a user supplied hex value to "#rrggbb" or return '' if invalid.
	 *
	 * @param mixed $value Raw value, e.g. "F00", "#ff0000", " #FF0000 ".
	 * @return string
	 */
	public static function normalize_hex( $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}
		$hex = ltrim( strtolower( trim( $value ) ), '#' );
		if ( preg_match( '/^[0-9a-f]{3}$/', $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		return preg_match( '/^[0-9a-f]{6}$/', $hex ) ? '#' . $hex : '';
	}

	/**
	 * Convert hex to HSL.
	 *
	 * @param string $hex "#rrggbb".
	 * @return array{h:float,s:float,l:float}|null Hue 0-360, saturation/lightness 0-1.
	 */
	public static function hex_to_hsl( $hex ) {
		$hex = self::normalize_hex( $hex );
		if ( '' === $hex ) {
			return null;
		}
		$r   = hexdec( substr( $hex, 1, 2 ) ) / 255;
		$g   = hexdec( substr( $hex, 3, 2 ) ) / 255;
		$b   = hexdec( substr( $hex, 5, 2 ) ) / 255;
		$max = max( $r, $g, $b );
		$min = min( $r, $g, $b );
		$l   = ( $max + $min ) / 2;
		$d   = $max - $min;
		$h   = 0.0;
		$s   = 0.0;
		if ( $d > 0 ) {
			$s = $d / ( 1 - abs( 2 * $l - 1 ) );
			if ( $max === $r ) {
				$h = 60 * fmod( ( $g - $b ) / $d, 6 );
			} elseif ( $max === $g ) {
				$h = 60 * ( ( $b - $r ) / $d + 2 );
			} else {
				$h = 60 * ( ( $r - $g ) / $d + 4 );
			}
			if ( $h < 0 ) {
				$h += 360;
			}
		}
		return array(
			'h' => round( $h, 2 ),
			's' => round( min( 1, $s ), 4 ),
			'l' => round( $l, 4 ),
		);
	}

	/**
	 * Classify a hex colour into a family slug (see FAMILIES).
	 *
	 * @param string $hex Hex colour.
	 * @return string Family slug or '' if hex is invalid.
	 */
	public static function family( $hex ) {
		$hsl = self::hex_to_hsl( $hex );
		if ( null === $hsl ) {
			return '';
		}
		list( $h, $s, $l ) = array( $hsl['h'], $hsl['s'], $hsl['l'] );

		if ( $l <= 0.12 ) {
			return 'black';
		}
		if ( $l >= 0.93 && $s < 0.6 ) {
			return 'white';
		}
		if ( $s < 0.12 ) {
			return $l < 0.2 ? 'black' : ( $l > 0.9 ? 'white' : 'grey' );
		}
		// Dark or desaturated oranges/yellows read as brown.
		if ( $h >= 15 && $h < 50 && ( $l < 0.4 || ( $s < 0.5 && $l < 0.72 ) ) ) {
			return 'brown';
		}
		if ( $h < 12 || $h >= 345 ) {
			return ( $l > 0.75 ) ? 'pink' : 'red';
		}
		if ( $h < 40 ) {
			return 'orange';
		}
		if ( $h < 66 ) {
			return 'yellow';
		}
		if ( $h < 160 ) {
			return 'green';
		}
		if ( $h < 195 ) {
			return 'turquoise';
		}
		if ( $h < 255 ) {
			return 'blue';
		}
		if ( $h < 290 ) {
			return 'violet';
		}
		return 'pink';
	}

	/**
	 * Sort key for "by colour" ordering: chromatic colours by hue, then
	 * neutrals (white -> grey -> black) at the end, like a printed colour chart.
	 *
	 * @param string $hex Hex colour.
	 * @return float
	 */
	public static function hue_sort_key( $hex ) {
		$hsl = self::hex_to_hsl( $hex );
		if ( null === $hsl ) {
			return 9999.0; // No colour: last.
		}
		$family = self::family( $hex );
		if ( in_array( $family, array( 'white', 'grey', 'black' ), true ) ) {
			return 1000 + ( 1 - $hsl['l'] ) * 100; // Light to dark.
		}
		// Reds just below 360 degrees belong at the start of the wheel.
		$h = $hsl['h'] >= 345 ? $hsl['h'] - 360 : $hsl['h'];
		// Hue first, then lightness (light to dark) within a hue band of 1 degree.
		return floor( $h ) + ( 1 - $hsl['l'] ) * 0.99;
	}

	/**
	 * Sort rows (arrays with 'hex', 'name', 'sku') in place.
	 *
	 * @param array  $rows Rows.
	 * @param string $by   hue|name|sku|menu (menu = keep WooCommerce order).
	 * @return array
	 */
	public static function sort_rows( array $rows, $by = 'hue' ) {
		if ( 'menu' === $by ) {
			return $rows;
		}
		usort(
			$rows,
			static function ( $a, $b ) use ( $by ) {
				if ( 'name' === $by || 'sku' === $by ) {
					return strnatcasecmp( (string) $a[ $by ], (string) $b[ $by ] );
				}
				return self::hue_sort_key( $a['hex'] ) <=> self::hue_sort_key( $b['hex'] );
			}
		);
		return $rows;
	}

	/**
	 * Pick black or white text for a given background (WCAG relative luminance).
	 *
	 * @param string $hex Background.
	 * @return string "#000000" or "#ffffff".
	 */
	public static function contrast_text( $hex ) {
		$hex = self::normalize_hex( $hex );
		if ( '' === $hex ) {
			return '#000000';
		}
		$lin = array();
		foreach ( array( 1, 3, 5 ) as $i ) {
			$c     = hexdec( substr( $hex, $i, 2 ) ) / 255;
			$lin[] = $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
		}
		$lum = 0.2126 * $lin[0] + 0.7152 * $lin[1] + 0.0722 * $lin[2];
		return $lum > 0.179 ? '#000000' : '#ffffff';
	}
}
