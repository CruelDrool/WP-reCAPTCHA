<!DOCTYPE html>
<html lang="en-gb" class="theme-dark">
	<head>
		<title>Changelog</title>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8">
		<style>
			:root {
				--base-text-weight-light: 300;
				--base-text-weight-normal: 400;
				--base-text-weight-medium: 500;
				--base-text-weight-semibold: 600;
			}

			html.theme-light {
				--color-canvas-default: rgb(255, 255, 255);
				--color-canvas-subtle: rgb(246, 248, 250);
				--color-fg-default: rgb(31, 35, 40);
				--color-accent-fg: rgb(9, 105, 218);
				--color-border-muted: rgb(216, 222, 228);
				--color-border-default: rgb(208, 215, 222);
				--color-neutral-muted: rgba(175, 184, 193, 0.2);
				--color-fg-muted: rgb(101, 109, 118);
			}

			html.theme-light-grey {
				--color-canvas-default: rgb(240, 240, 241);
				--color-canvas-subtle: rgb(230 230 231);
				--color-fg-default: rgb(60, 67, 74);
				--color-accent-fg: rgb(34, 113, 177);
				--color-border-default: rgb(208, 215, 222);
				--color-border-muted: rgb(220, 220, 220);
				--color-neutral-muted: rgba(0, 0, 0, 0.07);
				--color-fg-muted: rgb(101, 109, 118);
			}

			html.theme-dark {
				--color-canvas-default: rgb(13, 17, 23);
				--color-canvas-subtle: rgb(22, 27, 34);
				--color-fg-default: rgb(230, 237, 243);
				--color-accent-fg: rgb(47, 129, 247);
				--color-border-default: rgb(48, 54, 61);
				--color-border-muted: rgb(33, 38, 45);
				--color-neutral-muted: rgba(110, 118, 129, 0.4);
				--color-fg-muted: rgb(125, 133, 144);
			}

			html, body {
				margin: 0;
			}

			body {
				background: var(--color-canvas-default);
				font-family: -apple-system,BlinkMacSystemFont,"Segoe UI","Noto Sans",Helvetica,Arial,sans-serif,"Apple Color Emoji","Segoe UI Emoji";
				font-size: 16px;
				line-height: 1.5em;
				color: var(--color-fg-default);
			}

			a {
				color: var(--color-accent-fg);
				text-decoration: underline;
				text-underline-offset: 0.2rem;
			}

			a:focus {
				outline: none;
				box-shadow: none;
			}

			h1, h2, h3, h4, h5, h6 {
				line-height: 1.25em;
				font-weight: var(--base-text-weight-semibold);
				margin-top: 24px;
				margin-bottom: 16px;
			}

			h1, h2 {
				padding-bottom: 0.3em;
				border-bottom: 1px solid var(--color-border-muted);
			}

			h1 {
				font-size: 2em;
			}

			h2 {
				font-size: 1.5em;
			}

			h3 {
				font-size: 1.25em;
			}

			h4 {
				font-size: 1em;
			}

			h5 {
				font-size: .875em;
			}

			h6 {
				font-size: .85em;
				color: var(--color-fg-muted);
			}

			p, blockquote, ul, ol, dl, table, pre, details {
				margin-top: 0;
    			margin-bottom: 16px;
			}

			b, strong {
    			font-weight: var(--base-text-weight-semibold);
			}

			ol, ul {
				padding-left: 2em;
			}

			ol ol, ul ul {
				margin-top: 0;
				margin-bottom: 0;
			}

			code {
				padding: 0.2em 0.4em;
				margin: 0;
				font-size: 85%;
				white-space: break-spaces;
				background-color: var(--color-neutral-muted);
				border-radius: 6px;
			}

			pre {
				padding: 16px;
				overflow: auto;
				font-size: 85%;
				line-height: 1.45;
				color: var(--color-fg-default);
				background-color: var(--color-canvas-subtle);
				border-radius: 6px;
				word-wrap: normal;
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

			.markdown-body table th {
				font-weight: var(--base-text-weight-semibold);
			}

			table th, table td {
				padding: 6px 13px;
				border: 1px solid var(--color-border-default);
			}

			label {
				cursor: pointer;
				font-weight: var(--base-text-weight-semibold);
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
			#wrapper {
				padding: 32px;
				margin: 0 auto;
				max-width: 1012px;
			}

			#themes {
				margin: 0 auto;
				width: fit-content;
				margin-bottom: 16px;
				font-size: 16px !important;
				line-height: 1.5em;
			}

			#themes label {
				margin: 0 5px;
			}

			#themes input {
				margin: 0 5px 0 0;
			}

			article > :first-child {
				margin-top: 0;
			}

			article > :last-child {
				margin-bottom: 0;
			}

			#slider-container {
				float: right;
				line-height: 1.5em;
				font-size: 16px !important;
				display: flex;
				align-items: center;
				flex-direction: column;
			}

			#slider-container > div {
				display: flex;
				align-items: center;
				flex-direction: row;
				column-gap: 4px;
			}

			#slider-container > div span {
				font-size: 12px;
			}

			input[type="range"] {
				margin: 0;
				-webkit-appearance: none;
				appearance: none;
				background: transparent;
				width: 100px;
				height: 24px;
				--slider-background: var(--color-border-default);
				--slider-thumb-color: var(--color-accent-fg);
				--slider-thumb-box-shadow: 0px 0px 2px 1px var(--color-accent-fg);
				--slider-track-height: 10px;
				--slider-thumb-height: 20px;
				--slider-thumb-border-radius: 50%;
			}

			input[type="range"]::-webkit-slider-runnable-track {
				background: var(--slider-background);
				height: var(--slider-track-height);
				border-radius: 5px;
			}

			input[type="range"]::-moz-range-track {
				background: var(--slider-background);
				height: var(--slider-track-height);
				border-radius: 5px;
			}

			input[type="range"]::-webkit-slider-thumb{
				-webkit-appearance: none;
				appearance: none;
				background-color: var(--slider-thumb-color);
				height: var(--slider-thumb-height);
				width: var(--slider-thumb-height);
				margin-top: calc(calc(var(--slider-track-height) / 2 )  - calc(var(--slider-thumb-height) / 2));
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

		</style>
	</head>
	<body>
		<div id="wrapper">
			
			<main>
				<div id="slider-container">
					<span></span>
					<div>
						<span></span>
						<input type="range" step="1">
						<span></span>
					</div>
				</div>
				<div id="themes">
					<label><input type="radio" name="theme" value="dark" onclick="swap_theme(this.value)">Dark</label>
					<label><input type="radio" name="theme" value="light" onclick="swap_theme(this.value)">Light</label>
					<label><input type="radio" name="theme" value="light-grey" onclick="swap_theme(this.value)">Light grey</label>
				</div>
<script>
var swap_theme = function(theme) {
	document.documentElement.classList='theme-' + theme;
};

( function () {
	var container = document.getElementById('slider-container');
	var span_current_size = container.getElementsByTagName('span')[0];
	var span_min_size = container.getElementsByTagName('span')[1];
	var span_max_size = container.getElementsByTagName('span')[2];
	var slider = container.getElementsByTagName('input')[0];

	var current = getComputedStyle(document.body).getPropertyValue('font-size');
	span_current_size.innerHTML = current;

	current = parseInt(current.match(/(\d{1,2})px/)[1], 10);
	slider.value = current;

	slider.min = current - current/2;
	slider.max = current + current/2;

	span_min_size.innerHTML = slider.min + 'px';
	span_max_size.innerHTML = slider.max + 'px';

	slider.oninput=function() {
		var value = this.value +'px';
		document.body.style.fontSize = value;
		span_current_size.innerHTML = value
	};

	document.getElementById('themes').querySelectorAll('input').forEach(function(element) {
		var value = 'theme-' + element.value
		if ( value ==  document.documentElement.classList[0]) {
			element.checked = true;
		}
	});
} )();
</script>
				<article>
<?php
$url = 'https://github.com/CruelDrool/WP-reCAPTCHA/blob/main/recaptcha/CHANGELOG.md';
$options = [
	CURLOPT_HEADER         => false,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_SSL_VERIFYHOST => 2,
	CURLOPT_SSL_VERIFYPEER => true,
	CURLOPT_ENCODING       => 'gzip, deflate',
	CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_MAXREDIRS      => 5,
];

/* 
Check for SSl root certificates on Windows
*/
$ssl_certs = true;
if ( strtolower(substr(php_uname('s'),0,3)) == 'win' && empty(ini_get('curl.cainfo')) && empty(ini_get('openssl.cafile')) ) {
	$cafile = "{$_SERVER['DOCUMENT_ROOT']}/wp-includes/certificates/ca-bundle.crt";
	if ( is_file($cafile) ) {
		$options[CURLOPT_CAINFO] = $cafile;
	} else {
		$ssl_certs = false;
		//echo "<p>PHP on Windows warning: Not using SSL to connect. <code>curl.cainfo</code> or <code>openssl.cafile</code> not set and unable to find WordPress' own <code>ca-bundle.crt</code>.</p>";
	}
}

if ( !$ssl_certs ) {
	$url = substr_replace($url, 'http', 0, 5);
}

$options[CURLOPT_URL] = $url;

/* 
Set User-Agent
*/
if ( is_file("{$_SERVER['DOCUMENT_ROOT']}/wp-includes/version.php") ) {
	require_once "{$_SERVER['DOCUMENT_ROOT']}/wp-includes/version.php";

	$is_ssl = false;
	if ( isset( $_SERVER['HTTPS'] ) ) {
		if ( 'on' === strtolower( $_SERVER['HTTPS'] ) ) {
			$is_ssl = true;
		}

		if ( '1' === (string) $_SERVER['HTTPS'] ) {
			$is_ssl = true;
		}
	} elseif ( isset( $_SERVER['SERVER_PORT'] ) && ( '443' === (string) $_SERVER['SERVER_PORT'] ) ) {
		$is_ssl = true;
	}

	$options[CURLOPT_USERAGENT] = sprintf("WordPress/%s; %s://%s", $wp_version, $is_ssl ? 'https' : 'http', $_SERVER['SERVER_NAME']);
}

$ch = curl_init();
curl_setopt_array($ch, $options);
$result = curl_exec($ch);
$error_code = curl_errno($ch);
$error_msg = curl_error($ch);
$response_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

$data = '';
if ( $result !== false && $response_code === 200 ) {
	$data = $result;
} else {
	printf( '<p>Was unable to retrieve data from <a href="%1$s" target="_blank">%1$s</a></p><p>Response code: %2$s<br>Error code: %3$s<br>Error message: %4$s</p>',
		$url, // 1
		$response_code, // 2
		$error_code, // 3
		$error_msg // 4
	);
}

$html = '';
if ( !empty($data) ) {
	$data = json_decode($data, true);
	if (is_array($data)) {
		if ( isset($data['payload']['blob']['richText']) ) {
			$html = $data['payload']['blob']['richText'];
		} else {
			echo "<p>Couldn't find <code>\$data['payload']['blob']['richText']</code>.</p>";
		}
	} else {
		echo "<p>Invalid JSON data.</p>";
	}

	if (!empty($html)) {
		$html = preg_replace('/<article[a-z"=\- 0-9\.#]*>(.*)<\x2farticle>/is','${1}', $html);
		$html = preg_replace('/<(h1|h2|h3)[a-z"=\- 0-9\.#]*><a[a-z"=\- 0-9\.#:\x2f]*>(.*)<svg[a-z"=\- 0-9\.#]*><path[a-z"=\- 0-9\.#]*><\x2fpath><\x2fsvg><\x2fa><\x2f(h1|h2|h3)>/mi','<${1}>${2}</${1}>', $html);
		$html = preg_replace('/<([a-z]+)\x20+[a-z]+\x20?=\x20?"[a-z\- 0-9\.#]*">/mi','<${1}>', $html);
		$html = preg_replace('/<a ([a-z"=\- 0-9\.#:\x2f]*)>/mi','<a ${1} target="_blank">', $html);

		echo $html;
	}
} else {
	printf('<p>Empty data retrieved from <a href="%1$s" target="_blank">%1$s</a></p>', $url);
}


?>
				</article>
			</main>
		</div>
	</body>
</html>
