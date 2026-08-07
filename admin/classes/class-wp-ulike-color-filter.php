<?php
/**
 * Convert a CSS color to a filter chain that tints black/grey icons.
 * Deterministic SPSA (sosuke / StackOverflow algorithm).
 *
 * // @echo HEADER
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'wp_ulike_color_filter' ) ) {
	class wp_ulike_color_filter {

		/** @var array<string,string> Request-level cache. */
		private static $cache = array();

		/**
		 * @param string $color Hex or rgb/rgba color.
		 * @return string Filter value without the `filter:` prefix, or empty.
		 */
		public static function from_color( $color ) {
			$key = strtolower( trim( (string) $color ) );
			if ( '' === $key ) {
				return '';
			}
			if ( isset( self::$cache[ $key ] ) ) {
				return self::$cache[ $key ];
			}

			$rgb = self::parse_rgb( $key );
			if ( ! $rgb ) {
				return '';
			}

			// Private seeded LCG — deterministic, no global mt_srand side effects.
			$seed   = (int) sprintf( '%u', crc32( implode( ',', $rgb ) ) );
			$solver = new self( $rgb[0], $rgb[1], $rgb[2], $seed );
			$result = $solver->solve();

			if ( empty( $result ) ) {
				return '';
			}

			// Force any source icon to black first, then recolor.
			self::$cache[ $key ] = 'brightness(0) saturate(100%) ' . $result;
			return self::$cache[ $key ];
		}

		/** @var float */
		private $target_r;
		/** @var float */
		private $target_g;
		/** @var float */
		private $target_b;
		/** @var array */
		private $target_hsl;
		/** @var array */
		private $work;
		/** @var int LCG state (0..2147483646) */
		private $rng_state;

		private function __construct( $r, $g, $b, $seed ) {
			$this->target_r   = $r;
			$this->target_g   = $g;
			$this->target_b   = $b;
			$this->target_hsl = self::to_hsl( $r, $g, $b );
			$this->work       = array( 0.0, 0.0, 0.0 );
			$this->rng_state  = $seed % 2147483647;
			if ( $this->rng_state <= 0 ) {
				$this->rng_state = 1;
			}
		}

		/**
		 * Numerical Recipes LCG — private, overflow-safe on 64-bit PHP.
		 *
		 * @return float 0..1
		 */
		private function random() {
			$this->rng_state = ( 1103515245 * $this->rng_state + 12345 ) % 2147483647;
			if ( $this->rng_state < 0 ) {
				$this->rng_state += 2147483647;
			}
			return $this->rng_state / 2147483647;
		}

		/**
		 * @param string $color
		 * @return int[]|null
		 */
		private static function parse_rgb( $color ) {
			$color = trim( (string) $color );

			if ( preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color, $m ) ) {
				$hex = $m[1];
				if ( 3 === strlen( $hex ) ) {
					$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
				}
				return array(
					hexdec( substr( $hex, 0, 2 ) ),
					hexdec( substr( $hex, 2, 2 ) ),
					hexdec( substr( $hex, 4, 2 ) ),
				);
			}

			if ( preg_match( '/^rgba?\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})/i', $color, $m ) ) {
				return array(
					max( 0, min( 255, (int) $m[1] ) ),
					max( 0, min( 255, (int) $m[2] ) ),
					max( 0, min( 255, (int) $m[3] ) ),
				);
			}

			return null;
		}

		private function solve() {
			$wide = $this->solve_wide();
			$best = $this->solve_narrow( $wide );
			if ( empty( $best['values'] ) ) {
				return '';
			}
			return $this->to_css( $best['values'] );
		}

		private function solve_wide() {
			$a    = array( 60, 180, 18000, 600, 1.2, 1.2 );
			$best = array( 'loss' => INF, 'values' => null );
			for ( $i = 0; $best['loss'] > 25 && $i < 3; $i++ ) {
				$result = $this->spsa( 5, $a, 15, array( 50, 20, 3750, 50, 100, 100 ), 1000 );
				if ( $result['loss'] < $best['loss'] ) {
					$best = $result;
				}
			}
			return $best;
		}

		private function solve_narrow( $wide ) {
			$a  = $wide['loss'];
			$a1 = $a + 1;
			return $this->spsa(
				$a,
				array( 0.25 * $a1, 0.25 * $a1, $a1, 0.25 * $a1, 0.2 * $a1, 0.2 * $a1 ),
				2,
				$wide['values'] ? $wide['values'] : array( 50, 20, 3750, 50, 100, 100 ),
				500
			);
		}

		private function spsa( $A, $a, $c, $values, $iters ) {
			$best      = null;
			$best_loss = INF;
			$values    = array_values( $values );

			for ( $k = 0; $k < $iters; $k++ ) {
				$ck     = $c / pow( $k + 1, 1 / 6 );
				$deltas = array();
				$high   = array();
				$low    = array();
				for ( $i = 0; $i < 6; $i++ ) {
					$deltas[ $i ] = ( $this->random() > 0.5 ) ? 1 : -1;
					$high[ $i ]   = $values[ $i ] + $ck * $deltas[ $i ];
					$low[ $i ]    = $values[ $i ] - $ck * $deltas[ $i ];
				}

				$loss_diff = $this->loss( $high ) - $this->loss( $low );
				for ( $i = 0; $i < 6; $i++ ) {
					$g            = $loss_diff / ( 2 * $ck ) * $deltas[ $i ];
					$ak           = $a[ $i ] / pow( $A + $k + 1, 1 );
					$values[ $i ] = $this->fix_value( $values[ $i ] - $ak * $g, $i );
				}

				$loss = $this->loss( $values );
				if ( $loss < $best_loss ) {
					$best      = $values;
					$best_loss = $loss;
				}
			}

			return array(
				'values' => $best,
				'loss'   => $best_loss,
			);
		}

		private function fix_value( $value, $idx ) {
			$max = 100;
			if ( 2 === $idx ) {
				$max = 7500;
			} elseif ( 4 === $idx || 5 === $idx ) {
				$max = 200;
			}

			if ( 3 === $idx ) {
				if ( $value > $max ) {
					$value = fmod( $value, $max );
				} elseif ( $value < 0 ) {
					$value = $max + fmod( $value, $max );
				}
			} elseif ( $value < 0 ) {
				$value = 0;
			} elseif ( $value > $max ) {
				$value = $max;
			}

			return $value;
		}

		private function loss( $filters ) {
			$c = &$this->work;
			$c[0] = 0;
			$c[1] = 0;
			$c[2] = 0;

			$this->invert( $filters[0] / 100 );
			$this->sepia( $filters[1] / 100 );
			$this->saturate( $filters[2] / 100 );
			$this->hue_rotate( $filters[3] * 3.6 );
			$this->brightness( $filters[4] / 100 );
			$this->contrast( $filters[5] / 100 );

			$hsl = self::to_hsl( $c[0], $c[1], $c[2] );

			return abs( $c[0] - $this->target_r )
				+ abs( $c[1] - $this->target_g )
				+ abs( $c[2] - $this->target_b )
				+ abs( $hsl['h'] - $this->target_hsl['h'] )
				+ abs( $hsl['s'] - $this->target_hsl['s'] )
				+ abs( $hsl['l'] - $this->target_hsl['l'] );
		}

		private function to_css( $filters ) {
			return sprintf(
				'invert(%d%%) sepia(%d%%) saturate(%d%%) hue-rotate(%ddeg) brightness(%d%%) contrast(%d%%)',
				(int) round( $filters[0] ),
				(int) round( $filters[1] ),
				(int) round( $filters[2] ),
				(int) round( $filters[3] * 3.6 ),
				(int) round( $filters[4] ),
				(int) round( $filters[5] )
			);
		}

		private function multiply( $matrix ) {
			$c    = &$this->work;
			$new_r = $this->clamp( $c[0] * $matrix[0] + $c[1] * $matrix[1] + $c[2] * $matrix[2] );
			$new_g = $this->clamp( $c[0] * $matrix[3] + $c[1] * $matrix[4] + $c[2] * $matrix[5] );
			$new_b = $this->clamp( $c[0] * $matrix[6] + $c[1] * $matrix[7] + $c[2] * $matrix[8] );
			$c[0]  = $new_r;
			$c[1]  = $new_g;
			$c[2]  = $new_b;
		}

		private function invert( $value ) {
			$c = &$this->work;
			$c[0] = $this->clamp( ( $value + ( $c[0] / 255 ) * ( 1 - 2 * $value ) ) * 255 );
			$c[1] = $this->clamp( ( $value + ( $c[1] / 255 ) * ( 1 - 2 * $value ) ) * 255 );
			$c[2] = $this->clamp( ( $value + ( $c[2] / 255 ) * ( 1 - 2 * $value ) ) * 255 );
		}

		private function sepia( $value ) {
			$this->multiply(
				array(
					0.393 + 0.607 * ( 1 - $value ),
					0.769 - 0.769 * ( 1 - $value ),
					0.189 - 0.189 * ( 1 - $value ),
					0.349 - 0.349 * ( 1 - $value ),
					0.686 + 0.314 * ( 1 - $value ),
					0.168 - 0.168 * ( 1 - $value ),
					0.272 - 0.272 * ( 1 - $value ),
					0.534 - 0.534 * ( 1 - $value ),
					0.131 + 0.869 * ( 1 - $value ),
				)
			);
		}

		private function saturate( $value ) {
			$this->multiply(
				array(
					0.213 + 0.787 * $value,
					0.715 - 0.715 * $value,
					0.072 - 0.072 * $value,
					0.213 - 0.213 * $value,
					0.715 + 0.285 * $value,
					0.072 - 0.072 * $value,
					0.213 - 0.213 * $value,
					0.715 - 0.715 * $value,
					0.072 + 0.928 * $value,
				)
			);
		}

		private function hue_rotate( $angle ) {
			$angle = $angle / 180 * M_PI;
			$sin   = sin( $angle );
			$cos   = cos( $angle );
			$this->multiply(
				array(
					0.213 + $cos * 0.787 - $sin * 0.213,
					0.715 - $cos * 0.715 - $sin * 0.715,
					0.072 - $cos * 0.072 + $sin * 0.928,
					0.213 - $cos * 0.213 + $sin * 0.143,
					0.715 + $cos * 0.285 + $sin * 0.140,
					0.072 - $cos * 0.072 - $sin * 0.283,
					0.213 - $cos * 0.213 - $sin * 0.787,
					0.715 - $cos * 0.715 + $sin * 0.715,
					0.072 + $cos * 0.928 + $sin * 0.072,
				)
			);
		}

		private function brightness( $value ) {
			$this->linear( $value );
		}

		private function contrast( $value ) {
			$this->linear( $value, -( 0.5 * $value ) + 0.5 );
		}

		private function linear( $slope = 1, $intercept = 0 ) {
			$c = &$this->work;
			$c[0] = $this->clamp( $c[0] * $slope + $intercept * 255 );
			$c[1] = $this->clamp( $c[1] * $slope + $intercept * 255 );
			$c[2] = $this->clamp( $c[2] * $slope + $intercept * 255 );
		}

		private function clamp( $value ) {
			if ( $value > 255 ) {
				return 255.0;
			}
			if ( $value < 0 ) {
				return 0.0;
			}
			return (float) $value;
		}

		private static function to_hsl( $r, $g, $b ) {
			$r /= 255;
			$g /= 255;
			$b /= 255;
			$max = max( $r, $g, $b );
			$min = min( $r, $g, $b );
			$l   = ( $max + $min ) / 2;
			$h   = 0;
			$s   = 0;

			if ( $max !== $min ) {
				$d = $max - $min;
				$s = $l > 0.5 ? $d / ( 2 - $max - $min ) : $d / ( $max + $min );
				switch ( $max ) {
					case $r:
						$h = ( $g - $b ) / $d + ( $g < $b ? 6 : 0 );
						break;
					case $g:
						$h = ( $b - $r ) / $d + 2;
						break;
					default:
						$h = ( $r - $g ) / $d + 4;
						break;
				}
				$h /= 6;
			}

			return array(
				'h' => $h * 100,
				's' => $s * 100,
				'l' => $l * 100,
			);
		}
	}
}
