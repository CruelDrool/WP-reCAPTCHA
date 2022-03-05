<a href="#readme"><img src="https://github.com/CruelDrool/WP-reCAPTCHA/raw/main/.assets/icon.svg" alt="" align="right" /></a>

# reCAPTCHA plugin for WordPress
This is a fork of [Shamim Hasan](https://www.shamimsplugins.com)'s [Advanced noCaptcha & invisible Captcha](https://wordpress.org/plugins/advanced-nocaptcha-recaptcha) version 6.1.5. However, it has been almost completly rewritten with these goals in mind:
* [PSR-4](https://www.php-fig.org/psr/psr-4/) compliant.
* Full support of all versions of [Google's reCAPTCHA](https://www.google.com/recaptcha/).
* No premium version.

Currently, this plugin is only available here on GitHub. Once installed, however, WordPress will still be able to update it from this repository. This is because in WordPress 5.8 a new plugin header called [Update URI](https://make.wordpress.org/core/2021/06/29/introducing-update-uri-plugin-header-in-wordpress-5-8/) was introduced. [Here's a Gist I made regarding this feature](https://gist.github.com/CruelDrool/4cc70b819a33793396456c5ddb81781d).

## Requirements
WordPress >= 5.8 (lower may work just fine, but no support for updating from the repository)

PHP >= 7.1

## Features
* Switch between reCAPTCHA versions easily.
	* Each set of keys are tied to the selected version.
	* Custom error message for each version.
* Select the widget's colour theme: *Light* or *Dark*
	* Automatically set theme based on the background colour's brightness.
* Choose which request domain to use.
	* google.com
	* recaptcha.net
* Verify origin of solutions, if you've opted not to have Google do it.
* Hide for logged in users.
* Set which [language](https://developers.google.com/recaptcha/docs/language) to display the widget in.

### reCAPTCHA versions 
#### v2 "I'm not a robot" Checkbox
* Select size: *Normal* or *Compact*
	* Auto adjust size if screen is too narrow for *Normal*

#### v2 Invisible
* Select placement of the widget: *Bottom Right*, *Bottom Left* or *Inline*
	* Automatically set placement based on a page's text direction. (Left-to-Right: *Bottom Right*, Right-to-Left: *Bottom Left*)

#### v3
* Select placement of the widget: *Bottom Right*, *Bottom Left* or *Inline*
	* Automatically set placement based on a page's text direction. (Left-to-Right: *Bottom Right*, Right-to-Left: *Bottom Left*)
* Load on all pages or just form pages.
* [Actions](https://developers.google.com/recaptcha/docs/v3#actions) and thresholds for all supported forms.
	* Custom action names.

### Forms
* Login.
* Registration.
* Lost Password.
* Reset Password.
* Comment.
* Multisite User Signup. (only available on the main site in a multisite installation.)

### Languages
* English (US, GB).
* Norwegian (bokmål).

### Multisite
When network activated in a multisite installation the following happens: 
* The plugin's settings will only be available to the [Super Admin](https://wordpress.org/support/article/roles-and-capabilities/#super-admin) in the [Network Admin](https://wordpress.org/support/article/network-admin/).
* In the database the settings will be stored the `wp_sitemeta` table. This is separate from any sub-sites' settings, which are stored in `wp_<siteID>_options`.
* Settings set by the Super Admin will apply across all sites.