<?php
/**
 * Plugin Name: The SEO Framework - Focus Divi Compatibility
 * Plugin URI: https://theseoframework.com/
 * Description: Adds Divi 4 and 5 Builder compatibility for the Focus extension by rendering Divi content server-side so Focus can analyze the actual HTML output.
 * Version: 1.0.0
 * Author: Sybre Waaijer
 * Author URI: https://theseoframework.com/
 * License: GPLv3
 * Requires at least: 6.0
 * Requires PHP: 7.4.0
 * Requires Plugins: autodescription
 *
 * @package My_The_SEO_Framework\Compatibility
 */

defined( 'ABSPATH' ) or die;

if ( ! is_admin() )
	return;

add_action(
	'admin_init',
	function () {

		// Bail if Divi theme or Divi Builder plugin is not active.
		if ( ! function_exists( 'et_setup_theme' ) && ! defined( 'ET_BUILDER_VERSION' ) )
			return;

		add_action( 'wp_ajax_tsf_divi_focus_render', 'tsf_divi_focus_ajax_render' );

		add_action(
			'admin_enqueue_scripts',
			'tsf_divi_focus_maybe_init',
		);
	},
);

/**
 * Handles the AJAX request to render Divi content to HTML.
 *
 * For Divi 4: loads the Builder modules if not yet loaded, then runs
 * do_shortcode() on the submitted content.
 * For Divi 5: runs do_blocks() to render the registered Gutenberg blocks.
 *
 * @since 1.0.0
 */
function tsf_divi_focus_ajax_render() {

	check_ajax_referer( 'tsf_divi_focus_render' );

	$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;

	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) )
		wp_send_json_error();

	$content = isset( $_POST['content'] ) ? wp_unslash( $_POST['content'] ) : '';

	if ( ! $content )
		wp_send_json_success( '' );

	$has_shortcodes = false !== strpos( $content, '[et_pb_' );
	$has_blocks     = false !== strpos( $content, '<!-- wp:divi/' );

	if ( ! $has_shortcodes && ! $has_blocks )
		wp_send_json_success( $content );

	// Divi 4: render shortcodes via do_shortcode().
	if ( $has_shortcodes ) {
		if ( ! did_action( 'et_builder_ready' ) ) {
			if ( function_exists( 'et_builder_init_global_settings' ) )
				et_builder_init_global_settings();
			if ( function_exists( 'et_builder_add_main_elements' ) )
				et_builder_add_main_elements();
		}

		wp_send_json_success( do_shortcode( $content ) );
	}

	// Divi 5: render registered blocks via do_blocks().
	wp_send_json_success( do_blocks( $content ) );
}

/**
 * Initializes Focus-Divi compatibility on post edit screens
 * where the Divi Builder is active.
 *
 * @since 1.0.0
 *
 * @param string $hook The current admin page hook.
 */
function tsf_divi_focus_maybe_init( $hook ) {

	if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) )
		return;

	global $post;

	if ( ! $post )
		return;

	// Activate for Divi 4 (builder meta) or Divi 5 (registered blocks).
	$uses_divi_4 = 'on' === get_post_meta( $post->ID, '_et_pb_use_builder', true );
	$has_divi_5  = WP_Block_Type_Registry::get_instance()->is_registered( 'divi/text' );

	if ( ! $uses_divi_4 && ! $has_divi_5 )
		return;

	add_action(
		'admin_print_footer_scripts',
		'tsf_divi_focus_footer',
		99,
	);
}

/**
 * Outputs inline JS that intercepts Focus's block editor content store,
 * renders Divi content server-side, and feeds the resulting HTML back
 * to Focus for analysis.
 *
 * Uses Focus's blockEditorStore API to synchronously fill the store with
 * the last known rendered HTML, then asynchronously fetches a fresh render
 * from the server. Divi 4 shortcodes are rendered via do_shortcode();
 * Divi 5 blocks via do_blocks().
 *
 * @since 1.0.0
 */
function tsf_divi_focus_footer() {

	global $post;

	if ( ! $post )
		return;

	$nonce   = wp_create_nonce( 'tsf_divi_focus_render' );
	$post_id = $post->ID;
	?>
	<script>
	( () => {
		'use strict';

		const nonce  = <?= json_encode( $nonce ) ?>;
		const postId = <?= (int) $post_id ?>;

		let contentStore;
		let lastSentRaw  = '';
		let lastRendered = '';
		let pending      = false;
		let pendingRaw   = '';

		/**
		 * Sends raw Divi content to the server for rendering. On success,
		 * fills the Focus content store with rendered HTML and triggers
		 * re-analysis.
		 *
		 * @param {string} raw Raw post content containing Divi markers.
		 */
		function sendRender( raw ) {

			if ( raw === lastSentRaw )
				return;

			lastSentRaw = raw;

			tsfem_e_focus_inpost.setAllRatersOf( 'pageContent', 'loading' );

			if ( pending ) {
				pendingRaw = raw;
				return;
			}

			pending = true;

			wp.ajax.post( 'tsf_divi_focus_render', {
				_wpnonce: nonce,
				post_id:  postId,
				content:  raw,
			} )
				.done( data => {
					lastRendered = data;
					contentStore.fill( lastRendered );
					contentStore.triggerAnalysis();
				} )
				.fail( () => {
					lastSentRaw = '';
				} )
				.always( () => {
					pending = false;

					if ( pendingRaw ) {
						const next = pendingRaw;
						pendingRaw = '';
						sendRender( next );
					}
				} );
		}

		document.addEventListener(
			'tsfem-focus-gutenberg-content-store-setup',
			() => {
				contentStore = tsfem_e_focus_inpost.blockEditorStore( 'content' );

				document.addEventListener( 'tsfem-focus-gutenberg-content-store-fill', event => {

					const raw = event.detail.data;

					if ( ! raw
						|| ( raw.indexOf( '[et_pb_' ) === -1
							&& raw.indexOf( '<!-- wp:divi/' ) === -1 )
					) return;

					// Synchronously replace raw block markup with last rendered HTML.
					contentStore.fill( lastRendered );

					sendRender( raw );
				} );
			},
		);
	} )();
	</script>
	<?php
}
