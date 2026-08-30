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
		add_action( 'admin_footer-user-edit.php', array( __CLASS__, 'render_edit_header_button' ) );
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

	public static function render_edit_header_button(): void {
		$user_id = absint( $_GET['user_id'] ?? 0 );

		if ( ! $user_id || ! PinSwitch_Switching::can_switch_to( $user_id ) ) {
			return;
		}

		$user = get_userdata( $user_id );

		if ( ! $user instanceof WP_User ) {
			return;
		}

		$url   = self::switch_url_for_user( $user );
		$label = __( 'Switch To', 'pinswitch-user-switcher' );
		?>
		<script>
		(function () {
			if (document.getElementById('pinswitch-switch-to-user')) {
				return;
			}

			var wrap = document.getElementById('profile-page');
			if (!wrap) {
				return;
			}

			var addUser = wrap.querySelector('a.page-title-action[href*="user-new.php"]');
			var anchor = addUser || wrap.querySelector('h1.wp-heading-inline');

			if (!anchor) {
				return;
			}

			var link = document.createElement('a');
			link.id = 'pinswitch-switch-to-user';
			link.className = 'page-title-action';
			link.href = <?php echo wp_json_encode( $url ); ?>;
			link.textContent = <?php echo wp_json_encode( $label ); ?>;
			anchor.insertAdjacentElement('afterend', link);
		})();
		</script>
		<?php
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
