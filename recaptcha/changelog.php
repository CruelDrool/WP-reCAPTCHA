<?php
define('THEME', 'dark');
define('FONT_SIZE_NORMAL', 16);
define('FONT_SIZE_CONTENT', 16);
define('BLOCK_MARGIN_TOP', 24);
define('BLOCK_MARGIN_BOTTOM', 16);
define('SLIDER_MIN', 10);
define('SLIDER_MAX', 26);
define('SLIDER_STEP', 1);
?>
<!DOCTYPE html>
<html lang="en-gb" class="theme-<?= THEME ?>">
	<head>
		<title>Changelog</title>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8">
		<style>
			:root {
				--base-text-weight-light: 300;
				--base-text-weight-normal: 400;
				--base-text-weight-medium: 500;
				--base-text-weight-semibold: 600;
				--base-text-size-normal: <?= FONT_SIZE_NORMAL?>px;
				--base-text-line-height-normal: 1.5em;
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
				font-size: var(--base-text-size-normal);
				line-height: var(--base-text-line-height-normal);
				color: var(--color-fg-default);
			}

			a {
				color: var(--color-accent-fg);
				text-decoration: underline;
				text-underline-offset: 0.2em;
			}

			a:focus {
				outline: none;
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
		BLOCK_MARGIN_TOP / (FONT_SIZE_NORMAL * $size),
		BLOCK_MARGIN_BOTTOM  / (FONT_SIZE_NORMAL * $size)
	);
}
?>

			h6 {
				color: var(--color-fg-muted);
			}

			p, blockquote, ul, ol, dl, table, pre, details {
				margin-top: 0;
    			margin-bottom: <?= BLOCK_MARGIN_BOTTOM / FONT_SIZE_NORMAL ?>em;
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

			code {
				padding: 0.2em 0.4em;
				margin: 0;
				font-size: .85em;
				white-space: break-spaces;
				background-color: var(--color-neutral-muted);
				border-radius: 6px;
			}

			pre {
				padding: <?= 16 / (FONT_SIZE_NORMAL * .85) ?>em;
				overflow: auto;
				font-size: .85em;
				line-height: 1.45;
				color: var(--color-fg-default);
				background-color: var(--color-canvas-subtle);
				border-radius: 6px;
				word-wrap: normal;
				margin-bottom: <?= BLOCK_MARGIN_BOTTOM / (FONT_SIZE_NORMAL * .85) ?>em;
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
				padding: <?= 6 / FONT_SIZE_NORMAL ?>em <?= 13 / FONT_SIZE_NORMAL ?>em;
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
				padding: 5px 32px 32px;
				margin: 0 auto;
				max-width: 1012px;
			}

			#themes {
				margin: 0 auto;
				width: fit-content;
			}

			#themes label {
				margin: 0 5px;
			}

			#themes input {
				margin: 0 5px 0 0;
			}

			#content {
				clear: both;
				font-size: <?= FONT_SIZE_CONTENT ?>px;
				line-height: var(--base-text-line-height-normal);
			}

			#content > :first-child {
				margin-top: 0;
			}

			#content > :last-child {
				margin-bottom: 0;
			}

			#slider-container {
				float: right;
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

			#slider-container span {
				line-height: 1.3em;
				opacity: .8;
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
				--slider-track-border-radius: 5px;
				--slider-thumb-height: 20px;
				--slider-thumb-border-radius: 50%;
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
		<script>
			function swap_theme (value) {
				document.documentElement.classList='theme-' + value;
			}

			function update_font_size(value) {
				value = value +'px';
				document.getElementById('content').style.fontSize = value;
				document.getElementById('slider-container').getElementsByTagName('span')[0].innerHTML = value;
			}
		</script>
	</head>
	<body>
		<div id="wrapper">	
			<header>
				<div id="slider-container">
					<span><?= FONT_SIZE_CONTENT ?>px</span>
					<div>
						<span><?= SLIDER_MIN ?>px</span>
						<input type="range" step="<?= SLIDER_STEP ?>" min="<?= SLIDER_MIN ?>" max="<?= SLIDER_MAX ?>" value="<?= FONT_SIZE_CONTENT ?>" oninput="update_font_size(this.value)">
						<span><?= SLIDER_MAX ?>px</span>
					</div>
				</div>
				<div id="themes">
					<label><input type="radio" name="theme" value="dark" onclick="swap_theme(this.value)"<?= THEME == 'dark' ? ' checked' : ''?>>Dark</label>
					<label><input type="radio" name="theme" value="light" onclick="swap_theme(this.value)"<?= THEME == 'light' ? ' checked' : ''?>>Light</label>
					<label><input type="radio" name="theme" value="light-grey" onclick="swap_theme(this.value)"<?= THEME == 'light-grey' ? ' checked' : ''?>>Light grey</label>
				</div>
			</header>
			<main id="content">
<?php

$options = [
	CURLOPT_URL            => 'https://github.com/CruelDrool/WP-reCAPTCHA/blob/main/recaptcha/CHANGELOG.md',
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
if ( strtolower(substr(php_uname('s'),0,3)) == 'win' && empty(ini_get('curl.cainfo')) && empty(ini_get('openssl.cafile')) ) {
	$cafile = "{$_SERVER['DOCUMENT_ROOT']}/wp-includes/certificates/ca-bundle.crt";
	if ( is_file($cafile) ) {
		$options[CURLOPT_CAINFO] = $cafile;
	} else {
		$options[CURLOPT_URL] = substr_replace($options[CURLOPT_URL], 'http', 0, 5);
		//echo "<p>PHP on Windows warning: Not using SSL to connect. <code>curl.cainfo</code> or <code>openssl.cafile</code> not set and unable to find WordPress' own <code>ca-bundle.crt</code>.</p>";
	}
}

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
		$options[CURLOPT_URL], // 1
		$response_code, // 2
		$error_code, // 3
		$error_msg // 4
	);
}

if ( !empty($data) ) {
	$html = '';
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
	printf('<p>Empty data retrieved from <a href="%1$s" target="_blank">%1$s</a></p>', $options[CURLOPT_URL]);
}
?>
			</main>
		</div>
	</body>
</html>
