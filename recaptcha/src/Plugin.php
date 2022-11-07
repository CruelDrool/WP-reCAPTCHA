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
		$this->init();
	}
	
	/**
	 * Initiate the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function init() {
		$updated = $this->update();

		if ($updated) {
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
		add_action( 'init', [$this, 'load_plugin_textdomain']);
		add_action( 'wp_loaded', [$this, 'load_frontend']);
		add_action( 'wp_loaded', [$this, 'load_admin']);
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
		if (!is_admin() && is_null( $instance )) {
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
		if (is_admin() && is_null( $instance )) {
			$instance = new Admin\Settings($this->config);
		}
		return $instance;
	}
	
	/**
	 * Load translations.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function load_plugin_textdomain() {
		load_plugin_textdomain( $this->config->get_text_domain(), false, dirname( plugin_basename( $this->file) ) . '/languages' );
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

				$this->config->delete_option(['theme_auto', 'badge_auto', 'v2_checkbox_adjust_size']);
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
?>
