<?php
/**
 * Plugin Name:       LianaAutomation for Gravity Forms
 * Description:       LianaAutomation for Gravity Forms.
 * Version:           1.0.9
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            Liana Technologies Oy
 * Author URI:        https://www.lianatech.com
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0-standalone.html
 * Text Domain:       lianaautomation
 * Domain Path:       /languages
 *
 * PHP Version 7.4
 *
 * @package  LianaAutomation
 * @license  Liana License
 * @link     https://www.lianatech.com
 */

/**
 * Include cookie handler code
 */
require_once dirname( __FILE__ ) . '/includes/lianaautomation-cookie.php';

/**
 * Include Gravity Forms code
 */
require_once dirname( __FILE__ ) . '/includes/lianaautomation-gf.php';

/**
 * Conditionally include admin panel code
 */
if ( is_admin() ) {
	require_once dirname( __FILE__ ) . '/admin/class-lianaautomation-gf.php';
}
