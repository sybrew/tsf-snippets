<?php
/**
 * Plugin Name: The SEO Framework - GeoDirectory Compatibility
 * Plugin URI: https://theseoframework.com/
 * Description: Enables GeoDirectory's SEO variable system (%%category%%, %%tag%%, etc.) to work with TSF.
 * Version: 1.0.0
 * Author: Sybre Waaijer
 * Author URI: https://theseoframework.com/
 * License: GPLv3
 * Requires at least: 5.9
 * Requires PHP: 7.4.0
 *
 * @package My_The_SEO_Framework\Compatibility
 */

add_filter( 'the_seo_framework_title_from_generation', 'tsf_gd_filter_title', 10, 2 );
add_filter( 'the_seo_framework_generated_description', 'tsf_gd_filter_description', 10, 2 );

/**
 * Initializes GeoDirectory's SEO meta variables.
 *
 * @return bool True if initialized, false otherwise.
 */
function tsf_gd_init_meta() {

	static $initialized;

	if ( isset( $initialized ) )
		return $initialized;

	if ( ! class_exists( 'GeoDir_SEO' ) ) // phpcs:ignore TSF.Performance -- invoke autoloader
		return $initialized = false;

	GeoDir_SEO::set_meta();

	return $initialized = true;
}

/**
 * Filters TSF's generated title for GeoDirectory pages.
 *
 * @param string     $title The generated title.
 * @param array|null $args  The query arguments.
 * @return string
 */
function tsf_gd_filter_title( $title, $args ) {

	if ( isset( $args ) || is_admin() )
		return $title;

	if ( ! function_exists( 'geodir_is_geodir_page' ) || ! geodir_is_geodir_page() )
		return $title;

	tsf_gd_init_meta();

	return The_SEO_Framework\coalesce_strlen( GeoDir_SEO::$title ) ?? $title;
}

/**
 * Filters TSF's generated description for GeoDirectory pages.
 *
 * @param string     $desc The generated description.
 * @param array|null $args The query arguments.
 * @return string
 */
function tsf_gd_filter_description( $desc, $args ) {

	if ( isset( $args ) || is_admin() )
		return $desc;

	if ( ! function_exists( 'geodir_is_geodir_page' ) || ! geodir_is_geodir_page() )
		return $desc;

	tsf_gd_init_meta();

	return The_SEO_Framework\coalesce_strlen( GeoDir_SEO::$meta_description ) ?? $desc;
}
