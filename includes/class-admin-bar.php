<?php
/**
 * Admin bar quick switch menu + custom panel.
 *
 * @package PinSwitch_User_Switcher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PinSwitch_Admin_Bar {

	public static function boot(): void {
		add_action( 'admin_bar_menu', array( __CLASS__, 'register_nodes' ), 80 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function enqueue_assets(): void {
		if ( ! is_admin() || ! is_admin_bar_showing() || ! PinSwitch_Switching::can_manage() ) {
			return;
		}

		wp_enqueue_style(
			'pinswitch-admin-bar',
			plugins_url( 'assets/admin-bar.css', PINSWITCH_FILE ),
			array(),
			PINSWITCH_VERSION
		);

		wp_enqueue_script(
			'pinswitch-admin-bar',
			plugins_url( 'assets/admin-bar.js', PINSWITCH_FILE ),
			array(),
			PINSWITCH_VERSION,
			true
		);

		wp_localize_script(
			'pinswitch-admin-bar',
			'pinswitchPanel',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'pinswitch_panel' ),
				'i18n'    => array(
					'pinned'            => __( 'Pinned', 'pinswitch-user-switcher' ),
					'all'               => __( 'All users', 'pinswitch-user-switcher' ),
					'searchPlaceholder' => __( 'Search username or email…', 'pinswitch-user-switcher' ),
					'switchTo'          => __( 'Switch To', 'pinswitch-user-switcher' ),
					'pin'               => __( 'Pin', 'pinswitch-user-switcher' ),
					'pinUser'           => __( 'Pin user', 'pinswitch-user-switcher' ),
					'unpin'             => __( 'Unpin', 'pinswitch-user-switcher' ),
					'unpinUser'         => __( 'Unpin user', 'pinswitch-user-switcher' ),
					'loading'           => __( 'Loading…', 'pinswitch-user-switcher' ),
					'emptyPinned'       => __( 'No pinned users yet. Pin accounts from the Users screen or the All users tab.', 'pinswitch-user-switcher' ),
					'emptySearch'       => __( 'No users found.', 'pinswitch-user-switcher' ),
					'end'               => __( 'End of list', 'pinswitch-user-switcher' ),
				),
			)
		);
	}

	public static function register_nodes( WP_Admin_Bar $admin_bar ): void {
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		$old_user = PinSwitch_Switching::get_old_user();

		if ( $old_user instanceof WP_User ) {
			self::register_switched_nodes( $admin_bar, $old_user );
		}

		self::register_profile_menu_nodes( $admin_bar, $old_user );

		if ( is_admin() && PinSwitch_Switching::can_manage() ) {
			$admin_bar->add_node(
				array(
					'id'    => 'pinswitch-menu',
					'title' => __( 'Switch User', 'pinswitch-user-switcher' ),
					'href'  => '#',
					'meta'  => array(
						'class' => 'pinswitch-menu-trigger',
					),
				)
			);
		}
	}

	private static function register_switched_nodes( WP_Admin_Bar $admin_bar, WP_User $old_user ): void {
		$current = wp_get_current_user();

		$admin_bar->add_node(
			array(
				'id'    => 'pinswitch-switched',
				'title' => sprintf(
					/* translators: %s: current switched-in user display name */
					__( 'Switched to %s', 'pinswitch-user-switcher' ),
					$current->display_name
				),
				'href'  => false,
				'meta'  => array(
					'class' => 'pinswitch-switched',
				),
			)
		);

		$admin_bar->add_node(
			array(
				'parent' => 'pinswitch-switched',
				'id'     => 'pinswitch-switch-back',
				'title'  => PinSwitch_Switching::switch_back_message( $old_user ),
				'href'   => PinSwitch_Switching::switch_back_link_url( $old_user ),
			)
		);

		$admin_bar->add_node(
			array(
				'parent' => 'pinswitch-switched',
				'id'     => 'pinswitch-switch-off',
				'title'  => __( 'Switch Off', 'pinswitch-user-switcher' ),
				'href'   => PinSwitch_Switching::switch_off_link_url(),
			)
		);
	}

	/**
	 * @param false|WP_User $old_user
	 */
	private static function register_profile_menu_nodes( WP_Admin_Bar $admin_bar, false|WP_User $old_user ): void {
		if ( ! $admin_bar->get_node( 'user-actions' ) ) {
			return;
		}

		// When switched, Switch Back / Switch Off live under "Switched to …" in the admin bar.
		if ( $old_user instanceof WP_User || ! PinSwitch_Switching::can_manage() ) {
			return;
		}

		$admin_bar->add_node(
			array(
				'parent' => 'user-actions',
				'id'     => 'pinswitch-profile-switch-off',
				'title'  => __( 'Switch Off', 'pinswitch-user-switcher' ),
				'href'   => PinSwitch_Switching::switch_off_link_url(),
			)
		);
	}
}
