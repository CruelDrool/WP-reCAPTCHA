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
		$this->init();
	}
	
	/**
	 * 
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function init() {
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
		

		if ( $this->config->get_is_active_for_network() ) {
			add_action( 'admin_init', [ $this, 'network_settings_save' ], 99 );
			add_action( 'network_admin_menu', [ $this, 'network_menu_page' ] );
			add_filter( 'network_admin_plugin_action_links_' . plugin_basename( $this->config->get_file() ), [$this, 'add_settings_link' ] );
		} else {
			add_action( 'admin_menu', [ $this, 'menu_page' ] );
			add_filter( 'plugin_action_links_' . plugin_basename( $this->config->get_file() ), [ $this, 'add_settings_link' ] );
		}

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
					printf('<p class="hidden show-field-for-v3">%s</p>', sprintf(__( 'reCAPTCHA v3 returns a score (<samp>%s</samp> is very likely a good interaction, <samp>%s</samp> is very likely a bot).', 'cd-recaptcha' ), number_format_i18n(1.0, 1) , number_format_i18n(0.0, 1)));
				},
			],
			'other'       => [
				'section_title' => __( 'Other', 'cd-recaptcha' ),
			],
		];
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
				'desc'       => sprintf( __( 'Select your reCAPTCHA version. Make sure to use keys for your selected version. Read more about the versions on %s.', 'cd-recaptcha' ), '<a href="//developers.google.com/recaptcha/docs/versions" target="_blank">developers.google.com/recaptcha/docs/versions</a>'),
			],
			'v2_checkbox_site_key'           => [
				'label'      => __( 'Site Key', 'cd-recaptcha' ),
				'section_id' => 'general',
				'class'		=> 'hidden regular-text show-field-for-v2_checkbox',
				'desc'		=> sprintf( __('Your public site key that is used to load the widget. Keys can be obtained on %s.', 'cd-recaptcha'), '<a href="//www.google.com/recaptcha" target="_blank">www.google.com/recaptcha</a>'),
			],
			'v2_checkbox_secret_key'         => [
				'label'      => __( 'Secret Key', 'cd-recaptcha' ),
				'section_id' => 'general',
				'class'		=> 'hidden regular-text show-field-for-v2_checkbox',
				'desc'		=> sprintf( __('Your private secret key for communication between your site and the reCAPTCHA verification server. Keys can be obtained on %s.', 'cd-recaptcha'), '<a href="//www.google.com/recaptcha" target="_blank">www.google.com/recaptcha</a>'),
			],
			'v2_invisible_site_key'           => [
				'label'      => __( 'Site Key', 'cd-recaptcha' ),
				'section_id' => 'general',
				'class'		=> 'hidden regular-text show-field-for-v2_invisible',
				'desc'		=> sprintf( __('Your public site key that is used to load the widget. Keys can be obtained on %s.', 'cd-recaptcha'), '<a href="//www.google.com/recaptcha" target="_blank">www.google.com/recaptcha</a>'),
			],
			'v2_invisible_secret_key'         => [
				'label'      => __( 'Secret Key', 'cd-recaptcha' ),
				'section_id' => 'general',
				'class'		=> 'hidden regular-text show-field-for-v2_invisible',
				'desc'		=> sprintf( __('Your private secret key for communication between your site and the reCAPTCHA verification server. Keys can be obtained on %s.', 'cd-recaptcha'), '<a href="//www.google.com/recaptcha" target="_blank">www.google.com/recaptcha</a>'),
			],
			'v3_site_key'           => [
				'label'      => __( 'Site Key', 'cd-recaptcha' ),
				'section_id' => 'general',
				'class'		=> 'hidden regular-text show-field-for-v3',
				'desc'		=> sprintf( __('Your public site key that is used to load the widget. Keys can be obtained on %s.', 'cd-recaptcha'), '<a href="//www.google.com/recaptcha" target="_blank">www.google.com/recaptcha</a>'),
			],
			'v3_secret_key'         => [
				'label'      => __( 'Secret Key', 'cd-recaptcha' ),
				'section_id' => 'general',
				'class'		=> 'hidden regular-text show-field-for-v3',
				'desc'		=> sprintf( __('Your private secret key for communication between your site and the reCAPTCHA verification server. Keys can be obtained on %s.', 'cd-recaptcha'), '<a href="//www.google.com/recaptcha" target="_blank">www.google.com/recaptcha</a>'),
			],
			'v2_checkbox_error_message'=> [
				'label'      => __( 'Error message', 'cd-recaptcha' ),
				'section_id' => 'general',
				'type'		=> 'textarea',
				'placeholder'	=> $this->config->get_default_error_msg('v2_checkbox'),
				'desc'	=> __( 'In this textbox you can type in a custom error message. Leave it empty to use the default one.' , 'cd-recaptcha'),
				'class'		=> 'hidden regular-text show-field-for-v2_checkbox',
			],
			'v2_invisible_error_message'=> [
				'label'      => __( 'Error message', 'cd-recaptcha' ),
				'section_id' => 'general',
				'type'		=> 'textarea',
				'placeholder' => $this->config->get_default_error_msg('v2_invisible'),
				'desc'	=> __( 'In this textbox you can type in a custom error message. Leave it empty to use the default one.' , 'cd-recaptcha'),
				'class'		=> 'hidden regular-text show-field-for-v2_invisible',
			],
			'v3_error_message'      => [
				'label'      => __( 'Error message', 'cd-recaptcha' ),
				'section_id' => 'general',
				'type'		=> 'textarea',
				'placeholder' => $this->config->get_default_error_msg('v3'),
				'desc'	=> __( 'In this textbox you can type in a custom error message. Leave it empty to use the default one.' , 'cd-recaptcha'),
				'class'		=> 'hidden regular-text show-field-for-v3',
			],
			'loggedin_hide'      => [
				'label'      => __( 'Hide for logged in users', 'cd-recaptcha' ),
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
				'desc'        => __( 'The domain to fetch the script from, and to use when verifying requests.<br />Use <samp>recaptcha.net</samp> when <samp>google.com</samp> is not accessible.', 'cd-recaptcha' ),
			],
			'verify_origin'  => [
				'label'      => __( 'Verify origin of solutions', 'cd-recaptcha' ),
				'section_id' => 'general',
				'type'       => 'checkbox',
				'class'      => 'checkbox',
				'desc'		 => __( '<strong>NB!</strong> Only required if you have chosen not to have Google verify the origin of the solutions.', 'cd-recaptcha' ),
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
			'recaptcha_log'     => [
				'label'      => __( 'Enable logging', 'cd-recaptcha' ),
				'section_id' => 'general',
				'type'       => 'checkbox',
				'class'      => 'checkbox',
				'desc'       => sprintf(__( 'Log JSON response data (with the the user\'s IP address is added in).<br />The log file is located in the <code>wp-content</code> directory. It rotates every month. Text format used is %s. <br />Setting both <code>WP_DEBUG</code> and <code>WP_DEBUG_LOG</code> will have the same effect as enabling this setting.', 'cd-recaptcha' ), '<a href="//jsonlines.org" target="_blank">JSON Lines</a>' ),
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
					// 'ms_user_signup' => __( 'Multisite User Signup', 'cd-recaptcha' ),
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
				'desc'		 => sprintf( __( 'Language of the widget. Leave it blank to auto-detect the language. Read more about language codes on %s.', 'cd-recaptcha' ), '<a href="//developers.google.com/recaptcha/docs/language" target="_blank">developers.google.com/recaptcha/docs/language</a>'),
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
				'desc'       => sprintf(__( 'Color theme of the widget. Select <i>%s</i> to have the theme be set based on the brightness of the background color.', 'cd-recaptcha' ), __( 'Automatic', 'cd-recaptcha' )),
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
				'desc'       => sprintf(__( 'Position of the widget. Select <i>%s</i> to place the widget based on text direction (on a page with "right-to-left", the placement will be on the left).', 'cd-recaptcha' ), __( 'Automatic', 'cd-recaptcha' ) ),
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
				'desc'       => sprintf(
							/* translators: 1: Automatic, 2: Compact, 3: Normal */
							__( 'Size of the widget. Select <i>%1$s</i> to automatically set the size to <i>%2$s</i> if the area is too narrow for <i>%3$s</i>.', 'cd-recaptcha' ),
							__( 'Automatic', 'cd-recaptcha' ),
							__( 'Compact', 'cd-recaptcha' ),
							__( 'Normal', 'cd-recaptcha' )
							),
			],
			'v2_checkbox_add_css'  => [
				'label'      => __( 'Add stylesheet (CSS)', 'cd-recaptcha' ),
				'section_id' => 'other',
				'type'       => 'checkbox',
				'class'      => 'checkbox hidden show-field-for-v2_checkbox',
				'cb_label'   => __( "Add this plugin's stylesheet to the login page.", 'cd-recaptcha' ),
				'desc'       => sprintf(__( 'This stylesheet increases the width of the container element that holds the login form. This is to fit in the widget better. Only applicable if you have selected <i>%s</i> or <i>%s</i> as size.', 'cd-recaptcha' ), __('Normal', 'cd-recaptcha'), __('Automatic', 'cd-recaptcha')),
			],
		];

		if ( is_main_site() && is_multisite() ) {
			$fields['enabled_forms']['options']['ms_user_signup'] = __( 'Multisite User Signup', 'cd-recaptcha' );
			
			$fields['action_multisite_signup'] = [
				'label'      => __( 'Multisite User Signup', 'cd-recaptcha' ),
				'section_id' => 'actions',
				'class'      => 'regular hidden show-field-for-v3',
				'placeholder' => $this->config->get_default('action_multisite_signup'),
				'sanitize_callback' => function($value) {
					return $this->sanitize_action_name($value, $this->config->get_default('action_multisite_signup'));
				},
			];
			$fields['threshold_multisite_signup'] = [
				'label'      => __( 'Multisite User Signup', 'cd-recaptcha' ),
				'section_id' => 'thresholds',
				'type'       => 'select',
				'class'      => 'regular hidden show-field-for-v3',
				'std'        => $this->config->get_default('threshold_multisite_signup'),
				'options'    => $score_values,
				'sanitize_callback' => [$this, 'sanitize_threshold_value'],
			];
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
				printf( __( '<strong>%s</strong>: <i>%s</i> is an undefined <strong>&lt;input&gt;</strong> type.', 'cd-recaptcha' ), __( 'Error', 'cd-recaptcha' ), esc_html( $field['type'] ) );
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
		// This regex matches any characters that aren't in the list.
		$name = preg_replace('/[^a-zA-Z0-9_\/]+/', '', $name);

		// Empty value, fallback to default.
		if (empty($name)) {
			$name = $default;
		}

		return $name;
	}

	/**
	 * Sanitizes a v3 threshold value to ensure it's a double.
	 *
	 * @since 1.0.6
	 * @param string $value 
	 *
	 * @return double
	 */
	function sanitize_threshold_value($value) {
		return floatval($value);
	}

	/**
	 * Adds this plugin's options page as a submenu page to the Settings main menu.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function menu_page() {
		add_options_page( sprintf(__('%s Settings', 'cd-recaptcha'), $this->config->get_plugin_name()), $this->config->get_plugin_name(), 'manage_options', $this->menu_slug, [$this, 'admin_settings' ] );
	}

	/**
	 * [Multisite - Network Admin] Adds this plugin's options page as a submenu page to the Settings main menu.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function network_menu_page() {
		add_submenu_page( 'settings.php', sprintf(__('%s Settings', 'cd-recaptcha'), $this->config->get_plugin_name()), $this->config->get_plugin_name(), 'manage_network_options', $this->menu_slug, [ $this, 'admin_settings' ] );
	}

	/**
	 * [Multisite - Network Admin] Save settings
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */

	function network_settings_save() {
		if ( current_user_can( 'manage_options' ) &&
			isset( $_POST[$this->config->get_option_name()] ) &&
			isset( $_POST['action'] ) && $_POST['action'] === 'update' &&
			isset( $_GET['page'] ) && $this->menu_slug === $_GET['page'] ) {

			check_admin_referer( $this->config->get_option_name().'-options' );

			$value = wp_unslash( $_POST[$this->config->get_option_name()] );
			if ( ! is_array( $value ) ) {
				$value = [];
			}
			$this->config->update_option( $value );
			
			add_settings_error( 'general', 'settings_updated', __( 'Settings saved.' ), 'success' );

			set_transient( 'settings_errors', get_settings_errors(), 30 );

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
	 * Adds a link to the settings page on the Plugins page. 
	 * 
	 * See more on:
	 * 
	 * https://developer.wordpress.org/reference/hooks/plugin_action_links_plugin_file/
	 * https://developer.wordpress.org/reference/hooks/network_admin_plugin_action_links_plugin_file/
	 *
	 * @since 1.0.0
	 * @param array $actions 
	 *
	 * @return array
	 */
	function add_settings_link( $actions ) {
		$url = $this->config->get_is_active_for_network() ? network_admin_url( "settings.php?page={$this->menu_slug}" ) : admin_url( "options-general.php?page={$this->menu_slug}" );
		$links = [ '<a href="' . $url . '">' . __( 'Settings') . '</a>'
		];
		$actions = array_merge( $actions, $links );
		return $actions;
	}
}
