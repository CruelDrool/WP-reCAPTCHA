<?php
/**
 *	Uninstall
 *
 *	Deletes all the plugin data
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$option_name = 'cdr_options';
 
delete_option($option_name);
 
// for site options in Multisite
delete_site_option($option_name);
