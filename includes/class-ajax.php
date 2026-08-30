<?php
/**
 * AJAX endpoints for the Switch User panel.
 *
 * @package PinSwitch_User_Switcher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PinSwitch_Ajax {

	public static function boot(): void {
		add_action( 'wp_ajax_pinswitch_search_users', array( __CLASS__, 'search_users' ) );
	}

	public static function search_users(): void {
		if ( ! PinSwitch_Switching::can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Forbidden.', 'pinswitch-user-switcher' ) ), 403 );
		}

		check_ajax_referer( 'pinswitch_panel', 'nonce' );

		$search      = isset( $_REQUEST['search'] ) ? sanitize_text_field( wp_unslash( (string) $_REQUEST['search'] ) ) : '';
		$page        = isset( $_REQUEST['page'] ) ? absint( $_REQUEST['page'] ) : 1;
		$pinned_only = ! empty( $_REQUEST['pinned'] );

		wp_send_json_success( PinSwitch_Users::query( $search, $page, $pinned_only ) );
	}
}
