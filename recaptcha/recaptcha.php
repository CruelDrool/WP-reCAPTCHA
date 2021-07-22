<?php
/*
Plugin Name: reCAPTCHA
Description: reCAPTCHA for Login, Register, Lost Password, Reset Password. Supports customisation of v2 and v3. Based on <a href="https://www.shamimsplugins.com">Shamim Hasan</a>'s <a href="https://wordpress.org/plugins/advanced-nocaptcha-recaptcha">Advanced noCaptcha & invisible Captcha</a>.
Version: 1.0.0
Author: CruelDrool
Author URI: https://github.com/CruelDrool
Plugin URI: https://github.com/CruelDrool/WP-reCAPTCHA
Update URI: https://github.com/CruelDrool/WP-reCAPTCHA/raw/main/current.json
Text Domain: cd-recaptcha
Requires PHP: 7.1
License: GNU GPLv3
*/

namespace CD\recaptcha;

defined( 'ABSPATH' ) || exit;

/**
 * Bail early if PHP version dependency is not met.
 */
if ( version_compare( PHP_VERSION, '7.1', '<' ) ) {
	return;
}

/**
 * Plugin header ("Update URI") introduced in WordPress 5.8.
 */
function update_plugin( $array, $plugin_data, $plugin_file, $locales){
	$request = wp_remote_get($plugin_data['UpdateURI']);
	$request_body = wp_remote_retrieve_body( $request );
	$result = json_decode( $request_body, true );
	return $result;
}

add_filter('update_plugins_github.com', 'CD\recaptcha\update_plugin', 10, 4);

/**
 * Load the PSR-4 autoloader.
 */
require __DIR__ . '/autoloader.php';

/**
 * Go head and load the actual plugin.
 */
function load_plugin() {
	static $instance;

	if ( is_null( $instance ) ) {
		// plugin_basename() is not playing nicely with Windows Junctions
		$file = implode('/', [WP_PLUGIN_DIR,basename(dirname(__FILE__)),basename(__FILE__)]);
		$instance = new Plugin($file);
	}

	return $instance;
}

add_action( 'plugins_loaded', 'CD\recaptcha\load_plugin');
