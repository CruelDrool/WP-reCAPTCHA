<?php
namespace CD\recaptcha;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the configuration.
 *
 * @package CD_reCAPTCHA
 * @since 1.0.0
 */
class Config {
	
	/**
	 * @since 1.0.0
	 * @var string Path to the plugin file.
	 */
	private $file;

	/**
	 * @since 1.0.0
	 * @var array Plugin metadata.
	 */
	private $plugin_data;
	
	/**
	 * @since 1.0.0
	 * @since 1.0.6 Changed to a constant
	 * @var string
	 */
	private const PREFIX = 'cdr';

	/**
	 * @since 1.0.0
	 * @var string Option name used in wp_options
	 */
	private $option_name;

	/**
	 * @since 1.0.6
	 * @var bool
	 */
	private $is_active_for_network;
	
	/**
	 * @since x.y.z
	 * @var array List of ISO-8601 date formats
	 */
	private const DATE_FORMATS = [
		'daily'   => 'Y-m-d',
		'weekly'  => 'o-\WW',
		'monthly' => 'Y-m',
		'yearly'  => 'Y',
	];

	/** 
	 * @since 1.0.0
	 * @var array Possible domain names to load the script and verify the token.
	 */
	private const DOMAINS = [
		'GOOGLE' => 'google.com',
		'RECAPTCHA' => 'recaptcha.net',
	];

	/**
	 * @since 1.0.0
	 * @since 1.0.6 Changed to a constant.
	 * 
	 * @var array Default options.
	 */
	private const DEFAULTS = [
		'recaptcha_version'             => 'v2_checkbox',
		'recaptcha_domain'              => self::DOMAINS['GOOGLE'],
		'recaptcha_log'                 => false,
		'recaptcha_log_rotate_interval' => 'monthly',
		'recaptcha_log_ip'              => true,
		'debug_log'                     => false,
		'debug_log_rotate_interval'     => 'monthly',
		'debug_log_seperate'            => false,
		'debug_log_min_level'           => 2,
		'log_directory'                 => '',
		'theme'                         => 'light',
		// 'language'                      => '',
		'badge'                         => 'bottomright',
		// 'v2_checkbox_site_key'          => '',
		// 'v2_checkbox_secret_key'        => '',
		// 'v2_invisible_site_key'         => '',
		// 'v2_invisible_secret_key'       => '',
		// 'v3_site_key'                   => '',
		// 'v3_secret_key'                 => '',
		'v2_checkbox_size'              => 'normal',
		'v2_checkbox_add_css'           => true,
		'v3_script_load'                => 'all_pages',
		'loggedin_hide'                 => true,
		'verify_origin'                 => false,
		'require_remote_ip'             => true,
		'enabled_forms'                 => [],
		'action_login'                  => 'login',
		'action_registration'           => 'register',
		'action_multisite_signup'       => 'multisite_signup',
		'action_lost_password'          => 'lost_password',
		'action_reset_password'         => 'reset_password',
		'action_comment'                => 'comment',
		'threshold_login'               => 0.5,
		'threshold_registration'        => 0.5,
		'threshold_multisite_signup'    => 0.5,
		'threshold_lost_password'       => 0.5,
		'threshold_reset_password'      => 0.5,
		'threshold_comment'             => 0.5,
	];

	/** 
	 * @since 1.0.6
	 * @var array Options loaded from the database.
	 */
	private $options;

	/**
	 * Constructor
	 * 
	 * @since 1.0.0
	 * @param string $file
	 */
	function __construct(string $file) {
		$this->file = $file;
		if ( ! function_exists( 'get_plugin_data' ) || ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}
		$this->plugin_data = get_plugin_data($file, false, false);
		$this->option_name = self::PREFIX . "_options";
		$this->is_active_for_network = is_plugin_active_for_network( plugin_basename( $this->file ) );

		// Retrieve options from the database.
		$get_options_func = $this->is_active_for_network ? 'get_site_option' : 'get_option';
		$this->options = call_user_func($get_options_func, $this->option_name, []);
	}

	/**
	 * Returns the current version.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	function get_current_version() {
		return $this->plugin_data['Version'];
	}

	/**
	 * Returns the plugin's name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	function get_plugin_name() {
		return $this->plugin_data['Name'];
	}

	/**
	 * Returns the plugin's Text Domain.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	function get_text_domain() {
		return $this->plugin_data['TextDomain'];
	}

	/**
	 * Returns value of an option. If no value can be found, return a default value.
	 *
	 * @since 1.0.0
	 * @param string $option Name of option
	 * @param mixed $default Optional. Provide a fallback default value
	 *
	 * @return mixed
	 */
	function get_option($option, $default = '') {
		$default = self::DEFAULTS[$option] ?? $default;
		$value = $this->options[$option] ?? $default;

		return $value;
	}
	
	/**
	 * Get the default error message given the reCAPTCHA version.
	 *
	 * @since 1.0.5
	 * @param string $version 
	 *
	 * @return string
	 */
	function get_default_error_msg($version) {
		switch($version) {
			case 'v2_checkbox':
			case 'v2_invisible':
				$string = __( 'The CAPTCHA solution you provided was incorrect.', 'cd-recaptcha');
				break;
			case 'v3':
				$string = __( 'reCAPTCHA v3 returns a score based on your interaction with this site. Your score did not meet our threshold requirement set for this particular action.', 'cd-recaptcha');
				break;
			default:
				$string = '';
				break;
			
		}
		return $string;
	}
	
	/**
	 * Get default value for an option.
	 * 
	 * @since 1.0.0
	 * @param string $option Name of option
	 * 
	 * @return mixed
	 */
	function get_default($option) {
		return self::DEFAULTS[$option] ?? '';
	}
	
	/**
	 * Retrieves the selected reCAPTCHA domain. Also verifies that the domain is a valid one.
	 * 
	 * @since 1.0.0
	 * 
	 * @return string
	 */
	function get_domain() {
		$domain = $this->get_option('recaptcha_domain');
		$domain = in_array($domain, self::DOMAINS) ? $domain : self::DEFAULTS['recaptcha_domain'];
		
		return $domain;
	}
	
	/**
	 * Returns a lists of available domains.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	function get_domains() {
		return self::DOMAINS;
	}

	/**
	 * Update the value of an option.
	 *
	 * @since 1.0.0
	 * @since 1.0.6 Discarding options with values that are the same as default. Renamed parameter `$options` to `$option`.
	 * 
	 * @param string|array $option Name of an option or an associative array with multiple option names and corresponding values set.
	 * @param mixed $value Optional. Value to set for the given option. Default is an empty string. Ignored if parameter `$option` is an array.
	 *
	 * @return bool True if a successful update, false otherwise.
	 */
	function update_option($option, $value = '') {
		if ( !isset($option) ) { return false; }
		if ( empty($option) ) { return false; }

		$options = is_array( $option ) ? $option : [$option => $value];

		$options = array_merge( $this->options, $options );

		foreach( $options as $key => $value) {
			$default = $this->get_default($key);
			if ( $default == $value) {
				unset( $options[$key] );
			}
		}

		$this->options = $options;

		return $this->save_options();
	}

	/**
	 * Delete an option.
	 *
	 * @since 1.0.6
	 * @param string|array $option Name of option or an array with options to delete.
	 *
	 * @return bool True if a successful update, false otherwise.
	 */
	function delete_option($option) {
		if ( !isset($option) ) { return false; }
		if ( empty($option) ) { return false; }

		$options = is_array( $option ) ? $option : [$option];

		foreach( $options as $o) {
			if (isset( $this->options[$o] )) {
				unset( $this->options[$o] );
			}
		}

		return $this->save_options();
	}

	/**
	 * Save the options
	 *
	 * @since 1.0.6
	 *
	 * @return bool True if a successful update, false otherwise.
	 */
	private function save_options() {
		$update_options_func = $this->is_active_for_network ? 'update_site_option' : 'update_option';

		return call_user_func($update_options_func, $this->option_name, $this->options);
	}

	/**
	 * Return plugin file path.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	function get_file() {
		return $this->file;
	}

	/**
	 * Returns the option name used in wp_options.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	function get_option_name() {
		return $this->option_name;
	}

	/**
	 * Returns the plugin prefix used.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	function get_prefix() {
		return self::PREFIX;
	}
	
	/**
	 * Returns whether the plugin is active for the entire network or not.
	 *
	 * @since 1.0.6 Replaces `is_plugin_active_for_network()`
	 *
	 * @return bool True if active for the network, otherwise false.
	 */
	function get_is_active_for_network() {
		return $this->is_active_for_network;
	}

	/**
	 * Get date a format.
	 *
	 * @since x.y.z
	 * @param string $type 
	 *
	 * @return string ISO 8601 date format
	 */
	function get_date_format($type = 'monthly') {
		return self::DATE_FORMATS[$type] ?? self::DATE_FORMATS['monthly'];
	}
}
?>
