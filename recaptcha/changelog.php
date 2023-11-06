<?php
/* 
Need to get /wp-load.php so I can have access control.
I use a lot of junctions on Windows and __DIR__ points to the actual location. Couldn't think of a better way.
 */
if ( !empty($_SERVER['DOCUMENT_ROOT']) ) {
	$root = $_SERVER['DOCUMENT_ROOT'];
} else {
	$root = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['SCRIPT_FILENAME']);
}

if ( !is_file("{$root}/wp-load.php") ) {
	exit;
}

require_once "{$root}/wp-load.php";
require_once "{$root}/wp-admin/includes/plugin.php";

$json_request = isset($_POST['json']);

if ( is_file(__DIR__ . '/disable') || !is_plugin_active(plugin_basename(__DIR__.'/recaptcha.php')) ) {
	if ( $json_request ) {
		http_response_code(404);
	}
	exit;
};

$required_capability = 'install_plugins';

if ( $json_request && ( !is_user_logged_in() || !current_user_can( $required_capability) ) ) {
	http_response_code(403);
	exit;
} else {
	auth_redirect();

	if ( !current_user_can( $required_capability ) ) {
		wp_die( translate( 'Sorry, you are not allowed to access this page.' ) );
	}

	define( 'DEFAULT_THEME', 'light-grey' );
	define( 'FONT_SIZE_BASE', 16 );
	define( 'FONT_SIZE_CONTENT', 16 );
	define( 'BLOCK_MARGIN_TOP', 24 );
	define( 'BLOCK_MARGIN_BOTTOM', 16 );
	define( 'ADJUST_FONT_MIN', 10 );
	define( 'ADJUST_FONT_MAX', 26 );
	define( 'ADJUST_FONT_STEP', 1 );
	$theme = esc_html(!empty($_GET['theme']) ? $_GET['theme'] : DEFAULT_THEME);
	$font_size = esc_html(!empty($_GET['font_size']) ? $_GET['font_size'] : FONT_SIZE_CONTENT);
}

define( 'TRANSIENT_NAME', 'recaptcha_plugin_changelog' );
define( 'TRANSIENT_EXPIRATION', HOUR_IN_SECONDS );

$cached_html = '';
$html = '';
$error = '';

if ( isset($_POST['refresh_cache']) ) {
	delete_site_transient(TRANSIENT_NAME);
} else {
	$cached_html = get_site_transient(TRANSIENT_NAME);
}

if ( !empty($cached_html) ) {
	$html = $cached_html;
} else {
	// Got wp_remote_get() available, but I also want to use HTTP/2 and Gzip/Deflate content encoding!
	$ch = curl_init();
	if ($ch === false) {
		$error = '<p>Could not initialize a new cURL handle.</p>';
	} else {
		$plugin_data = get_plugin_data(__DIR__.'/recaptcha.php', false, false);

		$timeout = 20;
		$options = [
			CURLOPT_URL            => "{$plugin_data['PluginURI']}/blob/main/recaptcha/CHANGELOG.md",
			CURLOPT_HEADER         => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_ENCODING       => 'gzip, deflate',
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS      => 5,
			CURLOPT_USERAGENT      => sprintf("WordPress/%s; %s", $wp_version, home_url( '/' )),
			CURLOPT_CONNECTTIMEOUT => $timeout,
			CURLOPT_TIMEOUT        => $timeout,
		];
	
		// Check for SSl root certificates on Windows
		if ( strpos( $options[CURLOPT_URL], "https" ) === 0 && strtolower(substr(php_uname('s'),0,3)) == 'win' && empty(ini_get('curl.cainfo')) && empty(ini_get('openssl.cafile')) ) {
			$cafile = ABSPATH . WPINC . '/certificates/ca-bundle.crt';
			if ( is_file($cafile) ) {
				$options[CURLOPT_CAINFO] = $cafile;
			} else {
				$options[CURLOPT_URL] = substr_replace($options[CURLOPT_URL], 'http', 0, 5);
			}
		}

		curl_setopt_array($ch, $options);
		$result = curl_exec($ch);
		$error_code = curl_errno($ch);
		$error_msg = curl_error($ch);
		$response_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		curl_close($ch);

		$url_link = sprintf('<a href="%1$s" target="_blank">%1$s</a>', $options[CURLOPT_URL]);
		if ( $result === false || $response_code !== 200 ) {
			$error = sprintf( '<p>Was unable to retrieve data from %s.</p><p>%s%s%s</p>',
				$url_link,
				!empty($response_code) ? sprintf('Response code: "%s".<br>', $response_code) : '',
				!empty($error_code) ? sprintf('Error code: "%s".<br>', $error_code) : '',
				!empty($error_msg) ? sprintf(' Error message: "%s".<br>', $error_msg) : ''
			);
		} else {
			$data = $result;
			if ( empty($data) ) {
				$error = sprintf('<p>Empty data retrieved from %s.</p>', $url_link);
			} else {
				$data = json_decode($data, true);
				if (is_array($data)) {
					if ( isset($data['payload']['blob']['richText']) ) {
						$html = $data['payload']['blob']['richText'];
					} else {
						$error = sprintf('<p>Couldn\'t find <code>%s</code> in JSON data from %s.</p>', "\$data['payload']['blob']['richText']", $url_link );
					}
				} else {
					$error = sprintf('<p>Invalid JSON data from from %s.</p>', $url_link);
				}

				if (!empty($html)) {
					$html = preg_replace('/<article[a-z"=\- 0-9\.#]*>(.*)<\x2farticle>/is','${1}', $html);
					$html = preg_replace('/<(h1|h2|h3)[a-z"=\- 0-9\.#]*><a[a-z"=\- 0-9\.#:\x2f]*>(.*)<svg[a-z"=\- 0-9\.#]*><path[a-z"=\- 0-9\.#]*><\x2fpath><\x2fsvg><\x2fa><\x2f(h1|h2|h3)>/mi','<${1}>${2}</${1}>', $html);
					$html = preg_replace('/<([a-z]+)\x20+[a-z]+\x20?=\x20?"[a-z\- 0-9\.#]*">/mi','<${1}>', $html);
					$html = preg_replace('/<a ([a-z"=\- 0-9\.#:\x2f]*)>/mi','<a ${1} target="_blank">', $html);

					set_site_transient(TRANSIENT_NAME, $html, TRANSIENT_EXPIRATION);
				}
			}
		}
	}
}

$output = !empty($html) ? $html : $error;

if ( $json_request ) {	

	$json_data = json_encode(['payload' => $output ]);

	http_response_code(200);
	header("Content-type: application/json");
	header("Content-Length: ".strlen($json_data));
	echo $json_data;
	exit;
} elseif ( !empty($_POST) ) {
	$query_data = [
		'theme' => !empty($_POST['theme']) ? $_POST['theme'] : DEFAULT_THEME,
		'font_size' => !empty($_POST['font_size']) ? $_POST['font_size'] : FONT_SIZE_CONTENT,
	];

	$query = http_build_query($query_data, '', '&');

	wp_redirect("{$_SERVER['SCRIPT_NAME']}?{$query}", 303);
	exit;	
}

?>
<!DOCTYPE html>
<html lang="en-gb" data-theme="<?= $theme ?>">
	<head>
		<title>Changelog</title>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<style>
			:root {
				--base-text-weight-light: 300;
				--base-text-weight-normal: 400;
				--base-text-weight-medium: 500;
				--base-text-weight-semibold: 600;
				--base-text-size-normal: <?= FONT_SIZE_BASE?>px;
				--base-text-line-height-normal: 1.5;
				--base-text-font-family-default: -apple-system,BlinkMacSystemFont,"Segoe UI","Noto Sans",Helvetica,Arial,sans-serif,"Apple Color Emoji","Segoe UI Emoji";
				--base-text-underline-offset-default: 0.2em;

				--slider-width: 100px;
				--slider-height: 24px;
				--slider-margin: 0;
				--slider-background: var(--color-border-default);
				--slider-thumb-color: var(--color-accent-fg);
				--slider-thumb-box-shadow: 0px 0px 2px 1px var(--color-accent-fg);
				--slider-track-height: 10px;
				--slider-track-border-radius: 5px;
				--slider-thumb-height: 30px;
				--slider-thumb-border-radius: 50%;

				--wrapper-padding-tiny: 4px;
				--wrapper-padding-small: 8px;
				--wrapper-padding-medium: 16px;
				--wrapper-padding-large: 32px;
			}

			* {
				box-sizing: border-box;
			}

			html[data-theme="light"] {
				--color-accent-fg: rgb(9, 105, 218);
				--color-accent-muted: rgba(84,174,255,0.4);
				--color-border-default: rgb(208, 215, 222);
				--color-border-muted: rgb(216, 222, 228);
				--color-canvas-default: rgb(255, 255, 255);
				--color-canvas-subtle: rgb(246, 248, 250);
				--color-fg-default: rgb(31, 35, 40);
				--color-fg-muted: rgb(101, 109, 118);
				--color-neutral-muted: rgba(175, 184, 193, 0.2);
				--color-primer-shadow-inset: inset 0 1px 0 rgba(208,215,222,0.2);

				--color-btn-text: rgb(36, 41, 47);
				--color-btn-bg: rgb(246, 248, 250);
				--color-btn-border: rgba(31,35,40,0.15);
				--color-btn-shadow: 0 1px 0 rgba(31,35,40,0.04);
				--color-btn-inset-shadow: inset 0 1px 0 rgba(255,255,255,0.25);
				--color-btn-hover-bg: rgb(243, 244, 246);
				--color-btn-hover-border: rgba(31,35,40,0.15);
				--color-btn-active-bg: hsla(220,14%,93%,1);
				--color-btn-active-border: rgba(31,35,40,0.15);
				--color-btn-selected-bg: hsla(220,14%,94%,1);
				--color-btn-counter-bg: rgba(31,35,40,0.08);	
			}
			
			html[data-theme="light-grey"] {
				--color-accent-fg: rgb(34, 113, 177);
				--color-accent-muted: rgba(84,174,255,0.4);
				--color-border-default: rgb(208, 215, 222);
				--color-border-muted: rgb(220, 220, 220);
				--color-canvas-default: rgb(240, 240, 241);
				--color-canvas-subtle: rgb(230 230 231);
				--color-fg-default: rgb(60, 67, 74);
				--color-fg-muted: rgb(101, 109, 118);
				--color-neutral-muted: rgba(0, 0, 0, 0.07);
				--color-primer-shadow-inset: inset 0 1px 0 rgba(208,215,222,0.2);

				--color-btn-text: rgb(36, 41, 47);
				--color-btn-bg: rgb(228, 230, 232);
				--color-btn-border: rgba(31,35,40,0.15);
				--color-btn-shadow: 0 1px 0 rgba(31,35,40,0.04);
				--color-btn-inset-shadow: inset 0 1px 0 rgba(255,255,255,0.25);
				--color-btn-hover-bg: rgb(223, 224, 226);
				--color-btn-hover-border: rgba(31,35,40,0.15);
				--color-btn-active-bg: rgb(216, 218, 220);
				--color-btn-active-border: rgba(31,35,40,0.15);
				--color-btn-selected-bg: hsla(220,14%,94%,1);
				--color-btn-counter-bg: rgba(31,35,40,0.08);
			}

			html[data-theme="dark"] {
				--color-accent-fg: rgb(47, 129, 247);
				--color-accent-muted: rgba(56,139,253,0.4);
				--color-border-default: rgb(48, 54, 61);
				--color-border-muted: rgb(33, 38, 45);
				--color-canvas-default: rgb(13, 17, 23);
				--color-canvas-subtle: rgb(22, 27, 34);
				--color-fg-default: rgb(230, 237, 243);
				--color-fg-muted: rgb(125, 133, 144);
				--color-neutral-muted: rgba(110, 118, 129, 0.4);
				--color-primer-shadow-inset: 0 0 transparent;

				--color-btn-text: rgb(201, 209, 217);
				--color-btn-bg: rgb(33, 38, 45);
				--color-btn-border: rgba(240, 246, 252, 0.1);
				--color-btn-shadow: 0 0 transparent;
				--color-btn-inset-shadow: 0 0 transparent;
				--color-btn-hover-bg: rgb(48, 54, 61);
				--color-btn-hover-border: rgb(139, 148, 158);
				--color-btn-active-bg: hsla(212,12%,18%,1);
				--color-btn-active-border: rgb(110, 118, 129);
				--color-btn-selected-bg: rgb(22, 27, 34);
				--color-btn-counter-bg: rgb(48, 54, 61);
			}

			::selection {
				background-color: var(--color-accent-muted);
			}

			html, body {
				margin: 0;
			}

			body {
				background: var(--color-canvas-default);
				font-family: var(--base-text-font-family-default);
				font-size: var(--base-text-size-normal);
				line-height: var(--base-text-line-height-normal);
				color: var(--color-fg-default);
			}

			a, a:hover {
				color: var(--color-accent-fg);
				text-decoration: underline;
				text-underline-offset: var(--base-text-underline-offset-default);
			}

			a:not([class]):focus,
			a:not([class]):focus-visible,
			input[type=radio]:focus,
			input[type=radio]:focus-visible,
			input[type=checkbox]:focus,
			input[type=checkbox]:focus-visible {
				outline-offset: 0;
			}

			button:focus, a:focus {
				outline: none;
				box-shadow: none;
			}

			a:focus-visible, button:focus-visible,
			[role=button]:focus-visible,
			input[type=radio]:focus-visible,
			input[type=checkbox]:focus-visible {
				outline: 2px solid var(--color-accent-fg);
				outline-offset: -2px;
				box-shadow: none;
			}

			h1, h2, h3, h4, h5, h6 {
				line-height: 1.25em;
				font-weight: var(--base-text-weight-semibold);
			}

			h1, h2 {
				padding-bottom: 0.3em;
				border-bottom: 1px solid var(--color-border-muted);
			}
<?php
foreach ([2, 1.5, 1.25, 1, .875, .85] as $index => $size) {
	printf('
			h%s {
				font-size: %sem;
				margin-top: %sem;
				margin-bottom: %sem;
			}
',
		$index + 1,
		$size,
		BLOCK_MARGIN_TOP / (FONT_SIZE_BASE * $size),
		BLOCK_MARGIN_BOTTOM  / (FONT_SIZE_BASE * $size)
	);
}
?>

			h6 {
				color: var(--color-fg-muted);
			}

			p, blockquote, ul, ol, dl, table, pre, details {
				margin-top: 0;
				margin-bottom: <?= BLOCK_MARGIN_BOTTOM / FONT_SIZE_BASE ?>em;
			}

			b, strong {
				font-weight: var(--base-text-weight-semibold);
			}

			ol, ul {
				padding-left: 2em;
			}

			ul ul, ul ol, ol ol, ol ul {
				margin-top: 0;
				margin-bottom: 0;
			}

			li+li {
				margin-top: 0.25em;
			}

			tt, code, samp {
				font-family: ui-monospace,SFMono-Regular,SF Mono,Menlo,Consolas,Liberation Mono,monospace;
				font-size: 12px;
			}

			code {
				padding: 0.2em 0.4em;
				margin: 0;
				font-size: .85em;
				white-space: break-spaces;
				background-color: var(--color-neutral-muted);
				border-radius: <?= 6 / (FONT_SIZE_BASE * .85) ?>em;
			}

			pre {
				padding: <?= 16 / (FONT_SIZE_BASE * .85) ?>em;
				overflow: auto;
				font-size: .85em;
				line-height: 1.45;
				color: var(--color-fg-default);
				background-color: var(--color-canvas-subtle);
				border-radius: <?= 6 / (FONT_SIZE_BASE * .85) ?>em;
				word-wrap: normal;
				margin-bottom: <?= BLOCK_MARGIN_BOTTOM / (FONT_SIZE_BASE * .85) ?>em;
			}

			pre code {
				display: inline;
				padding: 0;
				margin: 0;
				overflow: visible;
				line-height: inherit;
				word-wrap: normal;
				background: transparent;
				font-size: 100%;
				border: 0;
				word-break: normal;
				white-space: pre;
			}

			table {
				display: block;
				width: max-content;
				max-width: 100%;
				overflow: auto;
				border-spacing: 0;
				border-collapse: collapse;
			}

			table tr {
				background-color: var(--color-canvas-default);
				border-top: 1px solid var(--color-border-muted);
			}

			table tr:nth-child(2n) {
				background-color: var(--color-canvas-subtle);
			}

			table th {
				font-weight: var(--base-text-weight-semibold);
			}

			table th, table td {
				padding: <?= 6 / FONT_SIZE_BASE ?>em <?= 13 / FONT_SIZE_BASE ?>em;
				border: 1px solid var(--color-border-default);
			}

			label {
				cursor: pointer;
				font-weight: var(--base-text-weight-semibold);
				font-size: .875em;
			}

			blockquote {
				margin-left: 0;
				margin-right: 0;
				padding: 0 1em;
				color: var(--color-fg-muted);
				border-left: 0.25em solid var(--color-border-default);
			}

			blockquote > :last-child {
				margin-bottom: 0;
			}

			blockquote > :first-child {
				margin-top: 0;
			}

			input, select, textarea, button {
				font-family: inherit;
				font-size: inherit;
				line-height: inherit;
				color: inherit;
			}

			input + span[data-type]:before {
				font-weight: normal;
				cursor: default;
				content: attr(data-type);
			}

			input[type="radio"], input[type="checkbox"] {
				margin: 0 5px 0 0;
			}

			input[type="number"] {
				width: 80px;
				background: var(--color-canvas-default);
				padding: 5px 12px;
				border-radius: 6px;
				border: 1px solid var(--color-border-default);
				box-shadow: var(--color-primer-shadow-inset);
			}

			input[type="number"]:focus {
				border-color: var(--color-accent-fg);
				outline: none;
				box-shadow: inset 0 0 0 1px var(--color-accent-fg);
			}

			input[type="number"]:focus-visible {
				border-color: var(--color-accent-fg);
				outline: none;
				box-shadow: inset 0 0 0 1px var(--color-accent-fg);
			}

			input[type="number"]:focus:not(:focus-visible) {
				border-color: transparent;
				border-color: var(--color-accent-fg);
				outline: none;
				box-shadow: inset 0 0 0 1px transparent;
			}

			input[type=number]::-webkit-inner-spin-button {
				opacity: 1;
			}

			input[type="range"] {
				margin: var(--slider-margin);
				-webkit-appearance: none;
				appearance: none;
				background: transparent;
				width: var(--slider-width);
				height: var(--slider-height);
			}

			input[type="range"]::-webkit-slider-runnable-track {
				background: var(--slider-background);
				height: var(--slider-track-height);
				border-radius: var(--slider-track-border-radius);
			}

			input[type="range"]::-moz-range-track {
				background: var(--slider-background);
				height: var(--slider-track-height);
				border-radius: var(--slider-track-border-radius);
			}

			input[type="range"]::-webkit-slider-thumb{
				-webkit-appearance: none;
				appearance: none;
				background-color: var(--slider-thumb-color);
				height: var(--slider-thumb-height);
				width: var(--slider-thumb-height);
				margin-top: calc(calc(var(--slider-track-height) / 2 ) - calc(var(--slider-thumb-height) / 2));
				border-radius: var(--slider-thumb-border-radius);
			}

			input[type="range"]::-moz-range-thumb {
				border: none;
				background-color: var(--slider-thumb-color);
				height: var(--slider-thumb-height);
				width: var(--slider-thumb-height);
				border-radius: var(--slider-thumb-border-radius);
			}

			input[type="range"]:focus {
				outline: none;
			}

			/* 
			input[type="range"]:active::-webkit-slider-thumb {
				box-shadow: var(--slider-thumb-box-shadow);

			}

			input[type="range"]:active::-moz-range-thumb {
				box-shadow: var(--slider-thumb-box-shadow);
			} */

			.btn {
				color: var(--color-btn-text);
				background-color: var(--color-btn-bg);
				box-shadow: var(--color-btn-shadow),var(--color-btn-inset-shadow);
				transition: 80ms cubic-bezier(0.33, 1, 0.68, 1);
				transition-property: color,background-color,box-shadow,border-color;
				display: inline-block;
				font-size: .875em;
				padding: <?= 5 / (FONT_SIZE_BASE * .875) ?>em <?= 16 / (FONT_SIZE_BASE * .875) ?>em; 
				line-height: <?= 20 / (FONT_SIZE_BASE * .875) ?>em;
				font-weight: var(--base-text-weight-medium);
				white-space: nowrap;
				vertical-align: middle;
				cursor: pointer;
				-webkit-user-select: none;
				user-select: none;
				border: 1px solid var(--color-btn-border);
				border-radius: <?= 6 / (FONT_SIZE_BASE * .875) ?>em;
				-webkit-appearance: none;
				appearance: none;
			}

			.btn:hover, .btn.hover, [open]>.btn {
				background-color: var(--color-btn-hover-bg);
				border-color: var(--color-btn-hover-border);
				transition-duration: .1s;
				text-decoration: none;
			}

			.btn:active {
				background-color: var(--color-btn-active-bg);
				border-color: var(--color-btn-active-border);
				transition: none;
			}

			.hide-if-no-js {
				display: none !important;
			}

			.loading {
				opacity: 0.35;
			}

			.inline-label {
				margin: 0 5px;
			}

			#wrapper {
				padding: var(--wrapper-padding-medium) var(--wrapper-padding-tiny) var(--wrapper-padding-large);
				margin: 0 auto;
				box-sizing: content-box;
				max-width: 1012px;
			}

			@media only screen and (min-width: 321px) {
				#wrapper {
					padding-left: var(--wrapper-padding-small);
					padding-right: var(--wrapper-padding-small);
				}
			}

			@media only screen and (min-width: 610px) {
				#wrapper {
					padding-left: var(--wrapper-padding-medium);
					padding-right: var(--wrapper-padding-medium);
				}
			}

			@media only screen and (min-width: 801px) {
				#wrapper {
					padding-left: var(--wrapper-padding-large);
					padding-right: var(--wrapper-padding-large);
				}
			}

			#wrapper > header {
				margin-bottom: 10px;
			}

			#content {
				font-size: <?= FONT_SIZE_CONTENT ?>px;
				line-height: var(--base-text-line-height-normal);
				word-wrap: break-word;
				transition: opacity 250ms linear;
			}

			#content > :first-child {
				margin-top: 0;
			}

			#content > :last-child {
				margin-bottom: 0;
			}

			#form {
				display: grid;
				width: 100%;
			}

			#form label {
				white-space: nowrap;
			}
			
			#form,
			#refresh-cache-button-container-no-js {
				row-gap: 15px;
			}

			#adjust-font-size,
			#theme-select,
			#refresh-cache {
				text-align: center;
			}

			#adjust-font-size-input-container-js {
				width: 85%;
				display: inline-grid;
				justify-items: center;
				row-gap: 10px;
			}

			#adjust-font-size-input-container-js > div {
				display: flex;
				align-items: center;
				flex-direction: row;
				column-gap: 5px;
			}

			#adjust-font-size-input-container-js > div,
			#adjust-font-size-input-container-js > div > input[type="range"] {
				width: 100%;
			}

			#adjust-font-size-input-container-js span {
				pointer-events: none;
				user-select: none;
				line-height: 1;
				opacity: .8;
				font-size: 0.75em;
				font-weight: var(--base-text-weight-semibold);
			}

			#adjust-font-size-input-container-no-js,
			#theme-select > div,
			#refresh-cache-button-container-js {
				display: inline-block;
			}

			#refresh-cache-button-container-no-js {
				text-align: center;
				display: inline-grid;
				justify-items: center;
			}


			@media only screen and (min-width: 400px) {
				#adjust-font-size-input-container-js {
					width: 70%;
				}
			}

			@media only screen and (min-width: 500px) {
				#adjust-font-size-input-container-js {
					width: 60%;
				}
			}

			@media only screen and (min-width: 590px) {
				:root {
					--slider-thumb-height: 20px;
				}

				#form {
					display: flex;
					justify-content: space-between;
					align-items: flex-start;
				}

				#adjust-font-size,
				#refresh-cache {
					width: 161px;
				}
				
				#adjust-font-size-input-container-js {
					row-gap: 0;
					width: 100%;
					
				}

				#adjust-font-size {
					text-align: left;
				}

				#refresh-cache {
					text-align: right;
				}

				#refresh-cache-button-container-no-js {
					justify-items: end;
					row-gap: 5px;
				}
			}
		</style>
		<script>
			function update_url_params(param, value) {
				if ( history.replaceState && URLSearchParams ) {
					params = new URLSearchParams(document.location.search)
					params.delete(param);
					params.append(param, value);
					var new_url = window.location.origin + window.location.pathname + `?${params.toString()}`;
					window.history.replaceState({path:new_url},'',new_url);
				}
			}

			function swap_theme (value) {
				update_url_params('theme', value)
				document.documentElement.setAttribute('data-theme', value);
			}

			function update_font_size(value) {
				update_url_params('font_size', value);
				value = value +'px';
				document.getElementById('content').style.fontSize = value;
				document.getElementById('current-font-size').innerHTML = value;
			}

			async function refresh_cache(button) {
				var button_innerHTML = button.innerHTML;
				var content = document.getElementById('content');

				button.innerHTML = 'Refreshing...';
				content.classList.add('loading');

				var post = new FormData();
				post.append( 'json', '' );
				post.append( 'refresh_cache', '' );
				try {
					const response = await fetch("<?= $_SERVER['SCRIPT_NAME'] ?>",
					{
						method: "POST",
						body: post
					});

					const data = await response.json();
					document.getElementById('content').innerHTML = data.payload;
				} catch (error) {
					console.log(`Error fetching JSON data: ${error.message}`)
				}

				button.innerHTML = button_innerHTML;
				content.classList.remove('loading');
			}

		</script>
	</head>
	<body>
		<div id="wrapper">	
			<header>
				<form id="form" action="" method="post">
					<div id="adjust-font-size">
						<div  id="adjust-font-size-input-container-js" class="hide-if-no-js">
							<div>
								<span><?= ADJUST_FONT_MIN ?>px</span>
								<input type="range" step="<?= ADJUST_FONT_STEP ?>" min="<?= ADJUST_FONT_MIN ?>" max="<?= ADJUST_FONT_MAX ?>" value="<?= $font_size ?>" oninput="update_font_size(this.value)" name="font_size">
								<span><?= ADJUST_FONT_MAX ?>px</span>
							</div>
							<span id="current-font-size"><?= $font_size ?>px</span>
						</div>
						<div id="adjust-font-size-input-container-no-js" class="remove-if-js">
							<label>Font size: <input type="number" name="font_size" step="<?= ADJUST_FONT_STEP ?>" min="<?= ADJUST_FONT_MIN ?>" max="<?= ADJUST_FONT_MAX ?>" value="<?= $font_size ?>" placeholder="<?= $font_size ?>"><span data-type="px"></span></label>
						</div>
					</div>
					<div id="theme-select">
						<div>
							<label class="inline-label"><input type="radio" name="theme" value="dark" onclick="swap_theme(this.value)"<?= $theme == 'dark' ? ' checked' : ''?>>Dark</label>
							<label class="inline-label"><input type="radio" name="theme" value="light" onclick="swap_theme(this.value)"<?= $theme == 'light' ? ' checked' : ''?>>Light</label>
							<label class="inline-label"><input type="radio" name="theme" value="light-grey" onclick="swap_theme(this.value)"<?= $theme == 'light-grey' ? ' checked' : ''?>>Light grey</label>
						</div>
					</div>
					<div id="refresh-cache">
						<div id="refresh-cache-button-container-js" class="hide-if-no-js">
							<button class="btn" type="button" onclick="refresh_cache(this)">Refresh cache</button>
						</div>
						<div id="refresh-cache-button-container-no-js" class="remove-if-js">
							<label><input type="checkbox" name="refresh_cache" value="">Refresh cache</label>
							<button type="submit" class="btn">Submit</button>
						</div>
					</div>
				<form>
			</header>
			<main id="content" style="font-size: <?= $font_size ?>px">
<?= $output ?>
			</main>
		</div>
	<script>
		( function () {
			document.getElementById('form').onsubmit = function(event) {
				event.preventDefault();
			};

			document.querySelectorAll('.remove-if-js').forEach(function(element){
				element.remove();
			});

			document.querySelectorAll('.hide-if-no-js').forEach(function(element){
				element.classList.remove('hide-if-no-js');
			});

			document.querySelectorAll('.hide-if-js').forEach(function(element){
				element.style.display = 'none';
			});
		} )();
	</script>
	</body>
</html>
