<?php
/**
 * Plugin Name: The SEO Framework - Remove category base
 * Plugin URI: https://theseoframework.com/
 * Description: Removed the category base from the URL, akin to how Yoast SEO does it.
 * Version: 1.0.2
 * Author: Sybre Waaijer
 * Author URI: https://theseoframework.com/
 * License: GPLv3
 * Requires at least: 5.9
 * Requires PHP: 7.4.0
 * Requires Plugins: autodescription
 *
 * @package My_The_SEO_Framework\Permalinks
 *
 * This is forked from Yoast SEO plugin: https://github.com/Yoast/wordpress-seo/blob/f61227287fbfdf8c175ddd2168336ac22dfd2540/inc/class-rewrite.php
 *
 * It's been modified to always remove the category base, and not just when the option is set.
 * This is because we do not have access to WPSEO_Options in this fork.
 *
 * To impove performance, the following changes were made:
 * It's also transformed to a functional style, instead of a class.
 * It's also made procedural where possible
 * Optimized opcodes (namespace escaping, removing unnecessary jumps and redundant variable assignments, etc.).
 *
 * This fork was requested because the following plugin doesn't reintroduce the 'blog' base: https://wordpress.org/plugins/no-category-base-wpml/.
 */

namespace My_The_SEO_Framework;

\defined( 'ABSPATH' ) or die;

\define( 'MY_THE_SEO_FRAMEWORK_REMOVE_CATEGORY_BASE_BASENAME', \plugin_basename( __FILE__ ) );

\add_filter( 'query_vars', __NAMESPACE__ . '\register_query_vars' );
\add_filter( 'term_link', __NAMESPACE__ . '\remove_category_base', 10, 3 );
\add_filter( 'request', __NAMESPACE__ . '\redirect_base' );
\add_filter( 'category_rewrite_rules', __NAMESPACE__ . '\modify_category_rewrite_rules' );

\add_action( 'created_category', __NAMESPACE__ . '\schedule_flush_rewrite_rules' );
\add_action( 'edited_category', __NAMESPACE__ . '\schedule_flush_rewrite_rules' );
\add_action( 'delete_category', __NAMESPACE__ . '\schedule_flush_rewrite_rules' );

\add_action( 'activate_' . \MY_THE_SEO_FRAMEWORK_REMOVE_CATEGORY_BASE_BASENAME, __NAMESPACE__ . '\schedule_flush_rewrite_rules' );
\add_action( 'deactivate_' . \MY_THE_SEO_FRAMEWORK_REMOVE_CATEGORY_BASE_BASENAME, __NAMESPACE__ . '\schedule_flush_rewrite_rules' );

/**
 * Override the category link to remove the category base.
 *
 * @hook term_link 10
 * @since 1.0.0
 *
 * @param string  $link     Term link, overridden by the function for categories.
 * @param WP_Term $term     Unused, term object.
 * @param string  $taxonomy Taxonomy slug.
 * @return string
 */
function remove_category_base( $link, $term, $taxonomy ) {

	if ( 'category' !== $taxonomy )
		return $link;

	$category_base_quoted = preg_quote(
		\trailingslashit( ltrim( \get_option( 'category_base' ) ?: 'category', '\/' ) ),
		'/',
	);

	return preg_replace(
		"/$category_base_quoted/u",
		'',
		$link,
		1,
	);
}

/**
 * Update the query vars with the redirect var when stripcategorybase is active.
 *
 * @hook query_vars 10
 * @since 1.0.0
 *
 * @param array<string> $query_vars Main query vars to filter.
 * @return array<string> The query vars.
 */
function register_query_vars( $query_vars ) {
	$query_vars[] = 'mytsf_category_redirect';
	return $query_vars;
}

/**
 * Checks whether the redirect needs to be created.
 *
 * @hook request 10
 * @since 1.0.2
 *
 * @param array<string> $query_vars Query vars to check for existence of redirect var.
 * @return array<string> The query vars.
 */
function redirect_base( $query_vars ) {
	if ( empty( $query_vars['mytsf_category_redirect'] ) ) {
		return $query_vars;
	}

	// Get the category by slug (the matched part)
	$category = get_category_by_slug( $query_vars['mytsf_category_redirect'] );
	if ( $category && ! is_wp_error( $category ) ) {
		$redirect_url = get_category_link( $category->term_id );
		wp_safe_redirect( $redirect_url, 301 );
		exit;
	}

	// Fallback: redirect to home if not found
	wp_safe_redirect( home_url(), 301 );
	exit;
}

/**
 * Helper: Get category by slug.
 */
function get_category_by_slug( $slug ) {
	$term = get_term_by( 'slug', $slug, 'category' );
	return $term;
}

/**
 * This function taken and only slightly adapted from WP No Category Base plugin by Saurabh Gupta.
 *
 * @hook category_rewrite_rules 10
 * @since 1.0.0
 *
 * @return array<string> The category rewrite rules.
 */
function modify_category_rewrite_rules() {
	global $wp_rewrite;

	$category_rewrite = [];

	$taxonomy            = \get_taxonomy( 'category' );
	$permalink_structure = \get_option( 'permalink_structure' );

	$blog_prefix = \is_main_site() && str_starts_with( $permalink_structure, '/blog/' )
		? 'blog/'
		: '';

	$categories = \get_categories( [ 'hide_empty' => false ] );

	if ( \is_array( $categories ) && $categories ) {
		foreach ( $categories as $category ) {
			$category_nicename = $category->slug;
			if ( $category->parent === $category->cat_ID ) {
				// Recursive recursion.
				$category->parent = 0;
			} elseif ( false !== $taxonomy->rewrite['hierarchical'] && 0 !== $category->parent ) {

				$parents = \get_category_parents( $category->parent, false, '/', true );

				if ( ! \is_wp_error( $parents ) )
					$category_nicename = $parents . $category_nicename;

				unset( $parents );
			}

			$category_rewrite = add_category_rewrites( $category_rewrite, $category_nicename, $blog_prefix, $wp_rewrite->pagination_base );

			// Adds rules for the uppercase encoded URIs.
			$category_nicename_filtered = str_contains( $category_nicename, '%' )
				? implode(
					'/',
					array_map(
						fn( $encoded ) => str_contains( $encoded, '%' ) ? strtoupper( $encoded ) : $encoded,
						explode( '/', $category_nicename ),
					),
				)
				: $category_nicename;

			if ( $category_nicename_filtered !== $category_nicename ) {
				$category_rewrite = add_category_rewrites( $category_rewrite, $category_nicename_filtered, $blog_prefix, $wp_rewrite->pagination_base );
			}
		}
		unset( $categories, $category, $category_nicename, $category_nicename_filtered );
	}

	// Redirect support from Old Category Base.
	$old_base = $wp_rewrite->get_category_permastruct();
	$old_base = str_replace( '%category%', '(.+)', $old_base );
	$old_base = trim( $old_base, '/' );

	$category_rewrite[ $old_base . '$' ] = 'index.php?mytsf_category_redirect=$matches[1]';

	return $category_rewrite;
}

/**
 * Adds required category rewrites rules.
 *
 * @since 1.0.0
 *
 * @param array<string> $rewrites        The current set of rules.
 * @param string        $category_name   Category nicename.
 * @param string        $blog_prefix     Multisite blog prefix.
 * @param string        $pagination_base WP_Query pagination base.
 * @return array<string> The added set of rules.
 */
function add_category_rewrites( $rewrites, $category_name, $blog_prefix, $pagination_base ) {

	$rewrite_name = "$blog_prefix($category_name)";

	$rewrites += [
		"$rewrite_name/feed/(feed|rdf|rss|rss2|atom)/?$" => 'index.php?category_name=$matches[1]&feed=$matches[2]',
		"$rewrite_name/$pagination_base/([0-9]{1,})/?$"  => 'index.php?category_name=$matches[1]&paged=$matches[2]',
		"$rewrite_name/?$"                               => 'index.php?category_name=$matches[1]',
	];

	return $rewrites;
}

/**
 * Trigger a rewrite_rule flush on shutdown.
 *
 * @hook created_category 10
 * @hook edited_category 10
 * @hook delete_category 10
 * @hook activate_remove-category-base/remove-category-base.php 10
 * @hook deactivate_remove-category-base/remove-category-base.php 10
 * @since 1.0.0
 *
 * @return void
 */
function schedule_flush_rewrite_rules() {
	\add_action( 'shutdown', 'flush_rewrite_rules' );
}
