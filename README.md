<a href="#readme"><img src="https://github.com/CruelDrool/WP-reCAPTCHA/raw/main/.assets/icon.svg" alt="" align="right" width="75" /></a>

# reCAPTCHA plugin for WordPress

This started as a fork of [Shamim Hasan](https://www.shamimsplugins.com)'s [Advanced noCaptcha & invisible Captcha](https://wordpress.org/plugins/advanced-nocaptcha-recaptcha) version 6.1.5. However, it has been almost completly rewritten with these goals in mind:
- [PSR-4](https://www.php-fig.org/psr/psr-4/) compliant.
- Full support for all versions of [Google's reCAPTCHA](https://www.google.com/recaptcha/).
- No premium version.

Currently, this plugin is only available here on GitHub. Once installed, however, WordPress will still be able to update it from this repository. This is because in WordPress 5.8 a new plugin header called [Update URI](https://make.wordpress.org/core/2021/06/29/introducing-update-uri-plugin-header-in-wordpress-5-8/) was introduced.

## Requirements

WordPress >= 5.8 (lower may work just fine, but no support for updating from the repository)

PHP >= 7.1

## Supported reCAPTCHA versions

### Enterprise (CreateAssessment API)

- Score
- Checkbox
- Invisible
- Policy-based

### Legacy (SiteVerify API)

- v2 Checkbox
- v2 Invisible
- v3

## Features

- Switch between reCAPTCHA versions easily. Each set of keys are tied to the selected version.
- Custom error message for each version.
- Hide for logged in users.
- Choose which request domain to use: *google.com* or *recaptcha.net*
- Verify origin of solutions, if you've opted not to have Google do it.
- Set which [language](https://developers.google.com/recaptcha/docs/language) to display the widget in.
- Select the widget's colour theme: *Light*, *Dark* or *Automatic*. *Automatic* will set theme based on the background colour's brightness.
- Select the widget's size: *Normal* or *Compact* or *Automatic*. *Automatic* will set size to *Compact* if screen/area is too narrow for *Normal*. (Only for Checkbox and v2 Checkbox.)
- Select placement of the widget: *Bottom Right*, *Bottom Left*, *Inline* or *Automatic*. *Automatic* will set placement based on a page's text direction. (Only for Score, Policy-based, Invisible, and v2 Invisible, and v3.)
- Add CSS stylesheet to the login page to increase the width of the container element that holds the login form. (Only for Checkbox and v2 Checkbox.)
- Load on non-form pages for analytics purposes. (Only for Score, Policy-based, Invisible, v2 Invisible, and v3.)
- Set action names. (Only for Score, Policy-based, and v3.)
- Set score thresholds. (Only for Score and v3.)

### Supported forms

- Login.
- Registration. (Only available in a single site installation.)
- Multisite User Signup. (Only available on the main site in a multisite installation.)
- Lost Password.
- Reset Password.
- Comment.

### Data submissions

Additional data to submit to the reCAPTCHA verification server.

- Client's IP address.
- Client's user agent.*
- Client's HTTP headers.*
- Request URI.*
- User ID.*
- Username.*
- User's e-mail address.*
- User's registration date.*

\* Enterprise versions only.

### Logging
- Log reCAPTCHA's JSON response data.
	- Add the client's IP address to the JSON response data. (Legacy versions only.)
	- Remove the reCAPTCHA token from the JSON response data. (Enterprise versions only.)
- Debug logging.
	- Have a separate file from WordPress' `/wp-content/debug.log`.
	- Set a minimum required severity level that messages must have for them to be written to the log.
- Rotate interval: never, daily, monthly, yearly. Uses UTC/GMT time with a [ISO 8601](https://www.iso.org/standard/40874.html) date format.
- Specify a directory where to store the log files.

### Languages
- English (US, GB).
- Norwegian (bokmål).

### Multisite

When network activated in a multisite installation the following happens:

- The plugin's settings will only be available to the [Super Admin](https://wordpress.org/support/article/roles-and-capabilities/#super-admin) in the [Network Admin](https://wordpress.org/support/article/network-admin/).
- In the database the settings will be stored the `wp_sitemeta` table. This is separate from any sub-sites' settings, which are stored in `wp_<siteID>_options`.
- Settings set by the Super Admin will apply across all sites.
