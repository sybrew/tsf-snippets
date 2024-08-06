<?php
/**
 * Plugin Name: The SEO Framework - Set admin display level (Headless Mode)
 * Plugin URI: https://theseoframework.com/
 * Description: Allows you to set TSF's admin display capability using Headless Mode.
 * Version: 1.0.0
 * Author: Sybre Waaijer
 * Author URI: https://theseoframework.com/
 * License: GPLv3
 * Requires at least: 5.9
 * Requires PHP: 7.4.0
 * Requires Plugins: autodescription
 *
 * @package My_The_SEO_Framework\AdminDisplayLevel
 */

namespace My_The_SEO_Framework\AdminDisplayLevel;

\defined( 'ABSPATH' ) or die;

/**
 * The display level option name.
 *
 * @since 1.0.0
 */
const OPTION = 'my-tsf-display-level-option';

/**
 * The options edit capability.
 *
 * @since 1.0.0
 */
const OPTION_CAPABILITY = 'manage_options';

/**
 * The admin page hook.
 *
 * @since 1.0.0
 */
const PAGE_HOOK = 'my-tsf-display-level-selector';

/**
 * The save action name.
 *
 * @since 1.0.0
 */
const SAVE_ACTION = 'my_tsf_display_level_settings';

/**
 * The saved action response name.
 *
 * @since 1.0.0
 */
const SAVED_RESPONSE = 'my_tsf_display_level_updated';

/**
 * The save nonce name and action.
 *
 * @since 1.0.0
 */
const SAVE_NONCE = [
	'name'   => '_my_tsf_display_level_nonce',
	'action' => '_my_tsf_display_level_nonce_save_settings',
];

\add_action( 'plugins_loaded', __NAMESPACE__ . '\set_admin_headless', 0 );
\add_action( 'admin_menu', __NAMESPACE__ . '\add_menu_link', 9001 );
\add_action( 'admin_post_' . SAVE_ACTION, __NAMESPACE__ . '\process_settings_submission' );

/**
 * Sets headless in admin mode.
 *
 * @hook plugins_loaded 0
 * @since 1.0.0
 */
function set_admin_headless() {

	if ( ! \is_admin() ) return;

	$option = \get_option( OPTION );

	// If the option isn't set, or the user has the capability, do not define headless mode.
	if ( empty( $option ) || \current_user_can( $option ) ) return;

	\defined( 'THE_SEO_FRAMEWORK_HEADLESS' )
		or \define( 'THE_SEO_FRAMEWORK_HEADLESS', true );
}

/**
 * Adds the menu link.
 *
 * @hook admin_menu 9001
 * @since 1.0.0
 */
function add_menu_link() {
	\add_submenu_page(
		\tsf()->admin()->menu()->get_top_menu_args()['menu_slug'],
		'Display Level',
		'Display Level',
		OPTION_CAPABILITY,
		PAGE_HOOK,
		__NAMESPACE__ . '\display_admin_page',
	);
}

/**
 * Processes the settings submission.
 *
 * @hook admin_post_my_tsf_display_level_settings 10
 * @since 1.0.0
 */
function process_settings_submission() {

	\check_admin_referer( SAVE_NONCE['action'], SAVE_NONCE['name'] );

	if ( ! \current_user_can( OPTION_CAPABILITY ) )
		\wp_die(
			\esc_html__( 'Sorry, you are not allowed to manage options for this site.', 'default' ),
			403,
		);

	// Filter all non-true values in POST, get all keys, and then comma-separate them.
	$new_setting = $_POST[ OPTION ] ?? '';

	$result = \get_option( OPTION, $new_setting ) !== $new_setting
		? (int) \update_option( OPTION, $new_setting )
		: 2;

	\wp_safe_redirect( \add_query_arg( SAVED_RESPONSE, $result, \wp_get_referer() ) );
	exit;
}

/**
 * Outputs the administrative page.
 *
 * @hook seo_page_my-tsf-display-level-selector 10
 * @since 1.0.0
 */
function display_admin_page() {
	?>
	<div class=wrap>
		<h1>Set admin display capability via Headless Mode</h1>
		<hr class=wp-header-end>
		<?php
		switch ( (int) ( $_GET[ SAVED_RESPONSE ] ?? -1 ) ) {
			case 0:
				?>
				<div id=message class="notice notice-error is-dismissible inline"><p>
					Settings failed to save.
				</p></div>
				<?php
				break;
			case 1:
				?>
				<div id=message class="notice notice-success is-dismissible inline"><p>
					Settings saved.
				</p></div>
				<?php
				break;
			case 2:
				?>
				<div id=message class="notice notice-info is-dismissible inline"><p>
					No settings were changed.
				</p></div>
				<?php
		}
		?>
		<form method=post action="<?= \esc_url( \admin_url( 'admin-post.php' ) ) ?>">
			<?php \wp_nonce_field( SAVE_NONCE['action'], SAVE_NONCE['name'] ); ?>
			<input type=hidden name=action value="<?= \esc_attr( SAVE_ACTION ) ?>">
			<table class=form-table role=presentation>
				<tr>
					<th scope=row>
						<label for="<?= \esc_attr( OPTION ) ?>">Select Role</label>
					</th>
					<td>
						<select name="<?= \esc_attr( OPTION ) ?>" id="<?= \esc_attr( OPTION ) ?>">
							<?php
							$current_setting = \get_option( OPTION, '' );
							// Show only the capabilities the current user has, so they won't lock themselves out.
							foreach ( \wp_get_current_user()->get_role_caps() as $role => $has ) {
								if ( ! $has ) continue;
								?>
								<option value="<?= \esc_attr( $role ) ?>"<?php \selected( $role, $current_setting ); ?>>
									<?= \esc_html( $role ) ?>
								</option>
								<?php
							}
							?>
						</select>
						<p class=description>Choose a role from the dropdown to set the admin display level. Only users with this capability can interact with The SEO Framework in the admin area.</p>
					</td>
				</tr>
			</table>
			<p class=submit>
				<input type=submit name="<?= \esc_attr( SAVE_ACTION ) ?>" value="Save" class="button button-primary">
			</p>
		</form>
	</div>
	<?php
}
