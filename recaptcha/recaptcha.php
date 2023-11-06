<?php
/*
Plugin Name: reCAPTCHA
Description: Allows you to place Google's reCAPTCHA widget on your WordPress forms. Supports customization of all reCAPTCHA versions.
Version: 1.1.3
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
 * Emgergeny stop: in the unlikely event that the need should arise, stop the execution of this plugin's code.
 */
if ( is_file(__DIR__ . '/disable') ) {
	return;
}

/**
 * Bail early if PHP version dependency is not met.
 * Using visibility on class constants introduced in 7.1.
 * Also using the null coalescing operator introduced in 7.0.
 */
if ( version_compare( PHP_VERSION, '7.1', '<' ) ) {
	return;
}

/**
 * Plugin header ("Update URI") introduced in WordPress 5.8.
 */
function update_plugin( $update, $plugin_data, $plugin_file, $locales){
	/*
	This filter is applied inside a foreach loop in wp_update_plugins(). 
	So, if there a several plugins using the same hostname as Update URI, our function will be run for each of those other plugins.
	Better check if the loop has reached *our* plugin until we do anything.
	*/
	if ($plugin_file == plugin_basename(__FILE__)) {
		$request = wp_remote_get($plugin_data['UpdateURI']);
		$request_body = wp_remote_retrieve_body( $request );
		$update = json_decode( $request_body, true );
	}
	return $update;
}

add_filter('update_plugins_github.com', 'CD\recaptcha\update_plugin', 10, 4);

/**
 * Load the PSR-4 autoloader.
 */
require __DIR__ . '/autoloader.php';

/**
 * Go ahead and load the actual plugin.
 */
function load_plugin() {
	static $instance;

	if ( is_null( $instance ) ) {
		$instance = new Plugin(__FILE__);
	}

	return $instance;
}

add_action( 'plugins_loaded', 'CD\recaptcha\load_plugin');
