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
	 * @since 1.1.2
	 * @var string
	 */
	private $page_slug;

	/**
	 * @since 1.1.2
	 * @var string
	 */
	private $page;

	/**
	 * @since 1.1.2
	 * @var string
	 */
	private $option_group;

	/**
	 * @since 1.1.2
	 * @var string
	 */
	private $option_name;

	/**
	 * @since 1.1.2
	 * @var string
	 */
	private $settings_error_slug;

	/**
	 * @since 1.1.2
	 * @var string
	 */
	private $form_field;

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
		
		// All of these can have unique values. However, I'm a bit lazy.
		$this->page_slug = $this->config->get_prefix().'-settings';
		$this->page = $this->page_slug;
		$this->option_group = $this->page_slug;
		$this->option_name = $this->page_slug;
		$this->settings_error_slug = $this->page_slug;
		$this->form_field = $this->page_slug;

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
		add_action( $this->config->get_is_active_for_network() ? 'network_admin_menu' : 'admin_menu', [ $this, 'add_submenu_page' ] );
		add_action( 'admin_init', [ $this, 'admin_init' ] );
		add_action( 'admin_init', [ $this, 'settings_save' ], 99 );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
		add_action( 'plugin_row_meta', [ $this, 'add_meta_links' ], 10, 3 );
	}

	/**
	 * 
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function admin_init() {

		register_setting( $this->option_group, $this->option_name, ['sanitize_callback' => [$this, 'options_sanitize']] );
		foreach ( $this->get_sections() as $section_id => $section ) {
			add_settings_section( $section_id, $section['section_title'], $section['section_callback'] ?? null, $this->page );
		}
		foreach ( $this->fields as $field_id => $field ) {
			add_settings_field( $field['id'], $field['label'], $field['callback'] ?? [$this, 'callback'], $this->page, $field['section_id'], $field );
		}
	}

	/**
	 * Enqueues admin scripts and styles.
	 *
	 * @since 1.1.0
	 * @param string $hook_suffix 
	 *
	 * @return void
	 */
	function admin_enqueue_scripts($hook_suffix) {
		// Ensure it only outputs on our own settings page.
		if ( $hook_suffix == "settings_page_{$this->page_slug}" ) {
			wp_enqueue_style( $this->page_slug, plugins_url( '/assets/css/settings.css', $this->config->get_file() ), [], $this->config->get_current_version() );
		}
	}

	/**
	 * Adds this plugin's settings page as a submenu page to the Settings main menu.
	 *
	 * @since 1.1.0 Consolidation of menu_page() and network_menu_page()
	 *
	 * @return void
	 */
	function add_submenu_page() {
		$parent_slug = $this->config->get_is_active_for_network() ? 'settings.php' : 'options-general.php';
		$capability = $this->config->get_is_active_for_network() ? 'manage_network_options' : 'manage_options';
		add_submenu_page( $parent_slug, sprintf(__('%s Settings', 'cd-recaptcha'), $this->config->get_plugin_name()), $this->config->get_plugin_name(), $capability, $this->page_slug, [$this, 'admin_settings' ] );
	}

	/**
	 * Add custom links to the meta row.
	 *
	 * @since 1.1.0
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
				// Currently, "View details" is only visible to those that can install plugins. Those that can't, they have the "Visit plugin site".
				$plugin_meta[2] = sprintf('<a href="%s" aria-label="%s">%s</a>',
					$plugin_data['PluginURI'],
					sprintf( translate( 'Visit plugin site for %s' ), $plugin_data['Name'] ),
					translate( 'Visit plugin site' )
				);
			}
			
			$url = '';
			if ( $this->config->get_is_active_for_network() && current_user_can('manage_network_options')) {
				$url = network_admin_url( "settings.php?page={$this->page_slug}" );
			} elseif ( current_user_can('manage_options') ) {
				$url = admin_url( "options-general.php?page={$this->page_slug}" );
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
	 * Save settings
	 *
	 * @since 1.1.2 Rename of network_settings_save()
	 *
	 * @return void
	 */
	 function settings_save() {
		if (!empty($_POST) &&
			current_user_can( $this->config->get_is_active_for_network() ? 'manage_network_options' : 'manage_options' ) &&
			isset( $_POST[$this->form_field] ) &&
			( $_POST['action'] ?? '' ) === 'update' &&
			( $_GET['page'] ?? ''  ) === $this->page_slug) {

			// Here $action param is determined by settings_fields(), which sets the nonce field.
			check_admin_referer( "{$this->option_group}-options" );

			$value = wp_unslash( $_POST[$this->form_field] );
			if ( ! is_array( $value ) ) {
				$value = [];
			}

			$value = sanitize_option( $this->option_name, $value );
			$this->config->update_option( $value );

			add_settings_error( $this->settings_error_slug, 'settings_updated', translate( 'Settings saved.' ), 'success' );
			set_transient( 'settings_errors', get_settings_errors($this->settings_error_slug), 30 );

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
				var select_field = $('#<?= $this->form_field ?>_recaptcha_version');
				function show_hide_fields(){
					$( 'form .hidden' ).removeAttr('style');
					$( 'form .show-field-for-'+ select_field.val() ).show();
				}

				if( select_field.length ){
					show_hide_fields();
				}
				
				select_field.on('change', show_hide_fields);

				function togglePassword() {
					var button = $(this);
					var status = button.attr('data-toggle');
					var input = button.parent().children('input').first();
					var icon = button.children('span');
					
					if ( parseInt( status, 10 ) === 0  ) {
						button.attr( 'data-toggle', 1 );
						button.attr( 'aria-label', '<?= translate( 'Hide password' ) ?>' );
						input.attr('type', 'text');
						icon.removeClass( 'dashicons-visibility' );
						icon.addClass( 'dashicons-hidden' );
					} else {
						button.attr( 'data-toggle', 0);
						button.attr('aria-label', '<?= translate( 'Show password' ) ?>' );
						input.attr( 'type', 'password' );
						icon.removeClass( 'dashicons-hidden' );
						icon.addClass( 'dashicons-visibility' );
					}
				}

				$('.wp-pwd button.wp-hide-pw').each(function(index, button) {
					$(button).removeClass('hide-if-no-js');
					$(button).on('click', togglePassword);
				});
			});
		</script>
		<div class="wrap">
			<h1><?php printf(__('%s Settings', 'cd-recaptcha'), $this->config->get_plugin_name()) ?></h1>
			<?php
			if ($this->config->get_is_active_for_network()) {
				settings_errors();
			}
			?>
			<form method="post" action="">
				<?php
				settings_fields( $this->option_group );
				do_settings_sections( $this->page );
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
			// 'other'       => [
			// 	'section_title' => __( 'Other', 'cd-recaptcha' ),
			// ],
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
				'std'        => $this->config->get_default('recaptcha_version'),
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
				'label'       => __( 'Site Key', 'cd-recaptcha' ),
				'section_id'  => 'general',
				'class'		  => 'hidden show-field-for-v2_checkbox',
				'field_class' => 'regular-text',
				'desc'		=> sprintf( __( 'The public site key is used to load the widget. %s.', 'cd-recaptcha' ), 
								sprintf( '<a href="https://www.google.com/recaptcha" target="_blank">%s</a>', 
									__( 'Keys can be obtained here', 'cd-recaptcha' )
								)
							),
			],
			'v2_checkbox_secret_key'         => [
				'label'       => __( 'Secret Key', 'cd-recaptcha' ),
				'section_id'  => 'general',
				'type'        => 'password',
				'class'		  => 'hidden show-field-for-v2_checkbox',
				'field_class' => 'regular-text',
				'desc'		=> sprintf( __( 'The private secret key is for communication between your site and the reCAPTCHA verification server. %s.', 'cd-recaptcha' ), 
								sprintf( '<a href="https://www.google.com/recaptcha" target="_blank">%s</a>', 
									__( 'Keys can be obtained here', 'cd-recaptcha' )
								)
							),
			],
			'v2_invisible_site_key'           => [
				'label'       => __( 'Site Key', 'cd-recaptcha' ),
				'section_id'  => 'general',
				'class'		  => 'hidden show-field-for-v2_invisible',
				'field_class' => 'regular-text',
				'desc'		=> sprintf( __( 'The public site key is used to load the widget. %s.', 'cd-recaptcha' ), 
								sprintf( '<a href="https://www.google.com/recaptcha" target="_blank">%s</a>', 
									__( 'Keys can be obtained here', 'cd-recaptcha' )
								)
							),
			],
			'v2_invisible_secret_key'         => [
				'label'       => __( 'Secret Key', 'cd-recaptcha' ),
				'section_id'  => 'general',
				'type'        => 'password',
				'class'		  => 'hidden show-field-for-v2_invisible',
				'field_class' => 'regular-text',
				'desc'		=> sprintf( __( 'The private secret key is for communication between your site and the reCAPTCHA verification server. %s.', 'cd-recaptcha' ), 
								sprintf( '<a href="https://www.google.com/recaptcha" target="_blank">%s</a>', 
									__( 'Keys can be obtained here', 'cd-recaptcha' )
								)
							),
			],
			'v3_site_key'           => [
				'label'       => __( 'Site Key', 'cd-recaptcha' ),
				'section_id'  => 'general',
				'class'		  => 'hidden show-field-for-v3',
				'field_class' => 'regular-text',
				'desc'		=> sprintf( __( 'The public site key is used to load the widget. %s.', 'cd-recaptcha' ), 
								sprintf( '<a href="https://www.google.com/recaptcha" target="_blank">%s</a>', 
									__( 'Keys can be obtained here', 'cd-recaptcha' )
								)
							),
			],
			'v3_secret_key'         => [
				'label'       => __( 'Secret Key', 'cd-recaptcha' ),
				'section_id'  => 'general',
				'type'        => 'password',
				'class'		  => 'hidden show-field-for-v3',
				'field_class' => 'regular-text',
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
				'class'		=> 'hidden show-field-for-v2_checkbox',
				'field_class' => 'regular-text',
			],
			'v2_invisible_error_message'=> [
				'label'      => __( 'Error message', 'cd-recaptcha' ),
				'section_id' => 'general',
				'type'		=> 'textarea',
				'placeholder' => $this->config->get_default_error_msg('v2_invisible'),
				'desc'	=> __( 'In this textbox, you can type in a custom error message. Leave it empty to use the default one.' , 'cd-recaptcha'),
				'class'		=> 'hidden show-field-for-v2_invisible',
				'field_class' => 'regular-text',
			],
			'v3_error_message'      => [
				'label'      => __( 'Error message', 'cd-recaptcha' ),
				'section_id' => 'general',
				'type'		=> 'textarea',
				'placeholder' => $this->config->get_default_error_msg('v3'),
				'desc'	=> __( 'In this textbox, you can type in a custom error message. Leave it empty to use the default one.' , 'cd-recaptcha'),
				'class'		=> 'hidden show-field-for-v3',
				'field_class' => 'regular-text',
			],
			'loggedin_hide'      => [
				'label'      => __( 'Hide for logged-in users', 'cd-recaptcha' ),
				'section_id' => 'general',
				'type'       => 'checkbox',
				'cb_label'		 => __( 'Only load for guest users.', 'cd-recaptcha'),
			],
			'recaptcha_domain'      =>[
				'label'      => __( 'Request domain', 'cd-recaptcha' ),
				'section_id' => 'general',
				'type'       => 'select',
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
				'desc'		 => sprintf( __( '%s This is only required if you have chosen not to have Google do this verification.', 'cd-recaptcha' ), 
									sprintf( '<strong>%s</strong>', __( 'NB!', 'cd-recaptcha' ) )
								),
			],
			'require_remote_ip'     => [
				'label'      => __( 'Require the client\'s IP address', 'cd-recaptcha' ),
				'section_id' => 'general',
				'type'       => 'checkbox',
				'desc'       => __( 'Require that the client\'s IP address has been determined before submitting data to the reCAPTCHA server. An undetermined IP address will be treated as a failed CAPTCHA attempt.', 'cd-recaptcha' ),
			],
			'language'           => [
				'label'      => __( 'Language code', 'cd-recaptcha' ),
				'section_id' => 'general',
				'field_class'      => 'small-text',
				'desc'		 => sprintf( __( 'Language of the widget. Leave it blank to auto-detect the language. %s.', 'cd-recaptcha' ),
									sprintf('<a href="https://developers.google.com/recaptcha/docs/language" target="_blank">%s</a>',
										__('Read more about language codes', 'cd-recaptcha' )
									)
								),
			],
			'theme'              => [
				'label'      => __( 'Theme', 'cd-recaptcha' ),
				'section_id' => 'general',
				'type'       => 'select',
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
			'v2_checkbox_size' => [
				'label'      => __( 'Size', 'cd-recaptcha' ),
				'section_id' => 'general',
				'type'       => 'select',
				'class'      => 'hidden show-field-for-v2_checkbox',
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
				'section_id' => 'general',
				'type'       => 'checkbox',
				'class'      => 'hidden show-field-for-v2_checkbox',
				'cb_label'   => __( "Add this plugin's stylesheet to the login page.", 'cd-recaptcha' ),
								/* translators: 1: Normal, 2: Automatic */
				'desc'       => sprintf(__( 'This stylesheet increases the width of the container element that holds the login form. This is to fit in the widget better. Only applicable if you have selected %1$s or %2$s as size.', 'cd-recaptcha' ),
									sprintf( '<i>%s</i>', __( 'Normal', 'cd-recaptcha' ) ),
									sprintf( '<i>%s</i>', __( 'Automatic', 'cd-recaptcha' ) )
								),
			],
			'badge'              => [
				'label'      => __( 'Placement', 'cd-recaptcha' ),
				'section_id' => 'general',
				'type'       => 'select',
				'class'      => 'hidden show-field-for-v2_invisible show-field-for-v3',
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
			'v3_load_all_pages'     => [
				'label'      => __( 'Load on all pages', 'cd-recaptcha' ),
				'section_id' => 'general',
				'type'       => 'checkbox',
				'class'      => 'hidden show-field-for-v3',
				'desc'       => __( 'For analytics purposes, it\'s recommended to load the widget in the background of all pages.', 'cd-recaptcha' ),
			],
			// Forms
			'enabled_forms'      => [
				'label'      => __( 'Enabled forms', 'cd-recaptcha' ),
				'section_id' => 'forms',
				'type'       => 'multicheck',
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
				'class'      => 'hidden show-field-for-v3',
				'placeholder' => $this->config->get_default('action_login'),
				'sanitize_callback' => function($value) {
					return $this->sanitize_action_name($value, $this->config->get_default('action_login'));
				},
			],
			'action_registration'   => [
				'label'      => __( 'Registration', 'cd-recaptcha' ),
				'section_id' => 'actions',
				'class'      => 'hidden show-field-for-v3',
				'placeholder' => $this->config->get_default('action_registration'),
				'sanitize_callback' => function($value) {
					return $this->sanitize_action_name($value, $this->config->get_default('action_registration'));
				},
			],
			'action_ms_user_signup' => [
				'label'      => __( 'Multisite User Signup', 'cd-recaptcha' ),
				'section_id' => 'actions',
				'class'      => 'hidden show-field-for-v3',
				'placeholder' => $this->config->get_default('action_ms_user_signup'),
				'sanitize_callback' => function($value) {
					return $this->sanitize_action_name($value, $this->config->get_default('action_ms_user_signup'));
				},
			],
			'action_lost_password'=> [
				'label'      => __( 'Lost Password', 'cd-recaptcha' ),
				'section_id' => 'actions',
				'class'      => 'hidden show-field-for-v3',
				'placeholder' => $this->config->get_default('action_lost_password'),
				'sanitize_callback' => function($value) {
					return $this->sanitize_action_name($value, $this->config->get_default('action_lost_password'));
				},
			],
			'action_reset_password'=> [
				'label'      => __( 'Reset Password', 'cd-recaptcha' ),
				'section_id' => 'actions',
				'class'      => 'hidden show-field-for-v3',
				'placeholder' => $this->config->get_default('action_reset_password'),
				'sanitize_callback' => function($value) {
					return $this->sanitize_action_name($value, $this->config->get_default('action_reset_password'));
				},
			],
			'action_comment'=> [
				'label'      => __( 'Comment', 'cd-recaptcha' ),
				'section_id' => 'actions',
				'class'      => 'hidden show-field-for-v3',
				'placeholder' => $this->config->get_default('action_comment'),
				'sanitize_callback' => function($value) {
					return $this->sanitize_action_name($value, $this->config->get_default('action_comment'));
				},
			],
			// Thresholds
			'threshold_login'=> [
				'label'      => __( 'Login', 'cd-recaptcha' ),
				'section_id' => 'thresholds',
				'type'       => 'number',
				'class'      => 'hidden show-field-for-v3',
				'field_class'=> 'small-text',
				'min'        => 0.0,
				'max'        => 1.0,
				'step'       => 0.1,
				'sanitize_callback' => [$this, 'sanitize_threshold_value'],
			],
			'threshold_registration'=> [
				'label'      => __( 'Registration', 'cd-recaptcha' ),
				'section_id' => 'thresholds',
				'type'       => 'number',
				'class'      => 'hidden show-field-for-v3',
				'field_class'=> 'small-text',
				'min'        => 0.0,
				'max'        => 1.0,
				'step'       => 0.1,
				'sanitize_callback' => [$this, 'sanitize_threshold_value'],
			],
			'threshold_ms_user_signup' => [
				'label'      => __( 'Multisite User Signup', 'cd-recaptcha' ),
				'section_id' => 'thresholds',
				'type'       => 'number',
				'class'      => 'hidden show-field-for-v3',
				'field_class'=> 'small-text',
				'min'        => 0.0,
				'max'        => 1.0,
				'step'       => 0.1,
				'sanitize_callback' => [$this, 'sanitize_threshold_value'],
			],
			'threshold_lost_password'=> [
				'label'      => __( 'Lost Password', 'cd-recaptcha' ),
				'section_id' => 'thresholds',
				'type'       => 'number',
				'class'      => 'hidden show-field-for-v3',
				'field_class'=> 'small-text',
				'min'        => 0.0,
				'max'        => 1.0,
				'step'       => 0.1,
				'sanitize_callback' => [$this, 'sanitize_threshold_value'],
			],
			'threshold_reset_password'=> [
				'label'      => __( 'Reset Password', 'cd-recaptcha' ),
				'section_id' => 'thresholds',
				'type'       => 'number',
				'class'      => 'hidden show-field-for-v3',
				'field_class'=> 'small-text',
				'min'        => 0.0,
				'max'        => 1.0,
				'step'       => 0.1,
				'sanitize_callback' => [$this, 'sanitize_threshold_value'],
			],
			'threshold_comment'=> [
				'label'      => __( 'Comment', 'cd-recaptcha' ),
				'section_id' => 'thresholds',
				'type'       => 'number',
				'class'      => 'hidden show-field-for-v3',
				'field_class'=> 'small-text',
				'min'        => 0.0,
				'max'        => 1.0,
				'step'       => 0.1,
				'sanitize_callback' => [$this, 'sanitize_threshold_value'],
			],
		];

		if ( file_exists(WP_PLUGIN_DIR . '/sidebar-login') ) {
			$fields['disable_sidebar_login_js'] = [
				'label'      => sprintf(__( 'Disable the AJAX JavaScript from the plugin %s', 'cd-recaptcha' ),
									sprintf('<a href="%ssidebar-login" target="_blank">%s</a>',
										translate('https://wordpress.org/plugins/'),
										__('Sidebar Login', 'cd-recaptcha')
									)
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
			unset($fields['action_ms_user_signup']);
			unset($fields['threshold_ms_user_signup']);
		}

		if ( is_main_site()  ) {
			$log_fields = [
				'recaptcha_log'  => [ 
					'label'      => __( 'Enable logging of reCAPTCHA\'s JSON response data', 'cd-recaptcha' ),
					'section_id' => 'logging',
					'type'       => 'checkbox',
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
				],
				'recaptcha_log_rotate_interval'  => [ 
					'label'      => __( 'reCAPTCHA log\'s rotate interval', 'cd-recaptcha' ),
					'section_id' => 'logging',
					'type'       => 'radio',
					'std'        => $this->config->get_default('recaptcha_log_rotate_interval'),
					'options'    => [
						'never'   => __( 'Never', 'cd-recaptcha' ),
						'daily'   => sprintf( '<span class="date-time-text">%s</span><code>%s</code>', __( 'Daily', 'cd-recaptcha' ), gmdate($this->config->get_date_format('daily'))),
						'weekly'  => sprintf( '<span class="date-time-text">%s</span><code>%s</code>', __( 'Weekly', 'cd-recaptcha' ), gmdate($this->config->get_date_format('weekly'))),
						'monthly' => sprintf( '<span class="date-time-text">%s</span><code>%s</code>', __( 'Monthly', 'cd-recaptcha' ), gmdate($this->config->get_date_format('monthly'))),
						'yearly'  => sprintf( '<span class="date-time-text">%s</span><code>%s</code>', __( 'Yearly', 'cd-recaptcha' ), gmdate($this->config->get_date_format('yearly'))),
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
					'type'       => 'radio',
					'std'        => $this->config->get_default('debug_log_rotate_interval'),
					'options'    => [
						'never'   => __( 'Never', 'cd-recaptcha' ),
						'daily'   => sprintf( '<span class="date-time-text">%s</span><code>%s</code>', __( 'Daily', 'cd-recaptcha' ), gmdate($this->config->get_date_format('daily'))),
						'weekly'  => sprintf( '<span class="date-time-text">%s</span><code>%s</code>', __( 'Weekly', 'cd-recaptcha' ), gmdate($this->config->get_date_format('weekly'))),
						'monthly' => sprintf( '<span class="date-time-text">%s</span><code>%s</code>', __( 'Monthly', 'cd-recaptcha' ), gmdate($this->config->get_date_format('monthly'))),
						'yearly'  => sprintf( '<span class="date-time-text">%s</span><code>%s</code>', __( 'Yearly', 'cd-recaptcha' ), gmdate($this->config->get_date_format('yearly'))),
					],
					'desc'       => sprintf('%s %s',
										__( 'Only applicable if you have chosen to have a separate debug log.', 'cd-recaptcha' ),
										sprintf(__( 'Uses UTC/GMT time with a %s date format.', 'cd-recaptcha' ),
											sprintf('<a href="https://www.iso.org/standard/40874.html" target="_blank">%s</a>',
											__( 'ISO 8601' , 'cd-recaptcha'))
										)
									),
				],
				'debug_log_min_level'  => [ 
					'label'      => __( 'Debug log\'s minimum level', 'cd-recaptcha' ),
					'section_id' => 'logging',
					'type'       => 'select',
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
					'field_class'=> 'regular-text',
					'desc'       => sprintf('%s %s</p><p class="description">%s</p><p class="description">%s',
									__( 'Specify your own directory where the log files will be stored.', 'cd-recaptcha' ),
									__( 'Using an absolute path is recommended.', 'cd-recaptcha'),
									__( 'If you are logging to a directory that is web accessible, then you should take measures to prevent people from accessing the logs.', 'cd-recaptcha' ),
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
					'class'      => '',
					'section_id' => '',
					'desc'       => '',
					'std'        => '',
				]
			);
		}
		
		return $fields;
	}

	/**
	 * Create HTML form fields
	 *
	 * @since 1.0.0
	 * @param mixed $array 
	 *
	 * @return void
	 */
	function callback( $field ) {
		$attrib = '';
		if ( ($field['required'] ?? false) === true ) {
			$attrib .= ' required="required"';
		}
		if ( ($field['readonly'] ?? false) === true ) {
			$attrib .= ' readonly="readonly"';
		}
		if ( ($field['disabled'] ?? false) === true ) {
			$attrib .= ' disabled="disabled"';
		}

		// $value = $this->config->get_option( $field['id'], $field['std'] );
		$value = $this->config->get_option( $field['id'] );
		// $default = $this->config->get_default($field['id']);

		switch ( $field['type'] ) {
			case 'password':
			case 'text':
			case 'email':
			case 'url':
				printf( '%11$s<input type="%1$s" id="%4$s_%2$s" name="%4$s[%2$s]" value="%3$s"%5$s%6$s%7$s%8$s%9$s%10$s autocomplete="off"/>%12$s',
					esc_attr( $field['type'] ), // 1
					esc_attr( $field['id'] ), // 2
					esc_attr( $value ), // 3
					$this->form_field, // 4
					isset( $field['field_class'] ) ? sprintf(' class="%s"', esc_attr( $field['field_class'] ) ) : '', // 5
					isset( $field['placeholder'] ) ? sprintf(' placeholder="%s"', esc_attr( $field['placeholder'] ) ) : '', // 6
					isset( $field['minlength']) ? sprintf(' minlength="%s"', $field['minlength'] ) : '', // 7
					isset( $field['maxlength']) ? sprintf(' maxlength="%s"', $field['maxlength'] ) : '', // 8
					isset( $field['pattern']) ? sprintf(' pattern="%s"', $field['pattern'] ) : '', // 9
					$attrib, // 10
					$field['type'] == "password" ? '<div class="wp-pwd">' : '', // 11
					$field['type'] == "password" ? sprintf('<button type="button" class="button wp-hide-pw" data-toggle="0" aria-label="%s"><span class="dashicons dashicons-visibility" aria-hidden="true"></span></button></div>', translate('Show password')) : '' // 12
				);
				break;
			case 'textarea':
				printf( '<textarea id="%3$s_%1$s" name="%3$s[%1$s]"%4$s%5$s%6$s%7$s%8$s rows="5" cols="50">%2$s</textarea>',
					esc_attr( $field['id'] ), // 1
					esc_textarea( $value ), // 2
					$this->form_field, // 3
					isset( $field['field_class'] ) ? sprintf(' class="%s"', esc_attr( $field['field_class'] ) ) : '', // 4
					isset( $field['placeholder'] ) ? sprintf(' placeholder="%s"', esc_attr( $field['placeholder'] ) ) : '', // 5
					isset( $field['minlength']) ? sprintf(' minlength="%s"', $field['minlength'] ) : '', // 6
					isset( $field['maxlength']) ? sprintf(' maxlength="%s"', $field['maxlength'] ) : '', // 7
					$attrib // 8
				);
				break;
			case 'checkbox':
				printf( '<input type="hidden" name="%2$s[%1$s]" value="0" /><label><input type="checkbox" id="%2$s_%1$s" name="%2$s[%1$s]" value="1"%3$s %4$s/>%5$s</label>',
					esc_attr( $field['id'] ), // 1
					$this->form_field, // 2
					isset( $field['field_class'] ) ? sprintf(' class="%s"', esc_attr( $field['field_class'] ) ) : '', // 3
					checked( $value, true, false ), // 4
					isset( $field['cb_label'] ) ? sprintf(' %s', esc_attr( $field['cb_label'] )) : '' // 5
				);
				break;
			case 'multicheck':
				$field_options = '';
				if ( isset($field['options']) ) {
					foreach ( $field['options'] as $key => $label ) {
						$field_options = sprintf( '%8$s<label><input type="checkbox" id="%3$s_%1$s_%2$s" name="%3$s[%1$s][]" value="%2$s"%4$s %5$s/> %6$s</label>%7$s',
							esc_attr( $field['id'] ), // 1
							esc_attr( $key ), // 2
							$this->form_field, // 3
							isset( $field['field_class'] ) ? sprintf(' class="%s"', esc_attr( $field['field_class'] ) ) : '', // 4
							checked( in_array( $key, (array) $value ), true, false ), // 5
							$label, // 6
							( $field['inline'] ?? false ) === true ? '&nbsp;&nbsp;&nbsp;' : '<br>', // 7
							$field_options // 8
						);
					}
				}
				printf( '<input type="hidden" name="%3$s[%1$s][]" value="" /><fieldset>%2$s</fieldset>',
					esc_attr( $field['id'] ), // 1
					$field_options, // 2
					$this->form_field // 3
				);
				break;
			case 'radio':
				$field_options = '';
				if ( isset($field['options']) ) {
					foreach ( $field['options'] as $key => $label ) {
						$field_options = sprintf( '%8$s<label><input type="radio" id="%3$s_%1$s_%2$s" name="%3$s[%1$s]" value="%2$s"%4$s %5$s/> %6$s</label>%7$s',
							esc_attr( $field['id'] ), // 1
							esc_attr( $key ), // 2
							$this->form_field, // 3
							isset( $field['field_class'] ) ? sprintf(' class="%s"', esc_attr( $field['field_class'] ) ) : '', // 4
							checked( in_array( $key, (array) $value ), true, false ), // 5
							$label, // 6
							( $field['inline'] ?? false ) === true ? '&nbsp;&nbsp;&nbsp;' : '<br>', // 7
							$field_options // 8
						);
					}
				}
				printf('<fieldset>%s</fieldset>', $field_options);
				break;
			case 'select':
				$field_options = '';
				if ( isset($field['options']) ) {
					foreach ( $field['options'] as $key => $label ) {
						$field_options = sprintf( '%4$s<option value="%1$s"%2$s>%3$s</option>',
							esc_attr( $key ), // 1
							selected( $value, $key, false ), // 2
							esc_attr( $label ), // 3
							$field_options // 4
						);
					}
				}
				printf( '<select id="%3$s_%1$s" name="%3$s[%1$s]"%4$s>%2$s</select>',
					esc_attr( $field['id'] ), // 1
					$field_options, // 2
					$this->form_field, // 3
					isset( $field['field_class'] ) ? sprintf(' class="%s"', esc_attr( $field['field_class'] ) ) : '' // 4
				);
				break;
			case 'number':
				printf( '<input type="number" id="%3$s_%1$s" name="%3$s[%1$s]" value="%2$s"%4$s%5$s%6$s%7$s%8$s/> ',
					esc_attr( $field['id'] ), // 1
					esc_attr( $value ), // 2
					$this->form_field, // 3
					isset( $field['field_class'] ) ? sprintf(' class="%s"', esc_attr( $field['field_class'] ) ) : '', // 4
					isset( $field['step']) ? sprintf(' step="%s"', $field['step'] ) : '', // 5
					isset( $field['min']) ? sprintf(' min="%s"', $field['min'] ) : '', // 6
					isset( $field['max']) ? sprintf(' max="%s"', $field['max'] ) : '', // 7
					$attrib // 8
				);
				break;
			default:
				printf( '<strong>Error</strong>: <i>%s</i> is an undefined <strong>&lt;input&gt;</strong> type.', esc_html( $field['type'] ) );
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
			case 'password':
			case 'email':
			case 'text':
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
			case 'radio':
			case 'select':
				if ( ! array_key_exists( $value, $field['options'] ) ) {
					$sanitized = $field['std'] ?? '';
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
				$name
			);

			add_settings_error($this->settings_error_slug, __FUNCTION__."-{$default}", $msg, 'info' );
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
	 * @since 1.1.0
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

		$setting_error_code = __FUNCTION__;
		$path = trim($value);

		// Relative directory path, try to resolve it.
		if ( boolval(preg_match('/^[^\x2f\x5c]|[\x2f\x5c]?\.\.[\x2f\x5c]|[\x2f\x5c]\.[\x2f\x5c]|[\x2f\x5c]\.+?\.?$/', $path)) && !boolval(preg_match('/^[A-Za-z]:/', $path)) ) {
			$path = realpath($path);

			if ( $path !== false ) {
				add_settings_error( $this->settings_error_slug, $setting_error_code,
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
			add_settings_error($this->settings_error_slug, $setting_error_code, $error_msg, 'error' );
		}

		return $error ? $default : rtrim($path, "\x2f\x5c");
	}
}
