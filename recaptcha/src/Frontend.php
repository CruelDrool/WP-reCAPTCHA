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
		$this->enabled_forms = $this->config->get_option('enabled_forms');
		$this->onload_callback_name = "{$this->config->get_prefix()}_onloadCallback";
		$this->captcha_div_class = "{$this->config->get_prefix()}_recaptcha_container";
		$this->error_code = "{$this->config->get_prefix()}_error";
		$this->actions_filters();
	}
	
	/**
	 * Checks if both Site Key and Secret Key are non-empty. Does not check if they are actually valid keys.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	private function is_available() {
		$version = $this->config->get_option('recaptcha_version');
		return ( !empty($this->config->get_option($version.'_site_key')) && !empty($this->config->get_option($version.'_secret_key')) );
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

		if ( $this->is_form_enabled( 'login' ) && ! defined( 'XMLRPC_REQUEST' ) ) {
			add_action( 'login_form', [ $this, 'login_form_field' ], 99 );
			add_filter( 'login_form_middle', [ $this, 'login_form_return' ], 99 );
			add_filter( 'authenticate', [ $this, 'login_verify' ], 999, 3 );
			// add_action( 'wp_login', [ $this, 'clear_login_attempts' ], 10, 2 );

			if ( $this->config->get_option( 'v2_checkbox_add_css' ) && $this->config->get_option( 'v2_checkbox_size' ) != 'compact' && $this->config->get_option( 'recaptcha_version' )  == 'v2_checkbox' ) {
				wp_enqueue_style( $this->config->get_prefix().'-login-style', plugins_url( '/', $this->config->get_file() ) . 'assets/css/loginform.css', [], $this->config->get_current_version() );
			}
		}

		if ( $this->is_form_enabled( 'registration' ) ) {
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
	 * Verifies a reCAPTCHA response token.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	function verify() {
		$version = $this->config->get_option('recaptcha_version');
		$secret_key  = $this->config->get_option($version.'_secret_key');
		
		$remoteip = $_SERVER['REMOTE_ADDR'];
		$response_token = $_POST['g-recaptcha-response'] ?? '';

		if ( empty($response_token) || empty($remoteip) ) {
			return false;
		}
				
		$verify_url = sprintf(self::API_URL_FORMAT, $this->config->get_domain(), '/siteverify');

		// Make a POST request to the Google reCAPTCHA Server
		$request = wp_remote_post(
			$verify_url, [
				'timeout' => 10,
				'body'    => [
					'secret'   => $secret_key,
					'response' => $response_token,
					'remoteip' => $remoteip,
				],
			]
		);

		// Get the request response body
		$request_body = wp_remote_retrieve_body( $request );
		if ( ! $request_body ) {
			return false;
		}

		$result = json_decode( $request_body, true );
		
		$this->recaptcha_log($result, $version, $remoteip);

		$is_success = false;
		if ( isset( $result['success'] ) && $result['success'] == true ) {
							
			$hostname_match = $this->config->get_option('verify_origin') ? ($result['hostname'] ?? '') === $_SERVER['SERVER_NAME'] : true;
			
			if ($hostname_match == true) {
				if ( $version == 'v3' ) {
					$threshold = $this->config->get_option( 'threshold_'.$this->recaptcha_action );
					$expected_action = $this->config->get_option('action_'.$this->recaptcha_action);

					$score = $result['score'] ?? 0.0;
					$action = $result['action'] ?? '';
					
					$is_success = $score >= $threshold && $action === $expected_action;
				} else { // v2
					$is_success = true;
				}
			}
		}

		return $is_success;
	}
	
	/**
	 * Outputs a log in the "JSON Lines" format.
	 *
	 * @since 1.0.6
	 * @param array $result 
	 * @param string $version 
	 * @param string $remoteip 
	 *
	 * @return void
	 */
	function recaptcha_log($result, $version, $remoteip){
		if ( !( ( WP_DEBUG && WP_DEBUG_LOG ) || $this->config->get_option('recaptcha_log')) )
			return;

		$file = sprintf('%s/recaptcha_%s_log_%s.jsonl', WP_CONTENT_DIR, $version, date("Y-m"));
		$result['remoteip'] = $remoteip;
		file_put_contents($file, json_encode($result)."\n", FILE_APPEND);
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
		$version = $this->config->get_option( 'recaptcha_version' );
		$default_msg = $this->config->get_default_error_msg($version);
		$m = $this->config->get_option( $version.'_error_message', $default_msg);
		
		if (!$prepend) {return $m;}

		$message = sprintf('<strong>%s</strong>: %s', __( 'Error', 'cd-recaptcha' ), $m);

		return $message;
	}

	/**
	 * Get total number of reCAPTCHAs that have been added to a page.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	function total_captcha() {
		return self::$captcha_count;
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
		$number   = $this->total_captcha();
		$version = $this->config->get_option( 'recaptcha_version' );
		$action = $this->config->get_option('action_'.$this->recaptcha_action);

		// Hidden field so that the v3's grecaptcha.execute() knows what action it is doing for this field.
		$hidden = sprintf('<input type="hidden" name="recaptcha_action" value="%s" />', $action);

		$field = sprintf('<div id="%4$s_recaptcha_field_%2$s">%1$s<div class="%3$s"></div></div>',
			$version == 'v3' ? $hidden : '',
			$number,
			$this->captcha_div_class,
			$this->config->get_prefix()
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

		$number = $this->total_captcha();
		$version = $this->config->get_option( 'recaptcha_version');

		if ( ! $number && ( $version !== 'v3' || $this->config->get_option( 'v3_script_load' ) !== 'all_pages' ) ) {
			return;
		}

		if ( 'v2_checkbox' === $version ) {
			$this->v2_checkbox_script();
		} elseif ( 'v2_invisible' === $version ) {
			$this->v2_invisible_script();
		} elseif ( 'v3' === $version ) {
			if ($number > 0) {
				$this->v3_script_form_pages();
			} else {
				$this->v3_script_all_pages();
			}

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
	 * https://developer.wordpress.org/reference/hooks/shake_error_codes/
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
	 * https://developer.wordpress.org/reference/hooks/login_form/
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
	 * https://developer.wordpress.org/reference/hooks/login_form_middle/
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
	 * https://developer.wordpress.org/reference/hooks/register_form/
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
	 * https://developer.wordpress.org/reference/hooks/signup_extra_fields/
	 * 
	 * https://developer.wordpress.org/reference/hooks/signup_blogform/
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
	 * https://developer.wordpress.org/reference/hooks/lostpassword_form/
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
	 * https://developer.wordpress.org/reference/hooks/resetpass_form/
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
	 * https://developer.wordpress.org/reference/hooks/comment_form_after_fields/
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
	 * https://developer.wordpress.org/reference/hooks/comment_form_field_comment/
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
	 * https://developer.wordpress.org/reference/hooks/wp_login/
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
	 * https://developer.wordpress.org/reference/hooks/authenticate/
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
	 * https://developer.wordpress.org/reference/hooks/registration_errors/
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
	 * https://developer.wordpress.org/reference/hooks/wpmu_validate_user_signup/
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
	 * https://developer.wordpress.org/reference/hooks/wpmu_validate_blog_signup/
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
	 * https://developer.wordpress.org/reference/hooks/lostpassword_post/
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
	 * https://developer.wordpress.org/reference/hooks/validate_password_reset/
	 *
	 * @since 1.0.0
	 * @param WP_Error $errors 
	 * @param WP_User|WP_Error $user 
	 *
	 * @return void
	 */
	function reset_password_verify( $errors, $user ) {
		$this->recaptcha_action = 'reset_password';
		if ( ! $this->verify() ) {
			$errors->add( $this->error_code, $this->get_error_msg() );
		}
	}

	/**
	 * Filter hook.
	 * 
	 * https://developer.wordpress.org/reference/hooks/pre_comment_approved/
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
