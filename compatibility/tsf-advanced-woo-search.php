<?php
/**
 * Plugin Name: The SEO Framework - Compatibility with Advanced Woo Search
 * Plugin URI: https://theseoframework.com/
 * Description: This plugin disables TSF's SEO support for Advanced Woo Search pages.
 * Version: 1.0.0
 * Author: Sybre Waaijer
 * Author URI: https://theseoframework.com/
 * License: GPLv3
 * Requires at least: 5.9
 * Requires PHP: 7.4.0
 * Requires Plugins: autodescription
 *
 * @package My_The_SEO_Framework\Compatbility
 */

add_filter(
	'the_seo_framework_query_supports_seo',
	function ( $supported ) {
		// If the query already doesn't support SEO, bail.
		if ( ! $supported ) return $supported;

		// phpcs:ignore, WordPress.Security.NonceVerification -- We're not processing data.
		if ( tsf()->query()->is_search() && ! empty( $_GET['type_aws'] ) )
			$supported = false;

		return $supported;
	},
);
