<?php
/**
 * Users list screen row actions.
 *
 * @package PinSwitch_User_Switcher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PinSwitch_Users_Table {

	public static function boot(): void {
		add_filter( 'user_row_actions', array( __CLASS__, 'filter_row_actions' ), 20, 2 );
	}

	/**
	 * @param array<string, string> $actions
	 * @return array<string, string>
	 */
	public static function filter_row_actions( array $actions, WP_User $user ): array {
		if ( ! PinSwitch_Switching::can_manage() ) {
			return $actions;
		}

		if ( (int) $user->ID === get_current_user_id() ) {
			return $actions;
		}

		if ( PinSwitch_Switching::can_switch_to( (int) $user->ID ) ) {
			$actions['pinswitch_switch_to'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					add_query_arg(
						array(
							'redirect_to' => admin_url( 'users.php' ),
						),
						PinSwitch_Switching::switch_to_url( (int) $user->ID )
					)
				),
				esc_html__( 'Switch To', 'pinswitch-user-switcher' )
			);
		}

		if ( PinSwitch_Pins::is_pinned( (int) $user->ID ) ) {
			$actions['pinswitch_unpin'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( PinSwitch_Pins::unpin_url( (int) $user->ID ) ),
				esc_html__( 'Unpin', 'pinswitch-user-switcher' )
			);
		} else {
			$actions['pinswitch_pin'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( PinSwitch_Pins::pin_url( (int) $user->ID ) ),
				esc_html__( 'Pin', 'pinswitch-user-switcher' )
			);
		}

		return $actions;
	}
}
