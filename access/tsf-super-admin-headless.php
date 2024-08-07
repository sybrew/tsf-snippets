<?php
/**
 * Plugin Name: The SEO Framework - Force admin display level (Headless Mode)
 * Plugin URI: https://theseoframework.com/
 * Description: Denies TSF's admin display and edit capability to anyone but super admins using Headless Mode.
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

add_action(
	'plugins_loaded',
	function () {
		if ( is_admin() && ! is_super_admin() )
			defined( 'THE_SEO_FRAMEWORK_HEADLESS' )
				or define( 'THE_SEO_FRAMEWORK_HEADLESS', true );
	},
	0,
);
