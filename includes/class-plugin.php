<?php
/**
 * Plugin bootstrap.
 *
 * @package QuickSwitch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class QSwitch_Plugin {

	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function boot(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		QSwitch_Switching::boot();
		QSwitch_Pins::boot();
		QSwitch_Admin_Post::boot();
		QSwitch_Ajax::boot();
		QSwitch_Admin_Bar::boot();
		QSwitch_Users_Table::boot();
		QSwitch_User_Profile::boot();
	}

	public function load_textdomain(): void {
		load_plugin_textdomain(
			'quickswitch',
			false,
			dirname( plugin_basename( QSWITCH_FILE ) ) . '/languages'
		);
	}
}
