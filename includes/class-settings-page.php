<?php
/**
 * Users → QuickSwitch settings page.
 *
 * @package QuickSwitch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class QSwitch_Settings_Page {

	public static function boot(): void {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_notices', array( __CLASS__, 'render_notices' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function register_menu(): void {
		add_users_page(
			__( 'QuickSwitch', 'quickswitch' ),
			__( 'QuickSwitch', 'quickswitch' ),
			'edit_users',
			'quickswitch',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function enqueue_assets( string $hook ): void {
		if ( 'users_page_quickswitch' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'qswitch-settings',
			plugins_url( 'assets/settings.css', QSWITCH_FILE ),
			array(),
			QSWITCH_VERSION
		);
	}

	public static function render_notices(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'users_page_quickswitch' !== $screen->id ) {
			return;
		}

		if ( isset( $_GET['qswitch_pinned'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'User pinned.', 'quickswitch' ) . '</p></div>';
		}

		if ( isset( $_GET['qswitch_unpinned'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'User unpinned.', 'quickswitch' ) . '</p></div>';
		}
	}

	public static function render_page(): void {
		if ( ! QSwitch_Switching::can_manage() ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'quickswitch' ) );
		}

		$pinned_users = QSwitch_Pins::get_pinned_users();
		?>
		<div class="wrap qswitch-settings">
			<h1><?php echo esc_html__( 'QuickSwitch', 'quickswitch' ); ?></h1>
			<p class="qswitch-settings__intro">
				<?php echo esc_html__( 'Pinned users appear in the admin bar Switch User panel for one-click switching while you test role-based behavior.', 'quickswitch' ); ?>
			</p>

			<?php if ( empty( $pinned_users ) ) : ?>
				<div class="qswitch-empty">
					<div class="qswitch-empty__icon" aria-hidden="true">
						<span class="dashicons dashicons-admin-users"></span>
					</div>
					<h2 class="qswitch-empty__title"><?php echo esc_html__( 'No pinned users yet', 'quickswitch' ); ?></h2>
					<p class="qswitch-empty__text">
						<?php echo esc_html__( 'Pin your test accounts (Editor, Subscriber, Customer, etc.) from the Users screen. They’ll show up here and at the top of Switch User in the admin bar.', 'quickswitch' ); ?>
					</p>
					<div class="qswitch-empty__actions">
						<a class="button button-primary button-hero" href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>">
							<?php echo esc_html__( 'Pin users', 'quickswitch' ); ?>
						</a>
					</div>
				</div>
			<?php else : ?>
				<div class="qswitch-table-wrap">
					<table class="qswitch-table widefat striped">
						<thead>
							<tr>
								<th scope="col"><?php echo esc_html__( 'User', 'quickswitch' ); ?></th>
								<th scope="col"><?php echo esc_html__( 'Role', 'quickswitch' ); ?></th>
								<th scope="col"><?php echo esc_html__( 'Email', 'quickswitch' ); ?></th>
								<th scope="col"><?php echo esc_html__( 'Actions', 'quickswitch' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $pinned_users as $user ) : ?>
								<?php
								$row       = QSwitch_Users::format_user( $user );
								$switch_url = QSwitch_Switching::switch_to_url( (int) $user->ID );
								?>
								<tr>
									<td>
										<div class="qswitch-user-cell">
											<div class="qswitch-user-cell__avatar">
												<img src="<?php echo esc_url( $row['avatar'] ); ?>" alt="" width="40" height="40" />
											</div>
											<div>
												<span class="qswitch-user-cell__name"><?php echo esc_html( $row['display_name'] ); ?></span>
												<span class="qswitch-user-cell__login">@<?php echo esc_html( $row['user_login'] ); ?></span>
											</div>
										</div>
									</td>
									<td><span class="qswitch-role"><?php echo esc_html( $row['role'] ); ?></span></td>
									<td><?php echo esc_html( $row['user_email'] ); ?></td>
									<td>
										<div class="qswitch-actions">
											<a class="button button-primary" href="<?php echo esc_url( $switch_url ); ?>">
												<?php echo esc_html__( 'Switch To', 'quickswitch' ); ?>
											</a>
											<a class="button-link-delete" href="<?php echo esc_url( QSwitch_Pins::unpin_url( (int) $user->ID ) ); ?>">
												<?php echo esc_html__( 'Remove pin', 'quickswitch' ); ?>
											</a>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<p style="margin-top:16px;">
					<a href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>">
						<?php echo esc_html__( 'Manage pins from Users →', 'quickswitch' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}
}
