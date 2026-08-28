<?php
/**
 * Plugin Name:       QuickSwitch
 * Plugin URI:        https://github.com/jawad0501/quickswitch
 * Description:       Pin test users and switch between accounts in one click while developing WordPress plugins.
 * Version:           1.1.0
 * Author:            jawad0501
 * Author URI:        https://profiles.wordpress.org/jawad0501/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       quickswitch
 * Domain Path:       /languages
 * Requires at least: 6.2
 * Requires PHP:      8.0
 *
 * @package QuickSwitch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'QSWITCH_VERSION', '1.1.0' );
define( 'QSWITCH_FILE', __FILE__ );
define( 'QSWITCH_PATH', plugin_dir_path( __FILE__ ) );
define( 'QSWITCH_META_KEY', 'qswitch_pinned_users' );

require_once QSWITCH_PATH . 'includes/class-switching.php';
require_once QSWITCH_PATH . 'includes/class-pins.php';
require_once QSWITCH_PATH . 'includes/class-users.php';
require_once QSWITCH_PATH . 'includes/class-admin-post.php';
require_once QSWITCH_PATH . 'includes/class-ajax.php';
require_once QSWITCH_PATH . 'includes/class-admin-bar.php';
require_once QSWITCH_PATH . 'includes/class-users-table.php';
require_once QSWITCH_PATH . 'includes/class-user-profile.php';
require_once QSWITCH_PATH . 'includes/class-plugin.php';

QSwitch_Plugin::instance()->boot();
