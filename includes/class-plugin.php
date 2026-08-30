<?php
/**
 * Plugin bootstrap.
 *
 * @package PinSwitch_User_Switcher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PinSwitch_Plugin {

	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function boot(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		PinSwitch_Switching::boot();
		PinSwitch_Pins::boot();
		PinSwitch_Admin_Post::boot();
		PinSwitch_Ajax::boot();
		PinSwitch_Admin_Bar::boot();
		PinSwitch_Users_Table::boot();
		PinSwitch_User_Profile::boot();
	}

	public function load_textdomain(): void {
		load_plugin_textdomain(
			'pinswitch-user-switcher',
			false,
			dirname( plugin_basename( PINSWITCH_FILE ) ) . '/languages'
		);
	}
}
