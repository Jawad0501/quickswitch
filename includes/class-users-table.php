<?php
/**
 * Users list screen row actions.
 *
 * @package QuickSwitch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class QSwitch_Users_Table {

	public static function boot(): void {
		add_filter( 'user_row_actions', array( __CLASS__, 'filter_row_actions' ), 20, 2 );
	}

	/**
	 * @param array<string, string> $actions
	 * @return array<string, string>
	 */
	public static function filter_row_actions( array $actions, WP_User $user ): array {
		if ( ! QSwitch_Switching::can_manage() ) {
			return $actions;
		}

		if ( (int) $user->ID === get_current_user_id() ) {
			return $actions;
		}

		if ( QSwitch_Switching::can_switch_to( (int) $user->ID ) ) {
			$actions['qswitch_switch_to'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					add_query_arg(
						array(
							'redirect_to' => admin_url( 'users.php' ),
						),
						QSwitch_Switching::switch_to_url( (int) $user->ID )
					)
				),
				esc_html__( 'Switch To', 'quickswitch' )
			);
		}

		if ( QSwitch_Pins::is_pinned( (int) $user->ID ) ) {
			$actions['qswitch_unpin'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( QSwitch_Pins::unpin_url( (int) $user->ID ) ),
				esc_html__( 'Unpin', 'quickswitch' )
			);
		} else {
			$actions['qswitch_pin'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( QSwitch_Pins::pin_url( (int) $user->ID ) ),
				esc_html__( 'Pin', 'quickswitch' )
			);
		}

		return $actions;
	}
}
