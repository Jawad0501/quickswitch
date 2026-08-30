<?php
/**
 * admin-post.php action handlers.
 *
 * @package PinSwitch_User_Switcher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PinSwitch_Admin_Post {

	public static function boot(): void {
		$actions = array(
			'pinswitch_pin',
			'pinswitch_unpin',
			'pinswitch_switch_to',
			'pinswitch_switch_back',
			'pinswitch_switch_off',
		);

		foreach ( $actions as $action ) {
			add_action( 'admin_post_' . $action, array( __CLASS__, 'handle_' . str_replace( 'pinswitch_', '', $action ) ) );
		}

		add_action( 'admin_post_nopriv_pinswitch_switch_back', array( __CLASS__, 'handle_switch_back' ) );
	}

	public static function handle_pin(): void {
		self::require_manage();

		$user_id = absint( $_REQUEST['user_id'] ?? 0 );

		check_admin_referer( 'pinswitch_pin_' . $user_id );

		if ( ! get_userdata( $user_id ) ) {
			wp_die( esc_html__( 'User not found.', 'pinswitch-user-switcher' ), 404 );
		}

		PinSwitch_Pins::pin( $user_id );

		self::redirect_back(
			array(
				'pinswitch_pinned' => '1',
			)
		);
	}

	public static function handle_unpin(): void {
		self::require_manage();

		$user_id = absint( $_REQUEST['user_id'] ?? 0 );

		check_admin_referer( 'pinswitch_unpin_' . $user_id );

		PinSwitch_Pins::unpin( $user_id );

		self::redirect_back(
			array(
				'pinswitch_unpinned' => '1',
			)
		);
	}

	public static function handle_switch_to(): void {
		self::require_manage();

		$user_id = absint( $_REQUEST['user_id'] ?? 0 );

		check_admin_referer( 'pinswitch_switch_to_' . $user_id );

		if ( ! PinSwitch_Switching::can_switch_to( $user_id ) ) {
			wp_die( esc_html__( 'Could not switch users.', 'pinswitch-user-switcher' ), 403 );
		}

		$user = PinSwitch_Switching::switch_to( $user_id, PinSwitch_Switching::remember() );

		if ( ! $user ) {
			wp_die( esc_html__( 'Could not switch users.', 'pinswitch-user-switcher' ), 404 );
		}

		$redirect_to = PinSwitch_Switching::redirect_after_switch( $user );

		wp_safe_redirect(
			add_query_arg(
				array(
					'pinswitch_switched' => '1',
				),
				$redirect_to
			),
			302,
			PinSwitch_Switching::APPLICATION
		);
		exit;
	}

	public static function handle_switch_back(): void {
		$old_user = PinSwitch_Switching::get_old_user();

		if ( ! ( $old_user instanceof WP_User ) ) {
			wp_die( esc_html__( 'Could not switch users.', 'pinswitch-user-switcher' ), 400 );
		}

		if ( ! PinSwitch_Switching::authenticate_old_user( $old_user ) ) {
			wp_die( esc_html__( 'Could not switch users.', 'pinswitch-user-switcher' ), 403 );
		}

		check_admin_referer( 'pinswitch_switch_back_' . $old_user->ID );

		if ( ! PinSwitch_Switching::switch_to( $old_user->ID, PinSwitch_Switching::remember(), false ) ) {
			wp_die( esc_html__( 'Could not switch users.', 'pinswitch-user-switcher' ), 404 );
		}

		$redirect_to = PinSwitch_Switching::redirect_after_switch( $old_user );

		wp_safe_redirect(
			add_query_arg(
				array(
					'pinswitch_switched'      => '1',
					'pinswitch_switched_back' => '1',
				),
				$redirect_to
			),
			302,
			PinSwitch_Switching::APPLICATION
		);
		exit;
	}

	public static function handle_switch_off(): void {
		if ( ! PinSwitch_Switching::can_switch_off() ) {
			wp_die( esc_html__( 'Could not switch off.', 'pinswitch-user-switcher' ), 403 );
		}

		check_admin_referer( 'pinswitch_switch_off' );

		if ( ! PinSwitch_Switching::switch_off() ) {
			wp_die( esc_html__( 'Could not switch off.', 'pinswitch-user-switcher' ), 403 );
		}

		$redirect_to = home_url();

		if ( ! empty( $_REQUEST['redirect_to'] ) ) {
			$redirect_to = wp_validate_redirect( wp_unslash( (string) $_REQUEST['redirect_to'] ), $redirect_to );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'pinswitch_switched_off' => '1',
				),
				$redirect_to
			),
			302,
			PinSwitch_Switching::APPLICATION
		);
		exit;
	}

	private static function require_manage(): void {
		if ( ! PinSwitch_Switching::can_manage() ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'pinswitch-user-switcher' ), 403 );
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
