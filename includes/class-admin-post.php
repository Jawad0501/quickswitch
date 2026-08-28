<?php
/**
 * admin-post.php action handlers.
 *
 * @package QuickSwitch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class QSwitch_Admin_Post {

	public static function boot(): void {
		$actions = array(
			'qswitch_pin',
			'qswitch_unpin',
			'qswitch_switch_to',
			'qswitch_switch_back',
			'qswitch_switch_off',
		);

		foreach ( $actions as $action ) {
			add_action( 'admin_post_' . $action, array( __CLASS__, 'handle_' . str_replace( 'qswitch_', '', $action ) ) );
		}
	}

	public static function handle_pin(): void {
		self::require_manage();

		$user_id = absint( $_REQUEST['user_id'] ?? 0 );

		check_admin_referer( 'qswitch_pin_' . $user_id );

		if ( ! get_userdata( $user_id ) ) {
			wp_die( esc_html__( 'User not found.', 'quickswitch' ), 404 );
		}

		QSwitch_Pins::pin( $user_id );

		self::redirect_back(
			array(
				'qswitch_pinned' => '1',
			)
		);
	}

	public static function handle_unpin(): void {
		self::require_manage();

		$user_id = absint( $_REQUEST['user_id'] ?? 0 );

		check_admin_referer( 'qswitch_unpin_' . $user_id );

		QSwitch_Pins::unpin( $user_id );

		self::redirect_back(
			array(
				'qswitch_unpinned' => '1',
			)
		);
	}

	public static function handle_switch_to(): void {
		self::require_manage();

		$user_id = absint( $_REQUEST['user_id'] ?? 0 );

		check_admin_referer( 'qswitch_switch_to_' . $user_id );

		if ( ! QSwitch_Switching::can_switch_to( $user_id ) ) {
			wp_die( esc_html__( 'Could not switch users.', 'quickswitch' ), 403 );
		}

		$user = QSwitch_Switching::switch_to( $user_id, QSwitch_Switching::remember() );

		if ( ! $user ) {
			wp_die( esc_html__( 'Could not switch users.', 'quickswitch' ), 404 );
		}

		$redirect_to = QSwitch_Switching::redirect_after_switch( $user );

		wp_safe_redirect(
			add_query_arg(
				array(
					'qswitch_switched' => '1',
				),
				$redirect_to
			),
			302,
			QSwitch_Switching::APPLICATION
		);
		exit;
	}

	public static function handle_switch_back(): void {
		$old_user = QSwitch_Switching::get_old_user();

		if ( ! ( $old_user instanceof WP_User ) ) {
			wp_die( esc_html__( 'Could not switch users.', 'quickswitch' ), 400 );
		}

		if ( ! QSwitch_Switching::authenticate_old_user( $old_user ) ) {
			wp_die( esc_html__( 'Could not switch users.', 'quickswitch' ), 403 );
		}

		check_admin_referer( 'qswitch_switch_back_' . $old_user->ID );

		if ( ! QSwitch_Switching::switch_to( $old_user->ID, QSwitch_Switching::remember(), false ) ) {
			wp_die( esc_html__( 'Could not switch users.', 'quickswitch' ), 404 );
		}

		$redirect_to = QSwitch_Switching::redirect_after_switch( $old_user );

		wp_safe_redirect(
			add_query_arg(
				array(
					'qswitch_switched'      => '1',
					'qswitch_switched_back' => '1',
				),
				$redirect_to
			),
			302,
			QSwitch_Switching::APPLICATION
		);
		exit;
	}

	public static function handle_switch_off(): void {
		self::require_manage();

		check_admin_referer( 'qswitch_switch_off' );

		if ( ! QSwitch_Switching::switch_off() ) {
			wp_die( esc_html__( 'Could not switch off.', 'quickswitch' ), 403 );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'qswitch_switched_off' => '1',
				),
				home_url()
			),
			302,
			QSwitch_Switching::APPLICATION
		);
		exit;
	}

	private static function require_manage(): void {
		if ( ! QSwitch_Switching::can_manage() ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'quickswitch' ), 403 );
		}
	}

	/**
	 * @param array<string, string> $args
	 */
	private static function redirect_back( array $args = array() ): void {
		$redirect = wp_get_referer();

		if ( ! $redirect ) {
			$redirect = admin_url( 'users.php' );
		}

		wp_safe_redirect( add_query_arg( $args, $redirect ) );
		exit;
	}
}
