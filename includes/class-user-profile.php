<?php
/**
 * User profile and edit screen switch controls.
 *
 * @package PinSwitch_User_Switcher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PinSwitch_User_Profile {

	public static function boot(): void {
		add_action( 'personal_options', array( __CLASS__, 'render_personal_options' ), 99 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_edit_screen_assets' ) );
	}

	public static function render_personal_options( WP_User $user ): void {
		if ( ! PinSwitch_Switching::can_switch_to( (int) $user->ID ) ) {
			return;
		}

		$url = self::switch_url_for_user( $user );
		?>
		<tr class="pinswitch-profile-option">
			<th scope="row"><?php esc_html_e( 'PinSwitch User Switcher', 'pinswitch-user-switcher' ); ?></th>
			<td>
				<a class="button" href="<?php echo esc_url( $url ); ?>">
					<?php esc_html_e( 'Switch To', 'pinswitch-user-switcher' ); ?>
				</a>
			</td>
		</tr>
		<?php
	}

	public static function enqueue_edit_screen_assets( string $hook_suffix ): void {
		if ( 'user-edit.php' !== $hook_suffix ) {
			return;
		}

		$user_id = absint( $_GET['user_id'] ?? 0 );

		if ( ! $user_id || ! PinSwitch_Switching::can_switch_to( $user_id ) ) {
			return;
		}

		$user = get_userdata( $user_id );

		if ( ! $user instanceof WP_User ) {
			return;
		}

		wp_enqueue_script(
			'pinswitch-user-edit',
			plugins_url( 'assets/user-edit.js', PINSWITCH_FILE ),
			array(),
			PINSWITCH_VERSION,
			true
		);

		wp_localize_script(
			'pinswitch-user-edit',
			'pinswitchUserEdit',
			array(
				'url'   => self::switch_url_for_user( $user ),
				'label' => __( 'Switch To', 'pinswitch-user-switcher' ),
			)
		);
	}

	private static function switch_url_for_user( WP_User $user ): string {
		return add_query_arg(
			array(
				'redirect_to' => PinSwitch_Switching::current_url(),
			),
			PinSwitch_Switching::switch_to_url( (int) $user->ID )
		);
	}
}
