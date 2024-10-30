<?php
/**
 * LianaAutomation cookie (avoids redeclaration by other LianaAutomation plugins)
 *
 * PHP Version 7.4
 *
 * @package  LianaAutomation
 * @license  https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL-3.0-or-later
 * @link     https://www.lianatech.com
 */

if ( ! function_exists( 'liana_automation_cookie' ) && ! function_exists( 'Liana_Automation_cookie' ) ) {
	/**
	 * Cookie Function
	 *
	 * Provides liana_t cookie functionality
	 *
	 * @return void
	 */
	function liana_automation_cookie(): void {
		// Generates liana_t tracking cookie if not set.
		if ( isset( $_COOKIE['liana_t'] ) ) {
			$liana_t = sanitize_key( $_COOKIE['liana_t'] );
		} else {
			$liana_t = uniqid( '', true );
			setcookie(
				'liana_t',
				$liana_t,
				time() + 315569260,
				COOKIEPATH,
				COOKIE_DOMAIN
			);
		}
	}

	add_action( 'wp_head', 'liana_automation_cookie', 1, 0 );
}
