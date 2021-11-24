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
	 * @var string
	 */
	private $prefix = 'cdr';

	/**
	 * @since 1.0.0
	 * @var string
	 */
	private $option_name;
	
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
	 * @var array Default options.
	 */
	private $defaults = [
		'recaptcha_version'			=> 'v2_checkbox',
		'recaptcha_domain'			=> self::DOMAINS['GOOGLE'],
		'theme'						=> 'light',
		'theme_auto'				=> false,
		'language'					=> '',
		'badge'						=> 'bottomright',
		'badge_auto'				=> false,
		'v2_checkbox_site_key'		=> '',
		'v2_checkbox_secret_key'	=> '',
		'v2_invisible_site_key'		=> '',
		'v2_invisible_secret_key'	=> '',
		'v3_site_key'				=> '',
		'v3_secret_key'				=> '',
		'v2_checkbox_size'			=> 'normal',
		'v2_checkbox_adjust_size'	=> true,
		'v2_checkbox_remove_css'	=> false,
		'v3_script_load'			=> 'all_pages',
		'loggedin_hide'				=> true,
		'verify_origin'				=> false,
		'enabled_forms'				=> [],
		'action_login'				=> 'login',
		'action_registration'		=> 'register',
		'action_multisite_signup'	=> 'multisite_signup',
		'action_lost_password'		=> 'lost_password',
		'action_reset_password'		=> 'reset_password',
		'action_comment'			=> 'comment',
		'threshold_login'			=> 0.5,
		'threshold_registration'	=> 0.5,
		'threshold_multisite_signup'=> 0.5,
		'threshold_lost_password'	=> 0.5,
		'threshold_reset_password'	=> 0.5,
		'threshold_comment'			=> 0.5,
	];
	
	/**
	 * Constructor
	 * 
	 * @since 1.0.0
	 * @param string $file
	 */
	function __construct(string $file) {
		$this->file = $file;
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}
		$this->plugin_data = get_plugin_data($file);
		$this->option_name = $this->prefix . "_options";

		$this->defaults['v2_checkbox_error_message'] = __( 'The CAPTCHA solution you provided was incorrect.', 'cd-recaptcha');
		$this->defaults['v2_invisible_error_message'] = __( 'The CAPTCHA solution you provided was incorrect.', 'cd-recaptcha');
		$this->defaults['v3_error_message'] = __( 'reCAPTCHA v3 returns a score based on your interaction with this site. Your score did not meet our threshold requirement set for this particular action.', 'cd-recaptcha');
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
	  * @param string $option name of option
	  * @param mixed $default provide a fallback default value
	  *
	  * @return mixed
	  */
	function get_option($option, $default = '') {
		if ( $this->is_plugin_active_for_network() ) {
			$options = get_site_option( $this->get_option_name() );
		} else {
			$options = get_option( $this->get_option_name() );
		}
		
		$default = $this->defaults[$option] ?? $default;
		$value = $options[ $option ] ?? $default;
		
		return $value;
	}
	
	/**
	 * Get default value for an option.
	 * 
	 * @since 1.0.0
	 * @param mixed $option
	 * 
	 * @return mixed
	 */
	function get_default($option) {
		return $this->defaults[$option] ?? '';
	}
	
	/**
	 * Retrieves the selected reCAPTCHA domain. Also verifies that's a valid domains.
	 * 
	 * @since 1.0.0
	 * 
	 * @return string
	 */
	function get_domain() {
		$domain = $this->get_option('recaptcha_domain');
		$domain = in_array($domain, self::DOMAINS) ? $domain : $this->defaults['recaptcha_domain'];
		
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
	 * @param string $options name of option
	 * @param mixed $value Option value to set. Default is empty value.
	 *
	 * @return void
	 */
	function update_option( $options, $value = '') {

		if ( $options && ! is_array( $options ) ) {
			$options = [
				$options => $value,
			];
		}
		if ( ! is_array( $options ) ) {
			return false;
		}
		if ( $this->is_plugin_active_for_network() ) {
			update_site_option( $this->get_option_name(), wp_parse_args( $options, get_site_option( $this->get_option_name() ) ) );
		} else {
			update_option( $this->get_option_name(), wp_parse_args( $options, get_option( $this->get_option_name() ) ) );
		}

		return true;
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
		// return self::OPTION_NAME;
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
		return $this->prefix;
	}
	
	/**
	 * Determines whether the plugin is active for the entire network.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if active for the network, otherwise false.
	 */
	function is_plugin_active_for_network() {
		// Makes sure the plugin is defined before trying to use it
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}
		return is_plugin_active_for_network( plugin_basename( $this->file ) );
	}
}
?>
