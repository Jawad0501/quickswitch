<?php
/**
 * Admin bar quick switch menu + custom panel.
 *
 * @package QuickSwitch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class QSwitch_Admin_Bar {

	public static function boot(): void {
		add_action( 'admin_bar_menu', array( __CLASS__, 'register_nodes' ), 80 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function enqueue_assets(): void {
		if ( ! is_admin() || ! is_admin_bar_showing() || ! QSwitch_Switching::can_manage() ) {
			return;
		}

		wp_enqueue_style(
			'qswitch-admin-bar',
			plugins_url( 'assets/admin-bar.css', QSWITCH_FILE ),
			array(),
			QSWITCH_VERSION
		);

		wp_enqueue_script(
			'qswitch-admin-bar',
			plugins_url( 'assets/admin-bar.js', QSWITCH_FILE ),
			array(),
			QSWITCH_VERSION,
			true
		);

		wp_localize_script(
			'qswitch-admin-bar',
			'qswitchPanel',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'qswitch_panel' ),
				'i18n'    => array(
					'pinned'            => __( 'Pinned', 'quickswitch' ),
					'all'               => __( 'All users', 'quickswitch' ),
					'searchPlaceholder' => __( 'Search username or email…', 'quickswitch' ),
					'switchTo'          => __( 'Switch To', 'quickswitch' ),
					'loading'           => __( 'Loading…', 'quickswitch' ),
					'emptyPinned'       => __( 'No pinned users yet. Pin accounts from the Users screen.', 'quickswitch' ),
					'emptySearch'       => __( 'No users found.', 'quickswitch' ),
					'end'               => __( 'End of list', 'quickswitch' ),
				),
			)
		);
	}

	public static function register_nodes( WP_Admin_Bar $admin_bar ): void {
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		$old_user = QSwitch_Switching::get_old_user();

		if ( $old_user instanceof WP_User ) {
			self::register_switched_nodes( $admin_bar, $old_user );
		}

		if ( is_admin() && QSwitch_Switching::can_manage() ) {
			$admin_bar->add_node(
				array(
					'id'    => 'qswitch-menu',
					'title' => __( 'Switch User', 'quickswitch' ),
					'href'  => '#',
					'meta'  => array(
						'class' => 'qswitch-menu-trigger',
					),
				)
			);
		}
	}

	private static function register_switched_nodes( WP_Admin_Bar $admin_bar, WP_User $old_user ): void {
		$current = wp_get_current_user();

		$admin_bar->add_node(
			array(
				'id'    => 'qswitch-switched',
				'title' => sprintf(
					/* translators: %s: current switched-in user display name */
					__( 'Switched to %s', 'quickswitch' ),
					$current->display_name
				),
				'href'  => false,
				'meta'  => array(
					'class' => 'qswitch-switched',
				),
			)
		);

		$switch_back_url = add_query_arg(
			array(
				'redirect_to' => rawurlencode( QSwitch_Switching::current_url() ),
			),
			QSwitch_Switching::switch_back_url( $old_user )
		);

		$admin_bar->add_node(
			array(
				'parent' => 'qswitch-switched',
				'id'     => 'qswitch-switch-back',
				'title'  => QSwitch_Switching::switch_back_message( $old_user ),
				'href'   => $switch_back_url,
			)
		);

		if ( QSwitch_Switching::can_manage() ) {
			$admin_bar->add_node(
				array(
					'parent' => 'qswitch-switched',
					'id'     => 'qswitch-switch-off',
					'title'  => __( 'Switch Off', 'quickswitch' ),
					'href'   => add_query_arg(
						array(
							'redirect_to' => rawurlencode( QSwitch_Switching::current_url() ),
						),
						QSwitch_Switching::switch_off_url()
					),
				)
			);
		}
	}
}
