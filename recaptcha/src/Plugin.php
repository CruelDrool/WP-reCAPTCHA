<?php
namespace CD\recaptcha;

defined( 'ABSPATH' ) || exit;

/**
 * Sets up the plugin.
 *
 * @package CD_reCAPTCHA
 * @since 1.0.0
 */
class Plugin {

	/**
	 * @since 1.0.0
	 * @var string Path to the plugin file.
	 */
	private $file;

	/**
	 * @since 1.0.0
	 * @var Config Plugin options.
	 */
	private $config;
			
	/**
	 * Constructor
	 * 
	 * @since 1.0.0
	 * @param string $file
	 */
	function __construct(string $file) {
		$this->file = $file;
		$this->config = new Config($file);
		if ( $this->update() ) {
			$this->actions_filters();
		}
	}
	
	/**
	 * Registers actions and filters.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function actions_filters() {
		add_action( 'init', [$this, 'load_translations']);
		if ( is_admin() ) {
			add_action( 'wp_loaded', [$this, 'load_admin']);
		} else {
			add_action( 'wp_loaded', [$this, 'load_frontend']);
		}
	}

	/**
	 * Load translations
	 *
	 * @since 1.1.0 Rename of load_plugin_textdomain()
	 *
	 * @return void
	 */
	function load_translations() {
		load_plugin_textdomain( $this->config->get_text_domain(), false, dirname( plugin_basename( $this->file) ) . '/languages' );
	}

	/**
	 * Instantiates the Frontend class.
	 *
	 * @since 1.0.0
	 *
	 * @return object
	 */
	function load_frontend() {
		static $instance;
		if ( is_null( $instance )) {
			$instance = new Frontend($this->config);
		}
		return $instance;
	}
	
	/**
	 * Instantiates the Admin settings class.
	 *
	 * @since 1.0.0
	 *
	 * @return object
	 */
	function load_admin() {
		static $instance;
		if ( is_null( $instance )) {
			$instance = new Admin\Settings($this->config);
		}
		return $instance;
	}
	
	/**
	 * Handles any updates required.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	private function update() {
		$current_version = $this->config->get_current_version();
		$prev_version = $this->config->get_option('version');
		
		// Plugin updates
		$options = [];
		if ( !empty($prev_version) ) {

			if (version_compare( $prev_version, "1.0.6", '<' )) {

				if ( $this->config->get_option('theme_auto', false) )
					$options['theme'] = 'auto';

				if ( $this->config->get_option('badge_auto', false) )
					$options['badge'] = 'auto';

				if ( $this->config->get_option('v2_checkbox_adjust_size', true) )
					$options['v2_checkbox_size'] = 'auto';

				if ( $this->config->get_option('v2_checkbox_remove_css', false) )
					$options['v2_checkbox_add_css'] = false;
			}

			if (version_compare( $prev_version, "1.1.1", '<' )) {
				$options['action_ms_user_signup'] = $this->config->get_option('action_multisite_signup', 'multisite_signup');
				$options['threshold_ms_user_signup'] = $this->config->get_option('threshold_multisite_signup', 0.5);
			}

		}

		if ( version_compare( $current_version, $prev_version, '!=' ) ) {
			$options['version'] = $current_version;
		}

		if ( !empty($options) ) {
			$this->config->update_option($options);
		}

		return true;
	}
}
