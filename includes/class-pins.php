<?php
/**
 * Per-admin pinned user storage.
 *
 * @package QuickSwitch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class QSwitch_Pins {

	public static function boot(): void {
		add_action( 'delete_user', array( __CLASS__, 'remove_user_from_all_pins' ) );
		add_action( 'admin_notices', array( __CLASS__, 'render_admin_notices' ) );
	}

	/**
	 * @return list<int>
	 */
	public static function get_pinned_ids( ?int $admin_id = null ): array {
		$admin_id = $admin_id ?? get_current_user_id();

		if ( $admin_id <= 0 ) {
			return array();
		}

		$stored = get_user_meta( $admin_id, QSWITCH_META_KEY, true );

		if ( ! is_array( $stored ) ) {
			return array();
		}

		$ids = array_values(
			array_unique(
				array_filter(
					array_map( 'absint', $stored )
				)
			)
		);

		return array_values(
			array_filter(
				$ids,
				static fn( int $id ): bool => (bool) get_userdata( $id )
			)
		);
	}

	/**
	 * @return list<WP_User>
	 */
	public static function get_pinned_users( ?int $admin_id = null ): array {
		$users = array();

		foreach ( self::get_pinned_ids( $admin_id ) as $user_id ) {
			$user = get_userdata( $user_id );

			if ( $user instanceof WP_User ) {
				$users[] = $user;
			}
		}

		return $users;
	}

	public static function is_pinned( int $user_id, ?int $admin_id = null ): bool {
		return in_array( $user_id, self::get_pinned_ids( $admin_id ), true );
	}

	public static function pin( int $user_id, ?int $admin_id = null ): bool {
		$admin_id = $admin_id ?? get_current_user_id();

		if ( $admin_id <= 0 || $user_id <= 0 || $admin_id === $user_id ) {
			return false;
		}

		if ( ! get_userdata( $user_id ) ) {
			return false;
		}

		$ids = self::get_pinned_ids( $admin_id );

		if ( in_array( $user_id, $ids, true ) ) {
			return true;
		}

		$ids[] = $user_id;

		return update_user_meta( $admin_id, QSWITCH_META_KEY, $ids );
	}

	public static function unpin( int $user_id, ?int $admin_id = null ): bool {
		$admin_id = $admin_id ?? get_current_user_id();

		if ( $admin_id <= 0 || $user_id <= 0 ) {
			return false;
		}

		$ids = array_values(
			array_filter(
				self::get_pinned_ids( $admin_id ),
				static fn( int $id ): bool => $id !== $user_id
			)
		);

		return update_user_meta( $admin_id, QSWITCH_META_KEY, $ids );
	}

	public static function pin_url( int $user_id ): string {
		return wp_specialchars_decode(
			wp_nonce_url(
				add_query_arg(
					array(
						'action'  => 'qswitch_pin',
						'user_id' => $user_id,
					),
					admin_url( 'admin-post.php' )
				),
				'qswitch_pin_' . $user_id
			)
		);
	}

	public static function unpin_url( int $user_id ): string {
		return wp_specialchars_decode(
			wp_nonce_url(
				add_query_arg(
					array(
						'action'  => 'qswitch_unpin',
						'user_id' => $user_id,
					),
					admin_url( 'admin-post.php' )
				),
				'qswitch_unpin_' . $user_id
			)
		);
	}

	public static function render_admin_notices(): void {
		if ( ! QSwitch_Switching::can_manage() ) {
			return;
		}

		if ( isset( $_GET['qswitch_pinned'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'User pinned.', 'quickswitch' ) . '</p></div>';
		}

		if ( isset( $_GET['qswitch_unpinned'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'User unpinned.', 'quickswitch' ) . '</p></div>';
		}
	}

	public static function remove_user_from_all_pins( int $user_id ): void {
		global $wpdb;

		$admin_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s",
				QSWITCH_META_KEY
			)
		);

		foreach ( $admin_ids as $admin_id ) {
			self::unpin( $user_id, (int) $admin_id );
		}
	}
}
