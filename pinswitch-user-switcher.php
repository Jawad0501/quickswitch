<?php
/**
 * Plugin Name:       PinSwitch User Switcher
 * Plugin URI:        https://github.com/Jawad0501/pinswitch-user-switcher
 * Description:       Pin test users and switch between accounts in one click while developing WordPress plugins.
 * Version:           1.2.0
 * Author:            jawad0501
 * Author URI:        https://profiles.wordpress.org/jawad0501/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       pinswitch-user-switcher
 * Domain Path:       /languages
 * Requires at least: 6.2
 * Requires PHP:      8.0
 *
 * @package PinSwitch_User_Switcher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PINSWITCH_VERSION', '1.2.0' );
define( 'PINSWITCH_FILE', __FILE__ );
define( 'PINSWITCH_PATH', plugin_dir_path( __FILE__ ) );
define( 'PINSWITCH_META_KEY', 'pinswitch_pinned_users' );

require_once PINSWITCH_PATH . 'includes/class-switching.php';
require_once PINSWITCH_PATH . 'includes/class-pins.php';
require_once PINSWITCH_PATH . 'includes/class-users.php';
require_once PINSWITCH_PATH . 'includes/class-admin-post.php';
require_once PINSWITCH_PATH . 'includes/class-ajax.php';
require_once PINSWITCH_PATH . 'includes/class-admin-bar.php';
require_once PINSWITCH_PATH . 'includes/class-users-table.php';
require_once PINSWITCH_PATH . 'includes/class-user-profile.php';
require_once PINSWITCH_PATH . 'includes/class-plugin.php';

PinSwitch_Plugin::instance()->boot();
