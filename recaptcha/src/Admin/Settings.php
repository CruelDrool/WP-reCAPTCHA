<?php
namespace CD\recaptcha\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Creates the Admin page.
 *
 * @package CD_reCAPTCHA
 * @since 1.0.0
 */
class Settings {

	/**
	 * @since 1.0.0
	 * @var object
	 */
	private $config;

	/**
	 * @since 1.0.0
	 * @var array
	 */
	private $fields;

	/**
	 * @since 1.0.0
	 * @var string
	 */
	private $menu_slug;

	/**
	 * Constructor
	 * 
	 * @since 1.0.0
	 * @param string $file
	 * @param object $config
	 * @param \CD\recaptcha\Config $plugin_data
	 */
	function __construct(\CD\recaptcha\Config $config) {
		$this->config = $config;
		$this->menu_slug = $this->config->get_prefix().'-settings';
		$this->fields = $this->get_fields();
		$this->actions_filters();
	}

	/**
	 * Registers actions and filters for WordPress admin backend.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function actions_filters() {
		
		add_action( 'admin_init', [ $this, 'admin_init' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
		add_action( 'plugin_row_meta', [ $this, 'add_meta_links' ], 10, 3 );

		$submenu_hook_name = 'admin_menu';
		if ( $this->config->get_is_active_for_network() ) {
			$submenu_hook_name = 'network_admin_menu';
			add_action( 'admin_init', [ $this, 'network_settings_save' ], 99 );
		}

		add_action( $submenu_hook_name, [ $this, 'add_submenu_page' ] );
	}

	/**
	 * 
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function admin_init() {

		register_setting( $this->config->get_option_name(), $this->config->get_option_name(), ['sanitize_callback' => [$this, 'options_sanitize']] );
		foreach ( $this->get_sections() as $section_id => $section ) {
			add_settings_section( $section_id, $section['section_title'], $section['section_callback'] ?? null, $this->config->get_option_name() );
		}
		foreach ( $this->fields as $field_id => $field ) {
			add_settings_field( $field['id'], $field['label'], $field['callback'] ?? [$this, 'callback'], $this->config->get_option_name(), $field['section_id'], $field );
		}
	}

	/**
	 * Enqueues admin scripts and styles.
	 *
	 * @since x.y.z
	 * @param string $hook_suffix 
	 *
	 * @return void
	 */
	function admin_enqueue_scripts($hook_suffix) {
		// Ensure it only outputs on our own settings page.
		if ( $hook_suffix == "settings_page_{$this->menu_slug}" ) {
			wp_enqueue_style( $this->menu_slug, plugins_url( '/', $this->config->get_file() ) . 'assets/css/settings.css', [], $this->config->get_current_version() );
		}
	}

	/**
	 * Adds this plugin's settings page as a submenu page to the Settings main menu.
	 *
	 * @since x.y.z Consolidation of menu_page() and network_menu_page()
	 *
	 * @return void
	 */
	function add_submenu_page() {
		$parent_slug = $this->config->get_is_active_for_network() ? 'settings.php' : 'options-general.php';
		$capability = $this->config->get_is_active_for_network() ? 'manage_network_options' : 'manage_options';
		add_submenu_page( $parent_slug, sprintf(__('%s Settings', 'cd-recaptcha'), $this->config->get_plugin_name()), $this->config->get_plugin_name(), $capability, $this->menu_slug, [$this, 'admin_settings' ] );
	}

	/**
	 * Add custom links to the meta row.
	 *
	 * @since x.y.z
	 * @param array $plugin_meta 
	 * @param string $plugin_file 
	 * @param array $plugin_data 
	 *
	 * @return array
	 */
	function add_meta_links($plugin_meta, $plugin_file, $plugin_data) {

		if ( $plugin_file == plugin_basename($this->config->get_file()) ) {
			/* 
			Replace "View details" link.
			WordPress ignoring Plugin URI due to an elseif in 
			wp-admin/includes/class-wp-plugins-list-table.php, line 1088.
			 */
			if ( current_user_can( 'install_plugins' ) ) {
				$plugin_meta[2] = sprintf('<a href="%s" aria-label="%s">%s</a>',
					$plugin_data['PluginURI'],
					sprintf( translate( 'Visit plugin site for %s' ), $plugin_data['Name'] ),
					translate( 'Visit plugin site' )
				);
			}
			
			$url = '';
			if ( $this->config->get_is_active_for_network() && current_user_can('manage_network_options')) {
				$url = network_admin_url( "settings.php?page={$this->menu_slug}" );
			} elseif ( current_user_can('manage_options') ) {
				$url = admin_url( "options-general.php?page={$this->menu_slug}" );
			}

			if ( !empty($url) ) {
				$plugin_meta[] = sprintf('<a href="%s">%s</a>',
					$url,
					translate( 'Settings' )
				);
			}
		}

		return $plugin_meta;
	}

	/**
	 * [Multisite - Network Admin] Save settings
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	 function network_settings_save() {
		if ( current_user_can( 'manage_network_options' ) &&
			isset( $_POST[$this->config->get_option_name()] ) &&
			isset( $_POST['action'] ) && $_POST['action'] === 'update' &&
			isset( $_GET['page'] ) && $this->menu_slug === $_GET['page'] ) {

			check_admin_referer( $this->config->get_option_name().'-options' );

			$value = wp_unslash( $_POST[$this->config->get_option_name()] );
			if ( ! is_array( $value ) ) {
				$value = [];
			}
			$this->config->update_option( $value );
			
			add_settings_error( $this->menu_slug, 'settings_updated', translate( 'Settings saved.' ), 'success' );

			set_transient( 'settings_errors', get_settings_errors($this->menu_slug), 30 );

			// Redirect back to the settings page that was submitted.
			$goback = add_query_arg( 'settings-updated', 'true', wp_get_referer() );
			wp_redirect( $goback );
			exit;
		}
	}

	/**
	 * Output the Admin page
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function admin_settings() {
		?>
		
		<script>
			jQuery(document).ready(function( $ ){		
				function show_hide_fields(){
					var selected_value = $('#<?php echo $this->config->get_option_name();?>_recaptcha_version').val();
					$( '.hidden' ).hide();
					$( '.show-field-for-'+ selected_value ).show();
				}
				if( $('#<?php echo $this->config->get_option_name();?>_recaptcha_version').length ){
					show_hide_fields();
				}
				
				$('.form-table').on( "change", "#<?php echo $this->config->get_option_name();?>_recaptcha_version", function(e) {
				show_hide_fields();
				});
			});
		</script>
		<div class="wrap">
			<h1><?php printf(__('%s Settings', 'cd-recaptcha'), $this->config->get_plugin_name()) ?></h1>
			<?php
			$page = 'options.php';
			if ($this->config->get_is_active_for_network()) {
				$page = '';
				settings_errors();
			}
			?>
			<form method="post" action="<?php echo $page; ?>">
				<?php
				settings_fields( $this->config->get_option_name() );
				do_settings_sections( $this->config->get_option_name() );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Generate sections.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	function get_sections() {
		$sections = [
			'general' => [
				'section_title'    => __( 'General', 'cd-recaptcha' ),
				// 'section_callback' => function() {
					// printf( __( 'Get reCAPTCHA keys from <a href="%s" target="_blank" rel="noopener noreferrer">Google</a>. Make sure to get keys for your selected version.', 'cd-recaptcha' ), 'https://www.google.com/recaptcha/admin' );
				// },
			],
			'forms'       => [
				'section_title' => __( 'Forms', 'cd-recaptcha' ),
			],
			'actions'       => [
				'section_title' => sprintf('<span class="hidden show-field-for-v3">%s</span>',__( 'Actions', 'cd-recaptcha' ) ),
				'section_callback' => function() {
					printf('<p class="hidden show-field-for-v3">%s</p>', __( 'Action names may only contain alphanumeric characters, underscores, and forward slashes.', 'cd-recaptcha' ));
				},
			],
			'thresholds'       => [
				'section_title' => sprintf('<span class="hidden show-field-for-v3">%s</span>',__( 'Thresholds', 'cd-recaptcha' ) ),
				'section_callback' => function() {
					printf('<p class="hidden show-field-for-v3">%s</p>',
						/* translators: 1: 1.0, 2: 0.0 */
						sprintf(__( 'reCAPTCHA v3 returns a score (%1$s is very likely a good interaction, %2$s is very likely a bot).', 'cd-recaptcha' ), 
							sprintf( '<samp>%s</samp>', number_format_i18n(1.0, 1) ),
							sprintf( '<samp>%s</samp>', number_format_i18n(0.0, 1) )
						)
					);
				},
			],
			'other'       => [
				'section_title' => __( 'Other', 'cd-recaptcha' ),
			],
		];

		if ( is_main_site() ) {
			$sections['logging'] = ['section_title' => __( 'Logging', 'cd-recaptcha' )];
		}

		return $sections;
	}

	/**
	 * Generate form fields.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	function get_fields() {
		$score_values = [];
		for ( $i = 0.0; $i <= 1; $i += 0.1 ) {
			$score_values[ "$i" ] = number_format_i18n( $i, 1 );
		}

		$domains = [];
		
		foreach ($this->config->get_domains() as $key => $domain) {
			$domains[$domain] = $domain;
		}

		$fields = [
			// General
			'recaptcha_version'            => [
				'label'      => __( 'Version', 'cd-recaptcha' ),
				'section_id' => 'general',
				'type'       => 'select',
				'class'      => 'regular',
				'std'        => $this->config->get_default('captcha_version'),
				'options'    => [
					'v2_checkbox'  => __( 'v2 "I\'m not a robot"', 'cd-recaptcha' ),
					'v2_invisible' => __( 'v2 Invisible', 'cd-recaptcha' ),
					'v3'           => __( 'v3', 'cd-recaptcha' ),
				],
				'desc'       => sprintf( __( 'Select your reCAPTCHA version. Make sure to use keys for your selected version. %s.', 'cd-recaptcha' ),
									sprintf( '<a href="https://developers.google.com/recaptcha/docs/versions" target="_blank">%s</a>',
										__( 'Read more about the versions', 'cd-recaptcha' )
									)
								),
			],
			'v2_checkbox_site_key'           => [
				'label'      => __( 'Site Key', 'cd-recaptcha' ),
				'section_id' => 'general',
				'class'		=> 'hidden regular-text show-field-for-v2_checkbox',
				'desc'		=> sprintf( __( 'The public site key is used to load the widget. %s.', 'cd-recaptcha' ), 
								sprintf( '<a href="https://www.google.com/recaptcha" target="_blank">%s</a>', 
									__( 'Keys can be obtained here', 'cd-recaptcha' )
								)
							),
			],
			'v2_checkbox_secret_key'         => [
				'label'      => __( 'Secret Key', 'cd-recaptcha' ),
				'section_id' => 'general',
				'class'		=> 'hidden regular-text show-field-for-v2_checkbox',
				'desc'		=> sprintf( __( 'The private secret key is for communication between your site and the reCAPTCHA verification server. %s.', 'cd-recaptcha' ), 
								sprintf( '<a href="https://www.google.com/recaptcha" target="_blank">%s</a>', 
									__( 'Keys can be obtained here', 'cd-recaptcha' )
								)
							),
			],
			'v2_invisible_site_key'           => [
				'label'      => __( 'Site Key', 'cd-recaptcha' ),
				'section_id' => 'general',
				'class'		=> 'hidden regular-text show-field-for-v2_invisible',
				'desc'		=> sprintf( __( 'The public site key is used to load the widget. %s.', 'cd-recaptcha' ), 
								sprintf( '<a href="https://www.google.com/recaptcha" target="_blank">%s</a>', 
									__( 'Keys can be obtained here', 'cd-recaptcha' )
								)
							),
			],
			'v2_invisible_secret_key'         => [
				'label'      => __( 'Secret Key', 'cd-recaptcha' ),
				'section_id' => 'general',
				'class'		=> 'hidden regular-text show-field-for-v2_invisible',
				'desc'		=> sprintf( __( 'The private secret key is for communication between your site and the reCAPTCHA verification server. %s.', 'cd-recaptcha' ), 
								sprintf( '<a href="https://www.google.com/recaptcha" target="_blank">%s</a>', 
									__( 'Keys can be obtained here', 'cd-recaptcha' )
								)
							),
			],
			'v3_site_key'           => [
				'label'      => __( 'Site Key', 'cd-recaptcha' ),
				'section_id' => 'general',
				'class'		=> 'hidden regular-text show-field-for-v3',
				'desc'		=> sprintf( __( 'The public site key is used to load the widget. %s.', 'cd-recaptcha' ), 
								sprintf( '<a href="https://www.google.com/recaptcha" target="_blank">%s</a>', 
									__( 'Keys can be obtained here', 'cd-recaptcha' )
								)
							),
			],
			'v3_secret_key'         => [
				'label'      => __( 'Secret Key', 'cd-recaptcha' ),
				'section_id' => 'general',
				'class'		=> 'hidden regular-text show-field-for-v3',
				'desc'		=> sprintf( __( 'The private secret key is for communication between your site and the reCAPTCHA verification server. %s.', 'cd-recaptcha' ), 
								sprintf( '<a href="https://www.google.com/recaptcha" target="_blank">%s</a>', 
									__( 'Keys can be obtained here', 'cd-recaptcha' )
								)
							),
			],
			'v2_checkbox_error_message'=> [
				'label'      => __( 'Error message', 'cd-recaptcha' ),
				'section_id' => 'general',
				'type'		=> 'textarea',
				'placeholder'	=> $this->config->get_default_error_msg('v2_checkbox'),
				'desc'	=> __( 'In this textbox, you can type in a custom error message. Leave it empty to use the default one.' , 'cd-recaptcha'),
				'class'		=> 'hidden regular-text show-field-for-v2_checkbox',
			],
			'v2_invisible_error_message'=> [
				'label'      => __( 'Error message', 'cd-recaptcha' ),
				'section_id' => 'general',
				'type'		=> 'textarea',
				'placeholder' => $this->config->get_default_error_msg('v2_invisible'),
				'desc'	=> __( 'In this textbox, you can type in a custom error message. Leave it empty to use the default one.' , 'cd-recaptcha'),
				'class'		=> 'hidden regular-text show-field-for-v2_invisible',
			],
			'v3_error_message'      => [
				'label'      => __( 'Error message', 'cd-recaptcha' ),
				'section_id' => 'general',
				'type'		=> 'textarea',
				'placeholder' => $this->config->get_default_error_msg('v3'),
				'desc'	=> __( 'In this textbox, you can type in a custom error message. Leave it empty to use the default one.' , 'cd-recaptcha'),
				'class'		=> 'hidden regular-text show-field-for-v3',
			],
			'loggedin_hide'      => [
				'label'      => __( 'Hide for logged-in users', 'cd-recaptcha' ),
				'section_id' => 'general',
				'type'       => 'checkbox',
				'class'      => 'checkbox',
				'cb_label'		 => __( 'Only load for guest users.', 'cd-recaptcha'),
			],
			'recaptcha_domain'      =>[
				'label'      => __( 'Request domain', 'cd-recaptcha' ),
				'section_id' => 'general',
				'type'       => 'select',
				'class'      => 'regular',
				'std'        => $this->config->get_default('recaptcha_domain'),
				'options'    => $domains,
								/* translators: 1: recaptcha.net 2: google.com */
				'desc'        => sprintf( __( 'The domain to fetch the script from, and to use when verifying requests. Use %1$s when %2$s is not accessible.', 'cd-recaptcha' ),
									'<samp>recaptcha.net</samp>',
									'<samp>google.com</samp>'
								),
			],
			'verify_origin'  => [
				'label'      => __( 'Verify origin of the solutions', 'cd-recaptcha' ),
				'section_id' => 'general',
				'type'       => 'checkbox',
				'class'      => 'checkbox',
				'desc'		 => sprintf( __( '%s This is only required if you have chosen not to have Google do this verification.', 'cd-recaptcha' ), 
									sprintf( '<strong>%s</strong>', __( 'NB!', 'cd-recaptcha' ) ), 
								),
			],
			'v3_script_load'     => [
				'label'      => __( 'Load on...', 'cd-recaptcha' ),
				'section_id' => 'general',
				'type'       => 'select',
				'class'      => 'regular hidden show-field-for-v3',
				'std'        => $this->config->get_default('v3_script_load'),
				'options'    => [
					'all_pages'  => __( 'All pages', 'cd-recaptcha' ),
					'form_pages' => __( 'Form pages', 'cd-recaptcha' ),
				],
				'desc'       => __( 'For analytics purposes, it\'s recommended to load the widget in the background of all pages.', 'cd-recaptcha' ),
			],
			'require_remote_ip'     => [
				'label'      => __( 'Require the client\'s IP address', 'cd-recaptcha' ),
				'section_id' => 'general',
				'type'       => 'checkbox',
				'class'      => 'checkbox',
				'desc'       => __( 'Require that the client\'s IP address has been determined before submitting data to the reCAPTCHA server. An undetermined IP address will be treated as a failed CAPTCHA attempt.', 'cd-recaptcha' ),
			],
			// Forms
			'enabled_forms'      => [
				'label'      => __( 'Enabled forms', 'cd-recaptcha' ),
				'section_id' => 'forms',
				'type'       => 'multicheck',
				'class'      => 'checkbox',
				'options'    => [
					'login'          => __( 'Login', 'cd-recaptcha' ),
					'registration'   => __( 'Registration', 'cd-recaptcha' ),
					'ms_user_signup' => __( 'Multisite User Signup', 'cd-recaptcha' ),
					'lost_password'  => __( 'Lost Password', 'cd-recaptcha' ),
					'reset_password' => __( 'Reset Password', 'cd-recaptcha' ),
					'comment'        => __( 'Comment', 'cd-recaptcha' ),
				],
			],
			// Actions
			'action_login'   => [
				'label'      => __( 'Login', 'cd-recaptcha' ),
				'section_id' => 'actions',
				'class'      => 'regular hidden show-field-for-v3',
				'placeholder' => $this->config->get_default('action_login'),
				'sanitize_callback' => function($value) {
					return $this->sanitize_action_name($value, $this->config->get_default('action_login'));
				},
			],
			'action_registration'   => [
				'label'      => __( 'Registration', 'cd-recaptcha' ),
				'section_id' => 'actions',
				'class'      => 'regular hidden show-field-for-v3',
				'placeholder' => $this->config->get_default('action_registration'),
				'sanitize_callback' => function($value) {
					return $this->sanitize_action_name($value, $this->config->get_default('action_registration'));
				},
			],
			'action_multisite_signup' => [
				'label'      => __( 'Multisite User Signup', 'cd-recaptcha' ),
				'section_id' => 'actions',
				'class'      => 'regular hidden show-field-for-v3',
				'placeholder' => $this->config->get_default('action_multisite_signup'),
				'sanitize_callback' => function($value) {
					return $this->sanitize_action_name($value, $this->config->get_default('action_multisite_signup'));
				},
			],
			'action_lost_password'=> [
				'label'      => __( 'Lost Password', 'cd-recaptcha' ),
				'section_id' => 'actions',
				'class'      => 'regular hidden show-field-for-v3',
				'placeholder' => $this->config->get_default('action_lost_password'),
				'sanitize_callback' => function($value) {
					return $this->sanitize_action_name($value, $this->config->get_default('action_lost_password'));
				},
			],
			'action_reset_password'=> [
				'label'      => __( 'Reset Password', 'cd-recaptcha' ),
				'section_id' => 'actions',
				'class'      => 'regular hidden show-field-for-v3',
				'placeholder' => $this->config->get_default('action_reset_password'),
				'sanitize_callback' => function($value) {
					return $this->sanitize_action_name($value, $this->config->get_default('action_reset_password'));
				},
			],
			'action_comment'=> [
				'label'      => __( 'Comment', 'cd-recaptcha' ),
				'section_id' => 'actions',
				'class'      => 'regular hidden show-field-for-v3',
				'placeholder' => $this->config->get_default('action_comment'),
				'sanitize_callback' => function($value) {
					return $this->sanitize_action_name($value, $this->config->get_default('action_comment'));
				},
			],
			// Thresholds
			'threshold_login'=> [
				'label'      => __( 'Login', 'cd-recaptcha' ),
				'section_id' => 'thresholds',
				'type'       => 'select',
				'class'      => 'regular hidden show-field-for-v3',
				'std'        => $this->config->get_default('threshold_login'),
				'options'    => $score_values,
				'sanitize_callback' => [$this, 'sanitize_threshold_value'],
			],
			'threshold_registration'=> [
				'label'      => __( 'Registration', 'cd-recaptcha' ),
				'section_id' => 'thresholds',
				'type'       => 'select',
				'class'      => 'regular hidden show-field-for-v3',
				'std'        => $this->config->get_default('threshold_registration'),
				'options'    => $score_values,
				'sanitize_callback' => [$this, 'sanitize_threshold_value'],
			],
			'threshold_multisite_signup' => [
				'label'      => __( 'Multisite User Signup', 'cd-recaptcha' ),
				'section_id' => 'thresholds',
				'type'       => 'select',
				'class'      => 'regular hidden show-field-for-v3',
				'std'        => $this->config->get_default('threshold_multisite_signup'),
				'options'    => $score_values,
				'sanitize_callback' => [$this, 'sanitize_threshold_value'],
			],
			'threshold_lost_password'=> [
				'label'      => __( 'Lost Password', 'cd-recaptcha' ),
				'section_id' => 'thresholds',
				'type'       => 'select',
				'class'      => 'regular hidden show-field-for-v3',
				'std'        => $this->config->get_default('threshold_lost_password'),
				'options'    => $score_values,
				'sanitize_callback' => [$this, 'sanitize_threshold_value'],
			],
			'threshold_reset_password'=> [
				'label'      => __( 'Reset Password', 'cd-recaptcha' ),
				'section_id' => 'thresholds',
				'type'       => 'select',
				'class'      => 'regular hidden show-field-for-v3',
				'std'        => $this->config->get_default('threshold_reset_password'),
				'options'    => $score_values,
				'sanitize_callback' => [$this, 'sanitize_threshold_value'],
			],
			'threshold_comment'=> [
				'label'      => __( 'Comment', 'cd-recaptcha' ),
				'section_id' => 'thresholds',
				'type'       => 'select',
				'class'      => 'regular hidden show-field-for-v3',
				'std'        => $this->config->get_default('threshold_comment'),
				'options'    => $score_values,
				'sanitize_callback' => [$this, 'sanitize_threshold_value'],
			],
			// Other
			'language'           => [
				'label'      => __( 'Language code', 'cd-recaptcha' ),
				'section_id' => 'other',
				'class'      => 'small-text',
				'desc'		 => sprintf( __( 'Language of the widget. Leave it blank to auto-detect the language. %s.', 'cd-recaptcha' ),
									sprintf('<a href="https://developers.google.com/recaptcha/docs/language" target="_blank">%s</a>',
										__('Read more about language codes', 'cd-recaptcha' )
									)
								),
			],
			'theme'              => [
				'label'      => __( 'Theme', 'cd-recaptcha' ),
				'section_id' => 'other',
				'type'       => 'select',
				'class'      => 'regular',
				'std'        => $this->config->get_default('theme'),
				'options'    => [
					'light' => __( 'Light', 'cd-recaptcha' ),
					'dark'  => __( 'Dark', 'cd-recaptcha' ),
					'auto'  => __( 'Automatic', 'cd-recaptcha' ),
				],
				'desc'       => sprintf(__( 'Color theme of the widget. Select %s to set the theme based on the brightness of the page\'s background color.', 'cd-recaptcha' ),
									sprintf( '<i>%s</i>', __( 'Automatic', 'cd-recaptcha' ) )
								),
			],
			'badge'              => [
				'label'      => __( 'Placement', 'cd-recaptcha' ),
				'section_id' => 'other',
				'type'       => 'select',
				'class'      => 'regular hidden show-field-for-v2_invisible show-field-for-v3',
				'std'        => $this->config->get_default('badge'),
				'options'    => [
					'bottomright' => __( 'Bottom Right', 'cd-recaptcha' ),
					'bottomleft'  => __( 'Bottom Left', 'cd-recaptcha' ),
					'inline'      => __( 'Inline', 'cd-recaptcha' ),
					'auto'        => __( 'Automatic', 'cd-recaptcha' ),
				],
				'desc'       => sprintf( __( 'Position of the widget. Select %s to place the widget based on text direction (on a page with "right-to-left", the placement will be on the left).', 'cd-recaptcha' ),
									sprintf( '<i>%s</i>', __( 'Automatic', 'cd-recaptcha' ) )
								),
			],
			'v2_checkbox_size' => [
				'label'      => __( 'Size', 'cd-recaptcha' ),
				'section_id' => 'other',
				'type'       => 'select',
				'class'      => 'regular hidden show-field-for-v2_checkbox',
				'std'        => $this->config->get_default('v2_checkbox_size'),
				'options'    => [
					'normal'    => __( 'Normal', 'cd-recaptcha' ),
					'compact'   => __( 'Compact', 'cd-recaptcha' ),
					'auto'      => __( 'Automatic', 'cd-recaptcha' ),
				],
								/* translators: 1: Automatic, 2: Compact, 3: Normal */
				'desc'       => sprintf( __( 'Size of the widget. Select %1$s to automatically set the size to %2$s if the area is too narrow for %3$s.', 'cd-recaptcha' ),
									sprintf( '<i>%s</i>', __( 'Automatic', 'cd-recaptcha' ) ),
									sprintf( '<i>%s</i>', __( 'Compact', 'cd-recaptcha' ) ),
									sprintf( '<i>%s</i>', __( 'Normal', 'cd-recaptcha' ) )
							),
			],
			'v2_checkbox_add_css'  => [
				'label'      => __( 'Add stylesheet (CSS)', 'cd-recaptcha' ),
				'section_id' => 'other',
				'type'       => 'checkbox',
				'class'      => 'checkbox hidden show-field-for-v2_checkbox',
				'cb_label'   => __( "Add this plugin's stylesheet to the login page.", 'cd-recaptcha' ),
								/* translators: 1: Normal, 2: Automatic */
				'desc'       => sprintf(__( 'This stylesheet increases the width of the container element that holds the login form. This is to fit in the widget better. Only applicable if you have selected %1$s or %2$s as size.', 'cd-recaptcha' ),
									sprintf( '<i>%s</i>', __( 'Normal', 'cd-recaptcha' ) ),
									sprintf( '<i>%s</i>', __( 'Automatic', 'cd-recaptcha' ) )
								),
			],
		];

		if ( file_exists(WP_PLUGIN_DIR . '/sidebar-login') ) {
			$fields['disable_sidebar_login_js'] = [
				'label'      => sprintf(__( 'Disable the AJAX JavaScript from the plugin %s', 'cd-recaptcha' ),
									sprintf('<a href="%ssidebar-login" target="_blank">%s</a>',
										translate('https://wordpress.org/plugins/'),
										__('Sidebar Login', 'cd-recaptcha')
									),
								),
				'section_id' => 'forms',
				'type'       => 'checkbox',
				'desc'       => sprintf('%s</p><p class="description">%s</p><p class="description">%s', 
									sprintf( __( 'This setting only applies if you have activated that plugin and enabled the form %s.', 'cd-recaptcha' ),
										sprintf( '<em>%s</em>', __( 'Login', 'cd-recaptcha' ) )
									),
									sprintf( __( 'The problem is that this script does not submit the required information needed for the verification process. Fortunately, no errors happen. That is because "%s" sends the login requests to WordPress\' admin backend, where this reCAPTCHA plugin\'s frontend does not get loaded. However, it does mean that there is no verification of those particular login requests.', 'cd-recaptcha' ), __( 'Sidebar Login', 'cd-recaptcha' ) ) ,
									sprintf( __( 'You are seeing this setting because "%s" is installed in this WordPress installation.', 'cd-recaptcha' ), __( 'Sidebar Login', 'cd-recaptcha' ) )
								),
			];
		}

		if ( is_multisite() ) {
			unset($fields['enabled_forms']['options']['registration']);
			unset($fields['action_registration']);
			unset($fields['threshold_registration']);
		}

		if ( !(is_main_site() && is_multisite()) ) {
			unset($fields['enabled_forms']['options']['ms_user_signup']);
			unset($fields['action_multisite_signup']);
			unset($fields['threshold_multisite_signup']);
		}

		if ( is_main_site()  ) {
			$log_fields = [
				'recaptcha_log'  => [ 
					'label'      => __( 'Enable logging of reCAPTCHA\'s JSON response data', 'cd-recaptcha' ),
					'section_id' => 'logging',
					'type'       => 'checkbox',
					'class'      => 'checkbox',
					'desc'       => sprintf('%s</p><p class="description">%s</p><p class="description">%s',
										/* translators: 1: WP_DEBUG, 2: WP_DEBUG_LOG, 3: true */
										sprintf( __( 'Setting both %1$s and %2$s to %3$s will automatically enable this.', 'cd-recaptcha' ), '<code>WP_DEBUG</code>', '<code>WP_DEBUG_LOG</code>', '<code>true</code>' ),
										/* translators: 1: /wp-content, 2: Path to log directory */
										sprintf( __( 'The log files are by default written to the %1$s directory, but that can be changed using the setting "%2$s".', 'cd-recaptcha' ),
											'<code>/wp-content</code>',
											sprintf( '<strong>%s</strong>', __( 'Path to log directory', 'cd-recaptcha' ) )
										),
										sprintf( __( 'The text format used is %s.', 'cd-recaptcha' ), 
											sprintf('<a href="https://jsonlines.org" target="_blank">%s</a>', __( 'JSON Lines' , 'cd-recaptcha' ) )
										)
									),
				],
				'recaptcha_log_ip'  => [ 
					'label'      => __( 'Add the client\'s IP address to the JSON response data', 'cd-recaptcha' ),
					'section_id' => 'logging',
					'type'       => 'checkbox',
					'class'      => 'checkbox',
				],
				'recaptcha_log_rotate_interval'  => [ 
					'label'      => __( 'reCAPTCHA log\'s rotate interval', 'cd-recaptcha' ),
					'section_id' => 'logging',
					'type'       => 'select',
					'class'      => 'regular',
					'std'        => $this->config->get_default('recaptcha_log_rotate_interval'),
					'options'    => [
						'never'   => __( 'Never', 'cd-recaptcha' ),
						'daily'   => sprintf(__( 'Daily (%s)', 'cd-recaptcha' ), gmdate($this->config->get_date_format('daily'), time())),
						'weekly'  => sprintf(__( 'Weekly (%s)', 'cd-recaptcha' ), gmdate($this->config->get_date_format('weekly'), time())),
						'monthly' => sprintf(__( 'Monthly (%s)', 'cd-recaptcha' ), gmdate($this->config->get_date_format('monthly'), time())),
						'yearly'  => sprintf(__( 'Yearly (%s)', 'cd-recaptcha' ), gmdate($this->config->get_date_format('yearly'), time())),
					],
					'desc'       => sprintf( __( 'Uses UTC/GMT time with a %s date format.', 'cd-recaptcha' ),
										sprintf( '<a href="https://www.iso.org/standard/40874.html" target="_blank">%s</a>',
											__( 'ISO 8601' , 'cd-recaptcha')
										)
								),
				],
				'debug_log'  => [ 
					'label'      => __( 'Enable debug logging', 'cd-recaptcha' ),
					'section_id' => 'logging',
					'type'       => 'checkbox',
					'class'      => 'checkbox',
					'desc'       => sprintf('%s %s </p><p class="description">%s',
										__( 'This only applies to this plugin.', 'cd-recaptcha' ),
										/* translators: 1: WP_DEBUG, 2: WP_DEBUG_LOG, 3: true */
										sprintf( __( 'Setting both %1$s and %2$s to %3$s will automatically enable this.', 'cd-recaptcha' ),
											'<code>WP_DEBUG</code>',
											'<code>WP_DEBUG_LOG</code>',
											'<code>true</code>'
										),
										/* translators: 1: Separate debug log, 2: PHP has been set up to do it */
										sprintf( __( 'Important to note that if debug logging in WordPress is off, you should enable the setting "%1$s". If not, the the messages will be logged where %2$s.', 'cd-recaptcha' ),
											sprintf( '<strong>%s</strong>',
												__( 'Separate debug log', 'cd-recaptcha' )
									),
											sprintf( '<a href="https://www.php.net/manual/en/errorfunc.configuration.php#ini.error-log" target="_blank">%s</a>',
												__( 'PHP has been set up to do it', 'cd-recaptcha' )
											)
										)
									),
				],
				'debug_log_separate'  => [ 
					'label'      => __( 'Separate debug log', 'cd-recaptcha' ),
					'section_id' => 'logging',
					'type'       => 'checkbox',
					'class'      => 'checkbox',
					'desc'       => sprintf('%s</p><p class="description">%s</p><p class="description">%s</p><p class="description">%s.',
										sprintf( __( 'When debug logging in WordPress is enabled, the log is by default written to %s.' , 'cd-recaptcha' ), '<code>/wp-content/debug.log</code>' ),
										__( 'By enabling this option, this plugin\'s debug log will be written to a separate file in the same directory.' , 'cd-recaptcha' ),
										sprintf( __( 'However, this directory can be changed either by specifying a different file with %s (example: "%s") or by specifying your own directory using the setting "%s".' , 'cd-recaptcha' ),
											'<code>WP_DEBUG_LOG</code>',
											sprintf('<samp>%s</samp>',
												strtolower(substr(php_uname('s'),0,3)) == 'win' ? __( 'c:\path\to\debug.log', 'cd-recaptcha' ) : __( '/path/to/debug.log', 'cd-recaptcha' )
											),
											sprintf( '<strong>%s</strong>', __( 'Path to log directory', 'cd-recaptcha' ) ) 
										),
										sprintf('<a href="https://wordpress.org/documentation/article/debugging-in-wordpress/" target="_blank">%s</a>',__('Read more about debugging in WordPress', 'cd-recaptcha'))
									),
				],
				'debug_log_rotate_interval'  => [ 
					'label'      => __( 'Debug log\'s rotate interval', 'cd-recaptcha' ),
					'section_id' => 'logging',
					'type'       => 'select',
					'class'      => 'regular',
					'std'        => $this->config->get_default('debug_log_rotate_interval'),
					'options'    => [
						'never'   => __( 'Never', 'cd-recaptcha' ),
						'daily'   => sprintf(__( 'Daily (%s)', 'cd-recaptcha' ), gmdate($this->config->get_date_format('daily'), time())),
						'weekly'  => sprintf(__( 'Weekly (%s)', 'cd-recaptcha' ), gmdate($this->config->get_date_format('weekly'), time())),
						'monthly' => sprintf(__( 'Monthly (%s)', 'cd-recaptcha' ), gmdate($this->config->get_date_format('monthly'), time())),
						'yearly'  => sprintf(__( 'Yearly (%s)', 'cd-recaptcha' ), gmdate($this->config->get_date_format('yearly'), time())),
					],
					'desc'       => sprintf('%s %s',
										__( 'Only applicable if you have chosen to have a separate debug log.', 'cd-recaptcha' ),
										sprintf(__( 'Uses UTC/GMT time with a %s date format.', 'cd-recaptcha' ),
											sprintf('<a href="https://www.iso.org/standard/40874.html" target="_blank">%s</a>',
											__( 'ISO 8601' , 'cd-recaptcha'))
										),
									),
				],
				'debug_log_min_level'  => [ 
					'label'      => __( 'Debug log\'s minimum level', 'cd-recaptcha' ),
					'section_id' => 'logging',
					'type'       => 'select',
					'class'      => 'regular',
					'std'        => $this->config->get_default('debug_log_min_level'),
					'options'    => [
						sprintf( '*%s*', __( 'Disabled', 'cd-recaptcha' ) ),
						__( 'Error', 'cd-recaptcha' ),
						__( 'Warning', 'cd-recaptcha' ),
						__( 'Notice', 'cd-recaptcha' ),
						__( 'Info', 'cd-recaptcha' ),
						__( 'Debug', 'cd-recaptcha' ),
					],
					'desc'       => __('The minimum required severity level that messages must have for them to be written to the log.', 'cd-recaptcha' ),
				],
				'log_directory'  => [ 
					'label'      => __( 'Path to log directory', 'cd-recaptcha' ),
					'section_id' => 'logging',
					'type'       => 'text',
					'desc'       => sprintf('%s %s</p><p class="description">%s</p><p class="description">%s',
									__( 'Specify your own directory where the log files will be stored.', 'cd-recaptcha' ),
									__( 'Using an absolute path is recommended.', 'cd-recaptcha'),
									__( 'If you are logging to a directory that is web accessible, then you take measures to prevent people from accessing the logs.', 'cd-recaptcha' ),
									/* translators: 1: .htaccess , 2: Code example #1, 3: Code example #2  */
									sprintf( __( 'One way of doing that is to create a %1$s file in that directory you are logging to and add something like this: %2$s Or this: %3$s', 'cd-recaptcha' ),
										'<code>.htaccess</code>',
										'<code class="code-pre">RedirectMatch 404 ".*\.(log|jsonl)$"</code>',
										"<code class=\"code-pre\">&lt;Files ~ \".*\.(log|jsonl)$\"&gt;\n\tRedirect 404\n&lt;/Files&gt;</code>"
									)
								),
					'sanitize_callback' => function($value) {
						return $this->sanitize_directory_path($value, $this->config->get_default('log_directory'));
					},
				],
			];

			$fields = array_merge($fields, $log_fields);
		}

		foreach ( $fields as $field_id => $field ) {
			$fields[ $field_id ] = wp_parse_args(
				$field, [
					'id'         => $field_id,
					'label'      => '',
					'cb_label'   => '',
					'type'       => 'text',
					'class'      => 'regular-text',
					'section_id' => '',
					'desc'       => '',
					'std'        => '',
				]
			);
		}
		
		return $fields;
	}

	/**
	 * 
	 *
	 * @since 1.0.0
	 * @param mixed $array 
	 *
	 * @return void
	 */
	function callback( $field ) {
		$attrib = '';
		if ( isset( $field['required'] ) ) {
			$attrib .= ' required="required"';
		}
		if ( isset( $field['readonly'] ) ) {
			$attrib .= ' readonly="readonly"';
		}
		if ( isset( $field['disabled'] ) ) {
			$attrib .= ' disabled="disabled"';
		}
		if ( isset( $field['minlength'] ) ) {
			$attrib .= ' minlength="' . absint( $field['minlength'] ) . '"';
		}
		if ( isset( $field['maxlength'] ) ) {
			$attrib .= ' maxlength="' . absint( $field['maxlength'] ) . '"';
		}
		if ( isset ( $field['step'] ) ) {
			$attrib .= ' step="' . $field['step'] . '"';
		}
		if ( isset( $field['min'] ) ) {
			$attrib .= ' min="' . $field['min'] . '"';
		}
		if ( isset( $field['max'] ) ) {
			$attrib .= ' max="' . $field['max'] . '"';
		}

		// $value = $this->config->get_option( $field['id'], $field['std'] );
		$value = $this->config->get_option( $field['id'] );
		// $default = $this->config->get_default($field['id']);

		switch ( $field['type'] ) {
			case 'text':
			case 'email':
			case 'url':
			case 'number':
			case 'hidden':
			case 'submit':
				printf(
					'<input type="%1$s" id="%7$s_%2$s" class="%3$s" name="%7$s[%2$s]" placeholder="%4$s" value="%5$s" %6$s autocomplete="off" />',
					esc_attr( $field['type'] ),
					esc_attr( $field['id'] ),
					esc_attr( $field['class'] ),
					isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '',
					esc_attr( $value ),
					// isset( $field['placeholder'] ) && $value == $default  ? '' : esc_attr( $value ),
					$attrib,
					$this->config->get_option_name()
				);
				break;
			case 'textarea':
				printf( '<textarea id="%6$s_%1$s" class="%2$s" name="%6$s[%1$s]" placeholder="%3$s" %4$s rows="5" cols="50">%5$s</textarea>',
					esc_attr( $field['id'] ),
					esc_attr( $field['class'] ),
					isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '',
					$attrib,
					esc_textarea( $value ),
					// isset( $field['placeholder'] ) && $value == $default  ? '' : esc_textarea( $value ),
					$this->config->get_option_name()
				);
				break;
			case 'checkbox':
				// printf( '<input type="hidden" name="%s[%s]" value="" />', $this->config->get_option_name(), esc_attr( $field['id'] ) );
				printf(
					'<input type="hidden" name="%5$s[%1$s]" value="0" />
					<label><input type="checkbox" id="%5$s_%1$s" class="%2$s" name="%5$s[%1$s]" value="1" %3$s/> %4$s</label>',
					esc_attr( $field['id'] ),
					esc_attr( $field['class'] ),
					checked( $value, true, false ),
					esc_attr( $field['cb_label'] ),
					$this->config->get_option_name()
				);
				break;
			case 'multicheck':
				printf( '<input type="hidden" name="%s[%s][]" value="" />', $this->config->get_option_name(), esc_attr( $field['id'] ) );
				foreach ( $field['options'] as $key => $label ) {
					printf(
						'<label><input type="checkbox" id="%6$s_%1$s_%3$s" class="%2$s" name="%6$s[%1$s][]" value="%3$s" %4$s/> %5$s</label><br>',
						esc_attr( $field['id'] ),
						esc_attr( $field['class'] ),
						esc_attr( $key ),
						checked( in_array( $key, (array) $value ), true, false ),
						esc_attr( $label ),
						$this->config->get_option_name()
					);
				}
				break;
			case 'select':
				printf(
					'<select id="%3$s_%1$s" class="%2$s" name="%3$s[%1$s]">',
					esc_attr( $field['id'] ),
					esc_attr( $field['class'] ),
					$this->config->get_option_name()
				);
				foreach ( $field['options'] as $key => $label ) {
					printf(
						'<option value="%1$s"%2$s>%3$s</option>',
						esc_attr( $key ),
						selected( $value, $key, false ),
						esc_attr( $label )
					);
				}
				printf( '</select>' );
				break;
			case 'html':
				echo $field['std'];
				break;

			default:
				printf(  '<strong>Error</strong>: <i>%s</i> is an undefined <strong>&lt;input&gt;</strong> type.', esc_html( $field['type'] ) );
				break;
		}
		if ( ! empty( $field['desc'] ) ) {
			printf( '<p class="description">%s</p>', $field['desc'] );
		}
	}

	/**
	 * 
	 *
	 * @since 1.0.0
	 * @param mixed $value 
	 *
	 * @return array
	 */
	function options_sanitize( $value ) {

		if ( ! $value || ! is_array( $value ) ) {
			return $value;
		}

		foreach ( $value as $option_slug => $option_value ) {
			if ( isset( $this->fields[ $option_slug ] ) && ! empty( $this->fields[ $option_slug ]['sanitize_callback'] ) ) {
				$value[ $option_slug ] = call_user_func( $this->fields[ $option_slug ]['sanitize_callback'], $option_value );
			} elseif ( isset( $this->fields[ $option_slug ] ) ) {
				$value[ $option_slug ] = $this->posted_value_sanitize( $option_value, $this->fields[ $option_slug ] );
			}
		}
		return $value;
	}

	/**
	 * 
	 *
	 * @since 1.0.0
	 * @param mixed $value 
	 * @param array $field 
	 *
	 * @return mixed
	 */
	function posted_value_sanitize( $value, $field ) {
		// $sanitized = $value;
		switch ( $field['type'] ) {
			case 'text':
			case 'hidden':
				$sanitized = sanitize_text_field( trim( $value ) );
				break;
			case 'url':
				$sanitized = esc_url( $value );
				break;
			case 'number':
				$sanitized = absint( $value );
				break;
			case 'textarea':
			case 'wp_editor':
			case 'teeny':
				$sanitized = sanitize_textarea_field( $value );
				break;
			case 'checkbox':
				$sanitized = boolval( $value );
				break;
			case 'multicheck':
				$sanitized = is_array( $value ) ? array_filter( $value ) : [];
				foreach( $sanitized as $key => $p_value ) {
					if ( ! array_key_exists( $p_value, $field['options'] ) ) {
						unset( $sanitized[ $key ] );
					}
				}
				break;
			case 'select':
				if ( ! array_key_exists( $value, $field['options'] ) ) {
					$sanitized = $field['std']?? '';
				}
				break;
			default:
				$sanitized = $value;
				break;
		}
		return $sanitized ?? $value;
	}

	/**
	 * Sanitizes a v3 action name. Allowed characters are alphanumeric, underscores, and forward slashes.
	 *
	 * @since 1.0.0
	 * @since 1.0.5 Removed $default (fallback value).
	 * @since 1.0.6 Added back $default (fallback value).
	 * 
	 * @param string $name The name of the action.
	 * @param string $default
	 *
	 * @return string
	 */
	function sanitize_action_name($name, $default) {
		$input_name = $name;
		// This regex matches any characters that aren't in the list.
		$name = preg_replace('/[^a-zA-Z0-9_\/]+/', '', $name);

		// Empty value, fallback to default.
		if (empty($name)) {
			$name = $default;
		}

		// Output a information message if the sanitized action name differs from the input name.
		if ( $name !== $input_name ) {
			/* translators: 1:  Input name, 2: Sanitized name */
			$msg = sprintf( __( 'Action name "%1$s" was changed to "%2$s."', 'cd-recaptcha' ),
				$input_name,
				$name,
			);

			add_settings_error($this->menu_slug, 'sanitize_action_name', $msg, 'info' );
		}

		return $name;
	}

	/**
	 * Sanitizes a v3 threshold value to ensure it's a double value between 0.0 and 1.0.
	 *
	 * @since 1.0.6
	 * @param string $value 
	 *
	 * @return double
	 */
	function sanitize_threshold_value($value) {
		$value = floatval($value);
		if ( $value < 0 ) {
			$value = 0.0;
		} elseif ( $value > 1 ) {
			$value = 1.0;
		}
		return $value;
	}

	/**
	 * Sanitize a directory path.
	 *
	 * @since x.y.z
	 * @param string $value 
	 * @param string $default 
	 *
	 * @return void
	 */
	function sanitize_directory_path($value, $default) {
		if ( empty($value) ) {
			return $default;
		}

		// "Copy as path" function on Windows has the path in quotation marks.
		preg_match('/^"*(.+?)"*$/', $value, $matches);

		if ( !empty($matches) ) {
			$value = $matches[1];
		}
		unset($matches);

		$setting_code = 'sanitize_directory_path';
		$setting_slug = $this->menu_slug;
		$path = trim($value);

		// Relative directory path, try to resolve it.
		if ( boolval(preg_match('/^[^\x2f\x5c]|[\x2f\x5c]?\.\.[\x2f\x5c]|[\x2f\x5c]\.[\x2f\x5c]|[\x2f\x5c]\.+?\.?$/', $path)) && !boolval(preg_match('/^[A-Za-z]:/', $path)) ) {
			$path = realpath($path);

			if ( $path !== false ) {
				add_settings_error( $setting_slug, $setting_code,
					/* translators: 1: Input path from user, 2: Resolved absolute path */
					sprintf( __( 'The relative path, "%1$s", was changed to "%2$s".', 'cd-recaptcha' ), $value, $path ),
					'info'
				);
			}
		}

		if ( $path !== false && strtolower(substr(php_uname('s'),0,3)) == 'win' ) {
			// Replace forward slashes with backslashes.
			$path = str_replace('/','\\', $path);

			// c:directory -> c:\directory
			preg_match('/^([A-Za-z]:)([^\x5c].*)/', $path, $matches);
			if ( !empty($matches) ) {
				$path = sprintf('%s\%s', $matches[1], $matches[2]);
			}

			unset($matches);

			/**
			 * \ -> C:
			 * \directory -> C:\directory
			 * This will not match double backslash (as in \\server\share)
			 */
			if ( boolval(preg_match('/^\x5c$|^\x5c[^\x5c].*/', $path)) ) {
				// Use the drive letter of this file.
				$path = substr(__FILE__, 0,2) . $path;
			}

			// Uppercase drive letter
			$path = substr_replace($path, strtoupper(substr($path, 0,1)), 0 , 1);
		}

		$error = false;
		$error_msg = '';

		if ($path === false) {
			$error = true;
			$error_msg = sprintf(__( 'Was unable to find the absolute path for "%s".', 'cd-recaptcha' ), $value	);
		} elseif ( !file_exists($path) ) {
			$error = true;
			$error_msg = sprintf(__( 'The path, "%s", does not exist.', 'cd-recaptcha' ), $path);
		} elseif ( !is_dir($path )) {
			$error = true;
			$error_msg = sprintf(__( 'The path, "%s", is not a directory.', 'cd-recaptcha' ), $path);
		} elseif ( !is_writable($path) ) {
			$error = true;
			$error_msg = sprintf(__( 'The path, "%s", is not writable.', 'cd-recaptcha' ), $path);
		}

		if ( $error ) {
			add_settings_error($setting_slug, $setting_code, $error_msg, 'error' );
		}

		return $error ? $default : rtrim($path, "\x2f\x5c");
	}
}
