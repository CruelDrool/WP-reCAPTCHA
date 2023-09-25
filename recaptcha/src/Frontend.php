<?php
namespace CD\recaptcha;

defined( 'ABSPATH' ) || exit;

use WP_Error;
use WP_User;

/**
 * Outputs reCAPTCHA containers and scripts. Handles verifications of reCAPTCHA response tokens.
 *
 * @package CD_reCAPTCHA
 * @since 1.0.0
 */
class Frontend {

	/**
	 * @since 1.0.0
	 * @var object Plugin options.
	 */
	private $config;

	/**
	 * @since 1.1.0
	 * @var string
	 */
	private $recaptcha_version;

	/**
	 * @since 1.0.0
	 * @var array The forms where reCAPTCHA should be loaded.
	 */
	private $enabled_forms = [];
	
	/**
	 * @since 1.0.0
	 * @var int Number of CAPTCHAs added to a page.
	 */
	private static $captcha_count = 0;
		
	/**
	 * @since 1.0.0
	 * @var string
	 */
	private $recaptcha_action;
	
	/**
	 * @since 1.0.0
	 * @var string 
	 */
	private const API_URL_FORMAT = 'https://www.%s/recaptcha/api%s';

	/**
	 * @since 1.0.0
	 * @var string 
	 */
	private $onload_callback_name;

	/**
	 * @since 1.0.0
	 * @var string 
	 */
	private $captcha_div_class;

	/**
	 * @since 1.0.0
	 * @var string
	 */
	private $error_code;
			
	/**
	 * Constructor
	 * 
	 * @since 1.0.0 
	 * @param object $config
	 * @param Config $plugin_data
	 */
	public function __construct(Config $config) {
		$this->config = $config;
		$this->recaptcha_version = $this->config->get_option('recaptcha_version');
		$this->enabled_forms = $this->config->get_option('enabled_forms');
		$this->onload_callback_name = "{$this->config->get_prefix()}_onloadCallback";
		$this->captcha_div_class = "{$this->config->get_prefix()}_recaptcha_container";
		$this->error_code = "{$this->config->get_prefix()}_error";
		$this->actions_filters();
	}

	/**
	 * Output debug logging to the error log.
	 *
	 * @since 1.1.0
	 * @param int $level 
	 * @param string $message 
	 * @param null|WP_Error $wp_error 
	 * @param bool $force Force the use of error_log() for output.
	 *
	 * @return void
	 */
	private function debug_log($level, $message, $wp_error = null, $force = false) {
		if ( !( $this->config->get_is_active_for_network() || is_main_site() ) ) {
			return;
		}

		if ( !( ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) || $this->config->get_option('debug_log')) ) {
			return;
		}

		$levels = [
			1 => 'error', 
			2 => 'warning',
			3 => 'notice',
			4 => 'info',  
			5 => 'debug', 
		];

		if (!isset($levels[$level])) {
			$level = 1;
			$this->debug_log(2, "Attempted to use an unknown debug level. Defaulted to level {$level} ({$levels[$level]})");
		}
		if ( $level > $this->config->get_option('debug_log_min_level') ) {
			return;
		}

		if ($wp_error instanceof WP_Error && $wp_error->has_errors()) {
			$error_code = !empty($wp_error->get_error_code()) ? sprintf(' Error code: "%s".', $wp_error->get_error_code()) : '';
			$error_message = !empty($wp_error->get_error_message()) ? sprintf(' Error message: "%s"', $wp_error->get_error_message()) : '';
			$message = sprintf('%s.%s%s',
				$message,
				$error_code,
				$error_message
			);
		}


		$output = sprintf('[%s] %s.', $levels[$level], $message);

		if ( $this->config->get_option('debug_log_separate') && !$force ) {

			$dir = WP_CONTENT_DIR;
			if ( !empty($this->config->get_option('log_directory')) ) {
				$dir = $this->config->get_option('log_directory');
			} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG && is_string(WP_DEBUG_LOG) ) {
				$dir = dirname(WP_DEBUG_LOG);
			}

			if ( file_exists($dir) && is_writable($dir) ) {

				$date = $this->config->get_log_rotate_interval('debug');
				$file = sprintf('%s%srecaptcha_debug%s.log',
					$dir,
					DIRECTORY_SEPARATOR,
					!empty($date) ? sprintf('_%s', $date) : ''
				);

				$output = sprintf('%s %s%s', gmdate('[d-M-Y H:i:s \U\T\C]'), $output, PHP_EOL);

				if ( @file_put_contents($file, $output, FILE_APPEND) === false) {
					$this->debug_log(1, sprintf('Failed to writing to: %s%s',
						$file,
						!is_writable($file) ? '. File is not writable' : ''
					), null, true);
					$this->debug_log($level, $message, null, true);
				}

			} else {
				$this->debug_log(1, "Directory doesn't exist or isn't writable: {$dir}", null, true);
				$this->debug_log($level, $message, null, true);
			}
		} else {
			$output = sprintf('[%s plugin] %s', $this->config->get_plugin_name(), $output);
			error_log($output);
		}
	}
	
	/**
	 * Checks if both Site Key and Secret Key are non-empty. Does not check if they are actually valid keys.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	private function is_available() {
		return ( !empty($this->config->get_option($this->recaptcha_version.'_site_key')) && !empty($this->config->get_option($this->recaptcha_version.'_secret_key')) );
	}

	/**
	 * Get the reCAPTCHA API script url.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private function get_api_script_url() {
		$queries = http_build_query(
			[
				'hl'		=> trim( $this->config->get_option( 'language' ) ),
				'onload'	=> $this->onload_callback_name,
				'render'	=> 'explicit'
			],'','&');
		
		
		$url = sprintf('%s?%s',
			sprintf(self::API_URL_FORMAT, $this->config->get_domain(), '.js'),
			$queries
			);
		return $url; 
	}

	/**
	 * Checks if a form is reCAPTCHA-enabled.
	 *
	 * @since 1.0.0
	 * @param string $form 
	 *
	 * @return bool
	 */
	private function is_form_enabled($form = '') {
		
		if ( ! is_array( $this->enabled_forms ) ) {
			return false;
		}
		
		return in_array( $form, $this->enabled_forms, true );
	}

	/**
	 * Registers actions and filters.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function actions_filters() {

		if (! $this->is_available() ) { return; }

		if ( is_user_logged_in() && $this->config->get_option( 'loggedin_hide' ) ) { return; }

		add_action ( 'wp_enqueue_scripts', [$this, 'enqueue_scripts'], 99 );

		if ( $this->is_form_enabled( 'login' ) && ! defined( 'XMLRPC_REQUEST' ) ) {
			add_action( 'login_form', [ $this, 'login_form_field' ], 99 );
			add_filter( 'login_form_middle', [ $this, 'login_form_return' ], 99 );
			add_filter( 'authenticate', [ $this, 'login_verify' ], 999, 3 );
			// add_action( 'wp_login', [ $this, 'clear_login_attempts' ], 10, 2 );
			add_action( 'login_enqueue_scripts', [$this, 'login_enqueue_scripts'] );
		}

		if ( $this->is_form_enabled( 'registration' ) && !is_multisite() ) {
			add_action( 'register_form', [ $this, 'register_form_field' ], 99 );
			add_filter( 'registration_errors', [ $this, 'registration_verify' ], 10, 3 );
		}

		if ( $this->is_form_enabled( 'ms_user_signup' ) && is_multisite() && is_main_site()) {
			if ( is_user_logged_in()) {
				add_action( 'signup_blogform', [ $this, 'ms_form_field' ], 99 );
				add_filter( 'wpmu_validate_blog_signup', [ $this, 'ms_blog_verify' ] );				
			} else {
				add_action( 'signup_extra_fields', [ $this, 'ms_form_field' ], 99 );
				add_filter( 'wpmu_validate_user_signup', [ $this, 'ms_form_field_verify' ] );	
			}
		}

		if ( $this->is_form_enabled( 'lost_password' ) ) {
			add_action( 'lostpassword_form', [ $this, 'lostpassword_form_field' ], 99 );
			add_action( 'lostpassword_post', [ $this, 'lostpassword_verify' ] );
		}

		if ( $this->is_form_enabled( 'reset_password' ) ) {
			add_action( 'resetpass_form', [ $this, 'resetpass_form_field' ], 99 );
			add_filter( 'validate_password_reset', [ $this, 'reset_password_verify' ], 10, 2 );
		}

		if ( $this->is_form_enabled( 'comment' ) && ( ! is_admin() || ! current_user_can( 'moderate_comments' ) ) ) {
			if ( ! is_user_logged_in() ) {
				add_action( 'comment_form_after_fields', [ $this, 'comment_form_field' ], 99 );
			} else {
				add_filter( 'comment_form_field_comment', [ $this, 'comment_form_field_return' ], 99 );
			}
			
			add_filter( 'pre_comment_approved', [ $this, 'comment_verify' ], 99 );
		}
		
		add_action( 'wp_footer', [$this, 'footer_script'], 99999 );
		add_action( 'login_footer', [$this, 'footer_script'], 99999 );
		add_filter( 'shake_error_codes', [$this, 'shake_error_codes']);
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	function enqueue_scripts() {
		// Disable the AJAX JavaScript from the plugin Sidebar Login.
		if ( $this->is_form_enabled( 'login' ) && $this->config->get_option( 'disable_sidebar_login_js' ) && is_plugin_active( 'sidebar-login/sidebar-login.php' ) ) {
			wp_deregister_script('sidebar-login');
			wp_deregister_script('sidebar-login-js-extra');
		}
	}

	/**
	 * Enqueue scripts and styles for the login page.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	function login_enqueue_scripts() {
		if ( $this->recaptcha_version  == 'v2_checkbox' && $this->config->get_option( 'v2_checkbox_add_css' ) && $this->config->get_option( 'v2_checkbox_size' ) != 'compact' ) {
			wp_enqueue_style( $this->config->get_prefix().'-login', plugins_url( '/', $this->config->get_file() ) . 'assets/css/loginform.css', [], $this->config->get_current_version() );
		}
	}

	/**
	 * Determines the user's actual IP address.
	 *
	 * @since 1.1.0
	 *
	 * @return false|string
	 */
	private function get_remote_ip() {
		$client_ip = false;

		// In order of preference, with the best ones for this purpose first.
		$address_headers = [
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		];

		foreach ( $address_headers as $header ) {
			if ( array_key_exists( $header, $_SERVER ) ) {
				/*
				 * HTTP_X_FORWARDED_FOR can contain a chain of comma-separated
				 * addresses. The first one is the original client. It can't be
				 * trusted for authenticity, but we don't need to for this purpose.
				 */
				$address_chain = explode( ',', $_SERVER[ $header ] );
				$client_ip = trim( $address_chain[0] ?? '' );

				break;
			}
		}

		return filter_var($client_ip, FILTER_VALIDATE_IP);
	}

	/**
	 * Verifies a reCAPTCHA response token.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	function verify() {
		$remote_ip = $this->get_remote_ip();
		$response_token = $_POST['g-recaptcha-response'] ?? '';

		// No user response token. Possible when the JavaScript was removed using the browser's developer tools interface.
		if ( empty($response_token) ) {
			$this->debug_log(1, 'Form was submitted without a response token' . ( $remote_ip !== false ? " from {$remote_ip}" : '' ));
			return false;
		}

		if ( $this->config->get_option('require_remote_ip') && $remote_ip === false ) {
			$this->debug_log(1, 'Required by settings to determine the remote IP, but was unable to do so');
			return false;
		}

		$verify_url = sprintf(self::API_URL_FORMAT, $this->config->get_domain(), '/siteverify');

		$post_params = [
			'secret'   => $this->config->get_option($this->recaptcha_version.'_secret_key'),
			'response' => $response_token,
		];

		if ( $remote_ip !== false ) {
			$post_params['remoteip'] = $remote_ip;
		}

		// Make a POST request to the Google reCAPTCHA Server
		$response = wp_remote_post($verify_url,	[ 'timeout' => 10, 'body' => $post_params ]);

		if ( $response instanceof WP_Error ) {
			$this->debug_log(1, 'Connecting to the verification server failed', $response);
			return false;
		}

		if ( !isset($response['body']) ) {
			$this->debug_log(1, 'Expected array key "body" missing in the response data');
			return false;
		}

		$result = json_decode( $response['body'], true );

		if ( !is_array($result) ) {
			$this->debug_log(1, 'The verification server returned invalid/empty JSON data');
			return false;
		}

		$this->recaptcha_log($result, $remote_ip);

		if ( !empty($result['error-codes']) ) {
			$this->debug_log(1, sprintf('The returned JSON data contained error codes: %s', implode(', ', $result['error-codes'])));
			return false;
		}

		if ( !isset( $result['success'] ) ) {
			$this->debug_log(1, 'Expected array key "success" missing in the JSON data');
			return false;
		}

		$is_success = false;
		$debug_message = '';
		$debug_level = 4;
		$hostname_match = $this->config->get_option('verify_origin') ? ($result['hostname'] ?? '') === $_SERVER['SERVER_NAME'] : true;

		if ( $hostname_match )  {		
			if ( $result['success'] == true ) {
				if ( $this->recaptcha_version == 'v3' ) {
					$threshold = $this->config->get_option( 'threshold_'.$this->recaptcha_action );
					$expected_action = $this->config->get_option('action_'.$this->recaptcha_action);

					$score = $result['score'] ?? 0.0;
					$action = $result['action'] ?? '';
					
					$is_success = $score >= $threshold && $action === $expected_action;

					$errors = [];
					if ( $score < $threshold ) {
						$errors[] = 'score was below the threshold';
					}
					if ( $action !== $expected_action ) {
						$errors[] = 'action was not the expected action';
					}
					$errors = ucfirst(implode(', ', $errors));
					$debug_message =
						sprintf('%sAction: "%s"; expected action: "%s". Score: %s; threshold: %.1f',
							!empty($errors) ? "{$errors}. " : '',
							$action,
							$expected_action,
							$score,
							$threshold
						);
				} else { // v2
					$is_success = true;
				}
			} else {
				// Not so interested in this when it's v2
				$debug_message = $this->recaptcha_version == 'v3' ? 'Array key "success" was not equal to true' : '';
			}
		} else {
			// This message can only occur if 'verify_origin' is set to true.
			$debug_level = 3; // Notice. Bump to Warning?
			$debug_message = sprintf('Hostname mismatch. Origin hostname: "%s". Expected: "%s"', $result['hostname'] ?? '', $_SERVER['SERVER_NAME']);
		}

		$this->debug_log($debug_level,
			sprintf('%s verification result: %s%s%s',
				$this->recaptcha_version,
				$is_success ? 'success' : 'no success',
				!empty($debug_message) ? ". {$debug_message}" : '',
				$remote_ip !== false ? ". IP address: {$remote_ip}" : ''
			)
		);

		return $is_success;
	}
	
	/**
	 * Outputs a log in the "JSON Lines" format.
	 *
	 * @since 1.0.6
	 * @since 1.0.7 Removed parameter $version.
	 * @since 1.1.0 $remoteip renamed to $remote_ip and can now be false.
	 * @param array $result 
	 * @param false|string $remote_ip 
	 *
	 * @return void
	 */
	private function recaptcha_log($result, $remote_ip = false){
		if ( !( $this->config->get_is_active_for_network() || is_main_site() ) ) {
			return;
		}

		if ( !( ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) || $this->config->get_option('recaptcha_log')) ) {
			return;
		}

		$dir = WP_CONTENT_DIR;
		if ( !empty($this->config->get_option('log_directory')) ) {
			$dir = $this->config->get_option('log_directory');
		} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG && is_string(WP_DEBUG_LOG) ) {
			$dir = dirname(WP_DEBUG_LOG);
		}

		if ( file_exists($dir) && is_writable($dir)) {
			
			if ($this->config->get_option('recaptcha_log_ip')) {
				$result['remoteip'] = $remote_ip !== false ? $remote_ip : '0.0.0.0';
			}

			$date = $this->config->get_log_rotate_interval('recaptcha');
			$file = sprintf('%s%srecaptcha_%s_log%s.jsonl',
				$dir,
				DIRECTORY_SEPARATOR,
				$this->recaptcha_version,
				!empty($date) ? sprintf('_%s', $date) : ''
			);
			
			$output = sprintf('%s%s',json_encode($result), PHP_EOL);
			if ( @file_put_contents($file, $output, FILE_APPEND) === false) {
				$this->debug_log(1, sprintf('Failed to writing to: %s%s',
					$file,
					!is_writable($file) ? '. File is not writable' : ''
				));
			}

		} else {
			$this->debug_log(1, "Directory doesn't exist or isn't writable: {$dir}");
		}
	}

	/**
	 * Get the error message for when the CAPTCHA wasn't solved.
	 *
	 * @since 1.0.0
	 * @param bool $prepend Optional. Prepend "Error: " to the message
	 *
	 * @return string
	 */
	function get_error_msg($prepend = true) {
		$default_msg = $this->config->get_default_error_msg($this->recaptcha_version);
		$m = $this->config->get_option( $this->recaptcha_version.'_error_message', $default_msg);
		
		if (!$prepend) {return $m;}

		$message = sprintf('<strong>%s</strong>: %s', __( 'Error', 'cd-recaptcha' ), $m);

		return $message;
	}

	/**
	 * Adds a reCAPTCHA container element (<div>) to a form. If the version is "v3", a <input> element will also be added.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	function captcha_form_field() {
		self::$captcha_count++;

		$field = sprintf('<div id="%s_recaptcha_field_%s">%s<div class="%s"></div></div>',
			$this->config->get_prefix(),
			self::$captcha_count,
			// Hidden field so that the v3's grecaptcha.execute() knows what action it is doing for this field.
			$this->recaptcha_version == 'v3' ?  sprintf('<input type="hidden" name="recaptcha_action" value="%s" />', $this->config->get_option('action_'.$this->recaptcha_action)) : '',
			$this->captcha_div_class
		);

		return $field;
	}

	/**
	 * Output the footer script for the selected version.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function footer_script() {

		if ( self::$captcha_count > 0 ) {
			if ( $this->recaptcha_version === 'v2_checkbox' ) {
				$this->v2_checkbox_script();
			} elseif ( $this->recaptcha_version === 'v2_invisible' ) {
				$this->v2_invisible_script();
			} elseif ( $this->recaptcha_version === 'v3' ) {
				$this->v3_script_form_pages();
			}
		} elseif ( $this->recaptcha_version === 'v3' && $this->config->get_option( 'v3_script_load' ) === 'all_pages' ) {
			$this->v3_script_all_pages();
		}
	}

	/**
	 * "v2 Checkbox" footer script.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function v2_checkbox_script() {
		?>
		<script>
			var <?php echo $this->onload_callback_name ?> = function() {<?php
				echo $this->javascript_set_theme(); ?> 

				for ( var i = 0; i < document.forms.length; i++ ) {
					var form = document.forms[i];
					var captcha_div = form.querySelector( '.<?php echo $this->captcha_div_class ?>' );

					if ( captcha_div === null )
						continue;

					captcha_div.innerHTML = '';<?php
					$size = $this->config->get_option( 'v2_checkbox_size' );
					if ($size == 'auto' ) : ?> 
					var size = ( captcha_div.parentNode.offsetWidth < 302 && captcha_div.parentNode.offsetWidth != 0 || document.body.scrollWidth < 302 ) ? 'compact' : 'normal';
					<?php else : ?> 
					var size = '<?php echo esc_js( $size ); ?>';
					<?php endif; ?>

					( function( form ) {
						var widget_id = grecaptcha.render( captcha_div,{
							'sitekey' : '<?php echo esc_js( trim( $this->config->get_option( 'v2_checkbox_site_key' ) ) ); ?>',
							'size'  : size,
							'theme' : theme,
						});
					})(form);
				}
			};
		</script>
		<script src="<?php echo $this->get_api_script_url(); ?>" async defer></script>
		<?php
	}

	/**
	 * "v2 Invisible" footer script.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function v2_invisible_script() {
		?>
		<script>
			var <?php echo $this->onload_callback_name ?> = function() {<?php
				echo $this->javascript_set_theme();

				$badge = $this->config->get_option( 'badge' );
				if ($badge == 'auto') : ?> 
				var badge = document.dir == 'rtl' ? 'bottomleft' : 'bottomright';
				<?php else : ?> 
				var badge = '<?php echo esc_js( $badge ); ?>';
				<?php endif; ?>

				for ( var i = 0; i < document.forms.length; i++ ) {
					var form = document.forms[i];
					var captcha_div = form.querySelector( '.<?php echo $this->captcha_div_class ?>' );

					if ( captcha_div === null )
						continue;

					captcha_div.innerHTML = '';

					( function( form ) {
						var widget_id = grecaptcha.render( captcha_div,{
							'sitekey' : '<?php echo esc_js( trim( $this->config->get_option( 'v2_invisible_site_key' ) ) ); ?>',
							'size'  : 'invisible',
							'theme' : theme,
							'badge' : badge,
							'callback' : function ( token ) {
								HTMLFormElement.prototype.submit.call( form );
							},
						});
						<?php
						// When an error happens, forms from wp-login.php will have a class named "shake" added to it.
						// This class has an animation that shakes the form, but also moves the badge into the form.
						// Going to let it do the shake animation, but then the class gets removed.
						?> 
						if (form.classList.contains('shake')) {
							setTimeout(function(form){ form.classList.remove('shake');}, 600, form);
						}

						form.onsubmit = function( e ){
							e.preventDefault();
							grecaptcha.execute( widget_id );
						};
					})(form);
				}
			};
		</script>
		<script src="<?php echo $this->get_api_script_url(); ?>" async defer></script>
		<?php
	}

	/**
	 * "v3" footer script for form pages.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function v3_script_form_pages() {
		?>
		<script>
			var <?php echo $this->onload_callback_name ?> = function() {<?php
				echo $this->javascript_set_theme();

				$badge = $this->config->get_option( 'badge' );
				if ($badge == 'auto') : ?> 
				var badge = document.dir == 'rtl' ? 'bottomleft' : 'bottomright';
				<?php else : ?> 
				var badge = '<?php echo esc_js( $badge ); ?>';
				<?php endif; ?>

				for ( var i = 0; i < document.forms.length; i++ ) {
					var form = document.forms[i];
					var captcha_div = form.querySelector( '.<?php echo $this->captcha_div_class ?>' );

					if ( captcha_div === null )
						continue;

					captcha_div.innerHTML = '';

					( function( form ) {
						var widget_id = grecaptcha.render( captcha_div,{
							'sitekey' : '<?php echo esc_js( trim( $this->config->get_option( 'v3_site_key' ) ) ); ?>',
							'size'  : 'invisible',
							'theme' : theme,
							'badge' : badge,
							'callback' : function ( token ) {
								HTMLFormElement.prototype.submit.call( form );
							},
						});
						<?php
						// When an error happens, forms from wp-login.php will have a class named "shake" added to it.
						// This class has an animation that shakes the form, but also moves the badge into the form.
						// Going to let it do the shake animation, but then the class gets removed.
						?> 
						if (form.classList.contains('shake')) {
							setTimeout(function(form){ form.classList.remove('shake');}, 600, form);
						}

						form.onsubmit = function( e ){<?php
							// Get value from the hidden field so we know what action we're doing for this particular form.?> 
							var recaptcha_action = form.querySelector("input[name='recaptcha_action']").value;
							e.preventDefault();
							grecaptcha.execute( widget_id, { action: recaptcha_action } );
						};
					})(form);
				}
			};
		</script>
		<script src="<?php echo $this->get_api_script_url(); ?>" async defer></script>
		<?php
	}

	/**
	 * "v3" footer script for all pages.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function v3_script_all_pages() {
		?>
		<div id="<?php echo $this->captcha_div_class ?>"></div>
		<script>
			var <?php echo $this->onload_callback_name ?> = function() {<?php
				echo $this->javascript_set_theme();

				$badge = $this->config->get_option( 'badge' );
				if ($badge == 'auto') : ?> 
				var badge = document.dir == 'rtl' ? 'bottomleft' : 'bottomright';
				<?php else : ?> 
				var badge = '<?php echo esc_js( $badge ); ?>';
				<?php endif; ?>

				var captcha_div = document.getElementById("<?php echo $this->captcha_div_class ?>");
				grecaptcha.render(captcha_div, {
					'sitekey' : '<?php echo esc_js( trim( $this->config->get_option( 'v3_site_key' ) ) ); ?>',
					'size'  : 'invisible',
					'theme' : theme,
					'badge' : badge,
				});
			};
		</script>
		<script src="<?php echo $this->get_api_script_url(); ?>" async defer></script>
		<?php
	}

	/**
	 * Javascript to set theme for the widget.
	 *
	 * @since 1.0.7 Replaces javascript_calculate_lumen()
	 *
	 * @return string Heredoc
	 */
	private function javascript_set_theme() {
		$theme = $this->config->get_option('theme');
		if ($theme == "auto") {
		$output = <<<SCRIPT

				var bgcolor = window.getComputedStyle(document.body).backgroundColor;
				var rgb = bgcolor.match(new RegExp('[+-]?([0-9]*[.])?[0-9]+','g'));
				var r = rgb[0] ? rgb[0] : 255; var g = rgb[1] ? rgb[1] : 255; var b = rgb[2] ? rgb[2] : 255;
				var lum = 0.2126 * r + 0.7152 * g + 0.0722 * b;

				var theme = lum < 127.5 ? 'dark' : 'light';
SCRIPT;
		} else {
			$theme = esc_js( $theme );
			$output = <<<SCRIPT

				var theme = '$theme';
SCRIPT;
		}
		return $output;
	}

	/**
	 * Currently not in use.
	 *
	 *
	 * @return bool
	 */
	/*
	function show_login_captcha() {
		global $wpdb;
	}
	*/

	/**
	 * 
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function form_field() {
		echo $this->form_field_return();
	}

	/**
	 * 
	 *
	 * @since 1.0.0
	 * @param string $return 
	 *
	 * @return string
	 */
	function form_field_return( $return = '' ) {		
		return $return . $this->captcha_form_field();
	}

	/**
	 * Filter hook: Add custom error code to the login form.
	 * 
	 * @link https://developer.wordpress.org/reference/hooks/shake_error_codes/
	 * 
	 * @since 1.0.0
	 * @param array $shake_error_codes 
	 *
	 * @return array
	 */
	function shake_error_codes($shake_error_codes) {
		$shake_error_codes[] = $this->error_code;

		return $shake_error_codes;
	}

	/**
	 * Action hook.
	 * 
	 * @link https://developer.wordpress.org/reference/hooks/login_form/
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function login_form_field() {
		$this->recaptcha_action = 'login';
		$this->form_field();
	}

	/**
	 * Filter hook.
	 * 
	 * @link https://developer.wordpress.org/reference/hooks/login_form_middle/
	 *
	 * @since 1.0.0
	 * @param string $field 
	 *
	 * @return string
	 */
	function login_form_return( $field = '' ) {
		$this->recaptcha_action = 'login';
		$field = $this->form_field_return( $field );

		return $field;
	}
	
	/**
	 * Action hook.
	 * 
	 * @link https://developer.wordpress.org/reference/hooks/register_form/
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function register_form_field() {
		$this->recaptcha_action = 'registration';
		$this->form_field();
	}
	
	/**
	 * Action hook. 
	 * 
	 * @link https://developer.wordpress.org/reference/hooks/signup_extra_fields/
	 * 
	 * @link https://developer.wordpress.org/reference/hooks/signup_blogform/
	 *
	 * @since 1.0.0
	 * @param mixed $errors 
	 *
	 * @return void
	 */
	function ms_form_field( $errors ) {
		if ( $errmsg = $errors->get_error_message( $this->error_code ) ) {
			echo '<p class="error">' . $errmsg . '</p>';
		}
		$this->recaptcha_action = 'multisite_signup';
		$this->form_field();
	}

	/**
	 * Action hook.
	 * 
	 * @link https://developer.wordpress.org/reference/hooks/lostpassword_form/
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function lostpassword_form_field() {
		$this->recaptcha_action = 'lost_password';
		$this->form_field();
	}

	/**
	 * Action hook.
	 * 
	 * @link https://developer.wordpress.org/reference/hooks/resetpass_form/
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function resetpass_form_field() {
		$this->recaptcha_action = 'reset_password';
		$this->form_field();
	}

	/**
	 * Action hook.
	 * 
	 * @link https://developer.wordpress.org/reference/hooks/comment_form_after_fields/
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function comment_form_field() {
		$this->recaptcha_action = 'comment';
		$this->form_field();
	}

	/**
	 * Filter hook.
	 * 
	 * @link https://developer.wordpress.org/reference/hooks/comment_form_field_comment/
	 *
	 * @since 1.0.0
	 * @param string $field 
	 *
	 * @return string
	 */
	function comment_form_field_return($field = '') {
		$this->recaptcha_action = 'comment';
		return $this->form_field_return($field);
	}

	/**
	 * Not currently in use.
	 * 
	 * @link https://developer.wordpress.org/reference/hooks/wp_login/
	 *
	 * @param string $user_login 
	 * @param WP_User $user 
	 *
	 * @return void
	 */
	/*
	function clear_login_attempts( $user_login, $user ) {
		global $wpdb;
	}
	*/

	/**
	 * Filter hook.
	 * 
	 * @link https://developer.wordpress.org/reference/hooks/authenticate/
	 *
	 * @since 1.0.0
	 * @param null|WP_User|WP_Error $user 
	 * @param string $username 
	 * @param string $password 
	 *
	 * @return null|WP_User|WP_Error
	 */
	function login_verify( $user, $username = '', $password = '' ) {
		// Hmm, this filter gets applied just by loading wp-login.php, no submission needed.	
		if ( count($_POST) ) {	
			$this->recaptcha_action = 'login';
			if ( ! $this->verify()) {
				if ($user instanceof WP_Error) {
					// There were errors before us, so let's just add to the pile.
					$user->add($this->error_code, $this->get_error_msg());
				} else {
					// Create a new error.
					$user = new WP_Error( $this->error_code, $this->get_error_msg() );
				}
			}
		}

		return $user;
	}

	/**
	 * Filter hook.
	 * 
	 * @link https://developer.wordpress.org/reference/hooks/registration_errors/
	 *
	 * @since 1.0.0
	 * @param WP_Error $errors 
	 * @param string $sanitized_user_login 
	 * @param string $user_email 
	 *
	 * @return WP_Error
	 */
	function registration_verify( $errors, $sanitized_user_login, $user_email ) {
		$this->recaptcha_action = 'registration';
		if ( ! $this->verify() ) {
			$errors->add( $this->error_code, $this->get_error_msg() );
		}

		return $errors;
	}

	/**
	 * Filter hook.
	 * 
	 * @link https://developer.wordpress.org/reference/hooks/wpmu_validate_user_signup/
	 *
	 * @since 1.0.0
	 * @param array $result 
	 *
	 * @return array
	 */
	function ms_form_field_verify( $result ) {
		// Only verify guests during the "validate user signup" stage because we don't load a CAPTCHA during the "validate blog signup" stage.
		if ( isset( $_POST['stage'] ) && $_POST['stage'] === 'validate-user-signup' ) {
			$this->recaptcha_action = 'multisite_signup';
			if ( ! $this->verify() ) {
				$result['errors']->add( $this->error_code, $this->get_error_msg(false) );
			}
		}
		
		return $result;
	}

	/**
	 * Filter hook.
	 * 
	 * @link https://developer.wordpress.org/reference/hooks/wpmu_validate_blog_signup/
	 *
	 * @since 1.0.0
	 * @param array $result 
	 *
	 * @return array
	 */
	function ms_blog_verify( $result ) {
		$this->recaptcha_action = 'multisite_signup';
		if ( ! $this->verify() ) {
			$result['errors']->add( $this->error_code, $this->get_error_msg(false) );
		}

		return $result;
	}

	/**
	 * Action hook.
	 * 
	 * @link https://developer.wordpress.org/reference/hooks/lostpassword_post/
	 *
	 * @since 1.0.0
	 * @param WP_Error $errors 
	 *
	 * @return void
	 */
	function lostpassword_verify( $errors ) {
		$this->recaptcha_action = 'lost_password';
		if ( ! $this->verify() ) {
			$errors->add( $this->error_code, $this->get_error_msg() );
		}
	}

	/**
	 * Action hook.
	 * 
	 * @link https://developer.wordpress.org/reference/hooks/validate_password_reset/
	 *
	 * @since 1.0.0
	 * @param WP_Error $errors 
	 * @param WP_User|WP_Error $user 
	 *
	 * @return void
	 */
	function reset_password_verify( $errors, $user ) {	
		if ( count($_POST) ) {
			$this->recaptcha_action = 'reset_password';
			if ( ! $this->verify() ) {
				$errors->add( $this->error_code, $this->get_error_msg() );
			}
		}
	}

	/**
	 * Filter hook.
	 * 
	 * @link https://developer.wordpress.org/reference/hooks/pre_comment_approved/
	 *
	 * @since 1.0.0
	 * @param int|string|WP_Error $approved 
	 *
	 * @return int|string|WP_Error
	 */
	function comment_verify( $approved ) {
		$this->recaptcha_action = 'comment';
		if ( ! $this->verify() ) {
			return new WP_Error( $this->error_code, $this->get_error_msg(), 403 );
		}
		return $approved;
	}
}
