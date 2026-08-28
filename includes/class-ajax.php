<?php
/**
 * AJAX endpoints for the Switch User panel.
 *
 * @package QuickSwitch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class QSwitch_Ajax {

	public static function boot(): void {
		add_action( 'wp_ajax_qswitch_search_users', array( __CLASS__, 'search_users' ) );
	}

	public static function search_users(): void {
		if ( ! QSwitch_Switching::can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Forbidden.', 'quickswitch' ) ), 403 );
		}

		check_ajax_referer( 'qswitch_panel', 'nonce' );

		$search      = isset( $_REQUEST['search'] ) ? sanitize_text_field( wp_unslash( (string) $_REQUEST['search'] ) ) : '';
		$page        = isset( $_REQUEST['page'] ) ? absint( $_REQUEST['page'] ) : 1;
		$pinned_only = ! empty( $_REQUEST['pinned'] );

		wp_send_json_success( QSwitch_Users::query( $search, $page, $pinned_only ) );
	}
}
