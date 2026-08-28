<?php
/**
 * User listing helpers for QuickSwitch UI.
 *
 * @package QuickSwitch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class QSwitch_Users {

	public const PAGE_SIZE = 20;

	/**
	 * @return array{id:int,display_name:string,user_login:string,user_email:string,role:string,avatar:string,pinned:bool,switch_url:string}
	 */
	public static function format_user( WP_User $user ): array {
		$roles      = $user->roles;
		$role_slug  = $roles[0] ?? '';
		$role_names = wp_roles()->get_names();
		$role_label = $role_slug && isset( $role_names[ $role_slug ] )
			? translate_user_role( $role_names[ $role_slug ] )
			: __( 'No role', 'quickswitch' );

		return array(
			'id'           => (int) $user->ID,
			'display_name' => $user->display_name,
			'user_login'   => $user->user_login,
			'user_email'   => $user->user_email,
			'role'         => $role_label,
			'avatar'       => get_avatar_url( $user->ID, array( 'size' => 64 ) ),
			'pinned'       => QSwitch_Pins::is_pinned( (int) $user->ID ),
			'switch_url'   => QSwitch_Switching::switch_to_url( (int) $user->ID ),
		);
	}

	/**
	 * @return array{users:list<array>,has_more:bool,page:int}
	 */
	public static function query( string $search = '', int $page = 1, bool $pinned_only = false ): array {
		$page   = max( 1, $page );
		$offset = ( $page - 1 ) * self::PAGE_SIZE;
		$exclude = array( get_current_user_id() );

		if ( $pinned_only ) {
			$pinned_ids = array_values(
				array_diff( QSwitch_Pins::get_pinned_ids(), $exclude )
			);

			if ( empty( $pinned_ids ) ) {
				return array(
					'users'    => array(),
					'has_more' => false,
					'page'     => $page,
				);
			}

			$users = get_users(
				array(
					'include' => $pinned_ids,
					'orderby' => 'display_name',
					'order'   => 'ASC',
				)
			);

			if ( '' !== $search ) {
				$needle = strtolower( $search );
				$users  = array_values(
					array_filter(
						$users,
						static function ( WP_User $user ) use ( $needle ): bool {
							return str_contains( strtolower( $user->user_login ), $needle )
								|| str_contains( strtolower( $user->user_email ), $needle )
								|| str_contains( strtolower( $user->display_name ), $needle );
						}
					)
				);
			}

			$slice = array_slice( $users, $offset, self::PAGE_SIZE + 1 );
			$more  = count( $slice ) > self::PAGE_SIZE;
			$slice = array_slice( $slice, 0, self::PAGE_SIZE );

			return array(
				'users'    => array_map( array( __CLASS__, 'format_user' ), $slice ),
				'has_more' => $more,
				'page'     => $page,
			);
		}

		$args = array(
			'number'  => self::PAGE_SIZE + 1,
			'offset'  => $offset,
			'exclude' => $exclude,
			'orderby' => 'display_name',
			'order'   => 'ASC',
		);

		if ( '' !== $search ) {
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
		}

		$users = get_users( $args );
		$more  = count( $users ) > self::PAGE_SIZE;
		$users = array_slice( $users, 0, self::PAGE_SIZE );

		$formatted = array();
		foreach ( $users as $user ) {
			if ( ! QSwitch_Switching::can_switch_to( (int) $user->ID ) ) {
				continue;
			}
			$formatted[] = self::format_user( $user );
		}

		return array(
			'users'    => $formatted,
			'has_more' => $more,
			'page'     => $page,
		);
	}
}
