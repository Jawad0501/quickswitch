<?php
/**
 * Core user switching via WordPress auth cookies and session tokens.
 *
 * @package QuickSwitch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class QSwitch_Switching {

	public const APPLICATION = 'WordPress/QuickSwitch';

	public static function boot(): void {
		self::define_cookies();
		add_action( 'plugins_loaded', array( __CLASS__, 'define_cookies' ), 1 );
		add_action( 'wp_logout', array( __CLASS__, 'clear_olduser_cookie' ) );
		add_action( 'wp_login', array( __CLASS__, 'clear_olduser_cookie' ) );
		add_action( 'clear_auth_cookie', array( __CLASS__, 'on_clear_auth_cookie' ) );
		add_filter( 'removable_query_args', array( __CLASS__, 'filter_removable_query_args' ) );
		add_action( 'all_admin_notices', array( __CLASS__, 'render_admin_notices' ), 1 );
	}

	public static function define_cookies(): void {
		if ( ! defined( 'QSWITCH_COOKIE' ) ) {
			define( 'QSWITCH_COOKIE', 'wordpress_qswitch_' . COOKIEHASH );
		}

		if ( ! defined( 'QSWITCH_SECURE_COOKIE' ) ) {
			define( 'QSWITCH_SECURE_COOKIE', 'wordpress_qswitch_secure_' . COOKIEHASH );
		}

		if ( ! defined( 'QSWITCH_OLDUSER_COOKIE' ) ) {
			define( 'QSWITCH_OLDUSER_COOKIE', 'wordpress_qswitch_olduser_' . COOKIEHASH );
		}
	}

	public static function on_clear_auth_cookie(): void {
		unset( $_COOKIE[ LOGGED_IN_COOKIE ] );
	}

	public static function can_manage(): bool {
		return current_user_can( 'edit_users' );
	}

	public static function can_switch_to( int $user_id ): bool {
		if ( ! self::can_manage() ) {
			return false;
		}

		if ( $user_id <= 0 || $user_id === get_current_user_id() ) {
			return false;
		}

		return (bool) get_userdata( $user_id );
	}

	public static function remember(): bool {
		/** This filter is documented in wp-includes/pluggable.php */
		$cookie_life = apply_filters( 'auth_cookie_expiration', 172800, get_current_user_id(), false );
		$current     = wp_parse_auth_cookie( '', 'logged_in' );

		if ( ! $current ) {
			return false;
		}

		return ( (int) $current['expiration'] - time() > $cookie_life );
	}

	public static function get_old_user(): false|WP_User {
		$cookie = self::get_olduser_cookie();

		if ( empty( $cookie ) ) {
			return false;
		}

		$old_user_id = wp_validate_auth_cookie( $cookie, 'logged_in' );

		if ( ! $old_user_id ) {
			return false;
		}

		return get_userdata( $old_user_id );
	}

	public static function authenticate_old_user( WP_User $user ): bool {
		$cookie = self::get_auth_cookie();

		if ( empty( $cookie ) ) {
			return false;
		}

		$scheme = self::secure_auth_cookie() ? 'secure_auth' : 'auth';
		$old_user_id = wp_validate_auth_cookie( end( $cookie ), $scheme );

		return ( $old_user_id && (int) $user->ID === (int) $old_user_id );
	}

	public static function switch_to( int $user_id, bool $remember = false, bool $set_old_user = true ): false|WP_User {
		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return false;
		}

		$old_user_id  = is_user_logged_in() ? get_current_user_id() : false;
		$old_token    = wp_get_session_token();
		$auth_cookies = self::get_auth_cookie();
		$auth_cookie  = end( $auth_cookies );
		$cookie_parts = $auth_cookie ? wp_parse_auth_cookie( $auth_cookie ) : false;

		if ( $set_old_user && $old_user_id ) {
			$new_token = '';
			self::set_olduser_cookie( (int) $old_user_id, false, (string) $old_token );
		} else {
			$new_token = $cookie_parts['token'] ?? '';
			self::clear_olduser_cookie( false );
		}

		$session_filter = static function ( array $session ) use ( $old_user_id, $old_token ): array {
			$session['switched_from_id']      = $old_user_id;
			$session['switched_from_session'] = $old_token;

			return $session;
		};

		add_filter( 'attach_session_information', $session_filter, 99 );

		wp_clear_auth_cookie();
		wp_set_auth_cookie( $user_id, $remember, '', $new_token );
		wp_set_current_user( $user_id );

		remove_filter( 'attach_session_information', $session_filter, 99 );

		if ( $old_token && $old_user_id && ! $set_old_user ) {
			WP_Session_Tokens::get_instance( $old_user_id )->destroy( $old_token );
		}

		return $user;
	}

	public static function switch_off(): bool {
		$old_user_id = get_current_user_id();

		if ( ! $old_user_id ) {
			return false;
		}

		$old_token = wp_get_session_token();

		self::set_olduser_cookie( $old_user_id, false, (string) $old_token );
		wp_clear_auth_cookie();
		wp_set_current_user( 0 );

		return true;
	}

	public static function switch_to_url( int $user_id ): string {
		return self::nonce_action_url(
			add_query_arg(
				array(
					'action'  => 'qswitch_switch_to',
					'user_id' => $user_id,
				),
				admin_url( 'admin-post.php' )
			),
			'qswitch_switch_to_' . $user_id
		);
	}

	public static function switch_back_url( WP_User $user ): string {
		return self::nonce_action_url(
			add_query_arg(
				array(
					'action' => 'qswitch_switch_back',
				),
				admin_url( 'admin-post.php' )
			),
			'qswitch_switch_back_' . $user->ID
		);
	}

	public static function switch_off_url(): string {
		return self::nonce_action_url(
			add_query_arg(
				array(
					'action' => 'qswitch_switch_off',
				),
				admin_url( 'admin-post.php' )
			),
			'qswitch_switch_off'
		);
	}

	public static function current_url(): string {
		$scheme = is_ssl() ? 'https' : 'http';

		return $scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}

	public static function redirect_after_switch( ?WP_User $new_user = null ): string {
		$fallback = self::default_redirect_for_user( $new_user );

		if ( empty( $_REQUEST['redirect_to'] ) || ! ( $new_user instanceof WP_User ) ) {
			return $fallback;
		}

		$redirect_to = wp_validate_redirect( wp_unslash( (string) $_REQUEST['redirect_to'] ), '' );

		if ( ! $redirect_to || ! self::user_can_access_redirect( $new_user, $redirect_to ) ) {
			return $fallback;
		}

		return $redirect_to;
	}

	private static function default_redirect_for_user( ?WP_User $new_user ): string {
		if ( $new_user instanceof WP_User && user_can( $new_user, 'read' ) ) {
			return admin_url();
		}

		if ( $new_user instanceof WP_User ) {
			return home_url();
		}

		return admin_url( 'users.php' );
	}

	private static function user_can_access_redirect( WP_User $user, string $url ): bool {
		if ( ! self::is_internal_admin_url( $url ) ) {
			return true;
		}

		if ( ! user_can( $user, 'read' ) ) {
			return false;
		}

		$path  = (string) wp_parse_url( $url, PHP_URL_PATH );
		$query = array();
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );

		if ( self::is_admin_dashboard_url( $path, $query ) ) {
			return true;
		}

		$cap = self::required_cap_for_admin_target( $path, $query );

		return user_can( $user, $cap );
	}

	private static function is_internal_admin_url( string $url ): bool {
		$url           = untrailingslashit( $url );
		$admin_url     = untrailingslashit( admin_url() );
		$network_admin = untrailingslashit( network_admin_url() );

		return str_starts_with( $url, $admin_url ) || str_starts_with( $url, $network_admin );
	}

	/**
	 * @param array<string, mixed> $query
	 */
	private static function is_admin_dashboard_url( string $path, array $query ): bool {
		if ( ! empty( $query['page'] ) ) {
			return false;
		}

		$script = basename( $path );

		return in_array( $script, array( 'admin.php', 'index.php', '' ), true );
	}

	/**
	 * @param array<string, mixed> $query
	 */
	private static function required_cap_for_admin_target( string $path, array $query ): string {
		if ( ! empty( $query['page'] ) ) {
			return 'manage_options';
		}

		$script    = basename( $path );
		$post_type = sanitize_key( (string) ( $query['post_type'] ?? 'post' ) );

		$map = array(
			'index.php'             => 'read',
			'profile.php'           => 'read',
			'users.php'             => 'list_users',
			'user-new.php'          => 'create_users',
			'user-edit.php'         => 'edit_users',
			'options-general.php'   => 'manage_options',
			'options-writing.php'   => 'manage_options',
			'options-reading.php'   => 'manage_options',
			'options-discussion.php' => 'manage_options',
			'options-media.php'     => 'manage_options',
			'options-permalink.php' => 'manage_options',
			'options-privacy.php'   => 'manage_options',
			'plugins.php'           => 'activate_plugins',
			'plugin-install.php'    => 'install_plugins',
			'plugin-editor.php'     => 'edit_plugins',
			'themes.php'            => 'switch_themes',
			'theme-install.php'     => 'install_themes',
			'theme-editor.php'      => 'edit_themes',
			'tools.php'             => 'edit_posts',
			'import.php'            => 'import',
			'export.php'            => 'export',
			'update-core.php'       => 'update_core',
			'upload.php'            => 'upload_files',
			'edit-comments.php'     => 'moderate_comments',
			'nav-menus.php'         => 'edit_theme_options',
			'widgets.php'           => 'edit_theme_options',
			'customize.php'         => 'customize',
			'site-editor.php'       => 'edit_theme_options',
			'edit.php'              => 'page' === $post_type ? 'edit_pages' : 'edit_posts',
			'post-new.php'          => 'page' === $post_type ? 'edit_pages' : 'edit_posts',
			'post.php'              => 'page' === $post_type ? 'edit_pages' : 'edit_posts',
		);

		return $map[ $script ] ?? 'manage_options';
	}

	public static function switched_to_message( WP_User $user ): string {
		return sprintf(
			/* translators: %s: user display name */
			__( 'Switched to %s.', 'quickswitch' ),
			$user->display_name
		);
	}

	public static function switch_back_message( WP_User $user ): string {
		return sprintf(
			/* translators: %s: user display name */
			__( 'Switch back to %s', 'quickswitch' ),
			$user->display_name
		);
	}

	public static function switched_back_message( WP_User $user ): string {
		return sprintf(
			/* translators: %s: user display name */
			__( 'Switched back to %s.', 'quickswitch' ),
			$user->display_name
		);
	}

	public static function filter_removable_query_args( array $args ): array {
		return array_merge(
			$args,
			array(
				'qswitch_switched',
				'qswitch_switched_back',
				'qswitch_switched_off',
				'qswitch_pinned',
				'qswitch_unpinned',
			)
		);
	}

	public static function render_admin_notices(): void {
		$user     = wp_get_current_user();
		$old_user = self::get_old_user();

		if ( $old_user instanceof WP_User ) {
			$switch_back_url = add_query_arg(
				array(
					'redirect_to' => rawurlencode( self::current_url() ),
				),
				self::switch_back_url( $old_user )
			);

			$message = isset( $_GET['qswitch_switched'] ) ? self::switched_to_message( $user ) . ' ' : '';
			$message .= sprintf(
				'<a href="%s">%s</a>',
				esc_url( $switch_back_url ),
				esc_html( self::switch_back_message( $old_user ) )
			);

			printf(
				'<div class="updated notice notice-success is-dismissible"><p><span class="dashicons dashicons-admin-users" style="color:#56c234" aria-hidden="true"></span> %s</p></div>',
				wp_kses(
					$message,
					array(
						'a' => array(
							'href' => array(),
						),
					)
				)
			);

			return;
		}

		if ( isset( $_GET['qswitch_switched'] ) ) {
			$text = isset( $_GET['qswitch_switched_back'] )
				? self::switched_back_message( $user )
				: self::switched_to_message( $user );

			printf(
				'<div class="updated notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( $text )
			);
		}
	}

	private static function secure_auth_cookie(): bool {
		return is_ssl() && ( 'https' === wp_parse_url( wp_login_url(), PHP_URL_SCHEME ) );
	}

	private static function secure_olduser_cookie(): bool {
		return is_ssl() && ( 'https' === wp_parse_url( home_url(), PHP_URL_SCHEME ) );
	}

	private static function nonce_action_url( string $url, string $action ): string {
		return wp_specialchars_decode( wp_nonce_url( $url, $action ) );
	}

	private static function set_olduser_cookie( int $old_user_id, bool $pop = false, string $token = '' ): void {
		$secure_auth_cookie    = self::secure_auth_cookie();
		$secure_olduser_cookie = self::secure_olduser_cookie();
		$expiration            = time() + 172800;
		$auth_cookie           = self::get_auth_cookie();
		$olduser_cookie        = wp_generate_auth_cookie( $old_user_id, $expiration, 'logged_in', $token );

		if ( $secure_auth_cookie ) {
			$auth_cookie_name = QSWITCH_SECURE_COOKIE;
			$scheme           = 'secure_auth';
		} else {
			$auth_cookie_name = QSWITCH_COOKIE;
			$scheme           = 'auth';
		}

		if ( $pop ) {
			array_pop( $auth_cookie );
		} else {
			$auth_cookie[] = wp_generate_auth_cookie( $old_user_id, $expiration, $scheme, $token );
		}

		$encoded = wp_json_encode( $auth_cookie );

		if ( false === $encoded ) {
			return;
		}

		if ( ! apply_filters( 'quickswitch_send_auth_cookies', true ) ) {
			return;
		}

		setcookie( $auth_cookie_name, $encoded, $expiration, SITECOOKIEPATH, COOKIE_DOMAIN, $secure_auth_cookie, true );
		setcookie( QSWITCH_OLDUSER_COOKIE, $olduser_cookie, $expiration, COOKIEPATH, COOKIE_DOMAIN, $secure_olduser_cookie, true );
	}

	public static function clear_olduser_cookie( bool $clear_all = true ): void {
		$auth_cookie = self::get_auth_cookie();

		if ( ! empty( $auth_cookie ) ) {
			array_pop( $auth_cookie );
		}

		if ( ! $clear_all && ! empty( $auth_cookie ) ) {
			$scheme = self::secure_auth_cookie() ? 'secure_auth' : 'auth';
			$old_cookie = end( $auth_cookie );
			$old_user_id = wp_validate_auth_cookie( $old_cookie, $scheme );

			if ( $old_user_id ) {
				$parts = wp_parse_auth_cookie( $old_cookie, $scheme );

				if ( false !== $parts ) {
					self::set_olduser_cookie( (int) $old_user_id, true, (string) $parts['token'] );
				}
			}

			return;
		}

		if ( ! apply_filters( 'quickswitch_send_auth_cookies', true ) ) {
			return;
		}

		$expire = time() - YEAR_IN_SECONDS;

		setcookie( QSWITCH_COOKIE, ' ', $expire, SITECOOKIEPATH, COOKIE_DOMAIN );
		setcookie( QSWITCH_SECURE_COOKIE, ' ', $expire, SITECOOKIEPATH, COOKIE_DOMAIN );
		setcookie( QSWITCH_OLDUSER_COOKIE, ' ', $expire, COOKIEPATH, COOKIE_DOMAIN );
	}

	private static function get_olduser_cookie(): string|false {
		if ( ! isset( $_COOKIE[ QSWITCH_OLDUSER_COOKIE ] ) ) {
			return false;
		}

		return wp_unslash( (string) $_COOKIE[ QSWITCH_OLDUSER_COOKIE ] );
	}

	/**
	 * @return list<string>
	 */
	private static function get_auth_cookie(): array {
		$auth_cookie_name = self::secure_auth_cookie() ? QSWITCH_SECURE_COOKIE : QSWITCH_COOKIE;
		$cookie           = array();

		if ( isset( $_COOKIE[ $auth_cookie_name ] ) && is_string( $_COOKIE[ $auth_cookie_name ] ) ) {
			$decoded = json_decode( wp_unslash( $_COOKIE[ $auth_cookie_name ] ) );

			if ( is_array( $decoded ) ) {
				$cookie = $decoded;
			}
		}

		return array_values( array_filter( $cookie, 'is_string' ) );
	}
}
