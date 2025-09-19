# Changelog

## [1.1.5] - 2025-09-19
### Changed
- Fixed comment approval bug introduced in WordPress 6.7 where the function to check comment submissions gets called twice even though the comment is already approved by this plugin. This bug meant that the reCAPTHA token would be also be verified twice and leading to errors about duplicates, which lead to a rejected comment.

## [1.1.4] - 2024-03-30
### Changed
- Updated the "View changelog" page script.
	- Added HTTP header "Accept" to the cURL request to ensure that JSON data is retrieved.
	- Updated the regular expressions that clean up the HTML from GitHub.
	- Updated the CSS colour schemes.

## [1.1.3] - 2023-11-06
### Changed
- Replaced `file_exists()` with `is_file()` for the emergency stop.
- Fixed the fix from [db0296c](https://github.com/CruelDrool/WP-reCAPTCHA/commit/db0296c875310f99f71c1eaa8dba22d55c273d1a)

### Added
- Ability to view the changelog from [GitHub.com](https://github.com). The link is on the plugin's meta row on the "Plugins" page.

## [1.1.2] - 2023-10-28
### Changed
- Discovered an issue where the sanitization of the settings was happening when calling `update_option()` (`update_site_option()` when network activated on multisite) and not before. This lead to default values some times being stored in the database. The idea is to keep the stored settings free of default values.
	- Fixed it so that sanitization is done first before saving.
- Did some clean up of the settings page.
	- Setting "Load on ..." changed to "Load on all pages".
	- Section "Other" has been removed. The settings have been moved up to "General".
	- Threshold values changed from input type `select` (dropdown) to `number`.
	- Option "Secret Key" changed to input type `password`, with a toggle button to view the value.
	- Log rotations changed from input type `select` (dropdown) to `radio` to better display the different date formats.
	- Added link to an article about domain validation.
	- Made textboxes using CSS class `.regular-text` a bit wider; from 25em to 28em.
	- Added icon and screen reader text to links that open in a new tab.
- Fix a small update mess up from 1.1.1.
- Various code clean ups.
- Translations updated.

## [1.1.1] - 2023-10-01
### Changed
- Changed the default action name for MS user signup.
	- New action name: "ms_user_signup". Old default will be saved for those that started using the plugin prior to this version.
- Adjusted some debug messages' severity level.

## [1.1.0] - 2023-09-25
### Changed
- The reCAPTCHA log
	- Using GMT/UTC time for the log rotation.
	- Using operating system specific end-of-line.
- Only hook the Settings when in the admin interface.
	- It wasn't being loaded while on the frontend, but it's bit cleaner this way.
- Determining the client's IP address should be a lot accurate now.
- Enqueue the login form's CSS stylesheet (v2 checkbox) at the proper place.
- Will now verify the origin of the solution first.
- Moved plugin list's "Settings" link.
	- New location of the settings link is on the meta row.
	- Also added "Visit plugin site" link. WordPress isn't adding it automatically.
- Updated translations
	- Attmept to fix some grammar mistakes.
	- Clarifying some descriptions/explanations.
	- Removed all html tags from translation strings.
	- Numbered text replacements in all strings with more than one text replacement.
### Added
- New settings and functionality:
	- "Require client IP": Require that a client's IP address has been determined before submitting data to the reCAPTCHA server. An undetermined IP address will be treated as a failed CAPTCHA attempt.
	- "Disable the AJAX JavaScript from the plugin Sidebar Login". The problem is that this script does not submit the required information needed for the verification process.
	- "Add client IP address to the JSON response data"
	- "reCAPTCHA log's rotate interval": Never, Daily, Weekly, Monthly or Yearly. Uses UTC/GMT time with a ISO 8601 date format.
	- "Enable debug logging": Setting both `WP_DEBUG` and `WP_DEBUG_LOG` to `true` will automatically enable this.
	- "Seperate debug log": Write the plugin's debug log to a seperate file.
	- "Debug log's rotate interval": Never, Daily, Weekly, Monthly or Yearly. Uses UTC/GMT time with a ISO 8601 date format.
	- "Debug log's minimum level": The minimum required severity level that messages must have for them to be written to the log.
	- "Path to log directory": Specify your own directory where the log files will be stored.
- Added an emergency stop.
	- Creating a file named "disable" in the plugin's directory will stop the execution of its code.
- Added info message to action name sanitization.
	- Output a information message if the sanitized action name differs from the input name.

## [1.0.8] - 2022-11-28
### Changed
- Logging update.
	- Only show the setting for logging on the main site. (On single-site installation this is always true.)
	- Don't log sub-domains' response data in a multisite installation unless the plugin is Network Activated.

## [1.0.7] - 2022-11-28
### Changed
- Consolidated the JavaScript code that sets the theme.
- Ensure that v3 threshold values are between 0.0 and 1.0.
- Hide settings for "Registration" form when in a multisite setup
- Fixed a bug with "Reset password" form.
- Removed unneeded parameter $version from function `recaptcha_log`.
- Translations updated.

## [1.0.6] - 2022-11-08
### Changed
- Update to Config class.
	- Function `is_plugin_active_for_network()` replaced by `get_is_active_for_network()`. This new function will return the value of the new private property named `is_active_for_network` (value set in the contructor function).
	- Options now loaded into private property named `options`.
	- Function `get_option()` functionality reverted back to before commit [c5348e7](https://github.com/CruelDrool/WP-reCAPTCHA/commit/c5348e75189fe2e41a849d1d75bd13e7fb75db70).
	- Function `update_option()` will now discard options with values that are the same as default.
	- Property `defaults` changed to a constant, `DEFAULTS`.
	- Property `prefix` changed to a constant, `PREFIX`.
	- New functions: `delete_option()` and `save_options()` (private).
	- Some changes done in the Settings class to reflect some of the above changes. Inputboxes with the `placeholder` attribute are not longer empty  by default (reverting change from commit [c5348e7](https://github.com/CruelDrool/WP-reCAPTCHA/commit/c5348e75189fe2e41a849d1d75bd13e7fb75db70)).
- Options "Theme", "Size" and "Placement" can now be set to "Automatic".
	- The override options that previously did this have been removed. Their values, if set, will converted to reflect the change.
- Fixed missing i18n formatting in v3 threshold description.
- Option "Remove stylesheet (CSS)" renamed.
	- New name is "Add stylesheet (CSS)".
- Added a sanitization callback function to v3 threshold values.
	- This ensures that the values are of type double before being stored in the database.
- Misc. code tweaks.
- Translations updated.

### Added
- Logging feature added.
	- When enabled it will log the JSON response data to a file in `wp-content` directory. [JSON Lines](https://jsonlines.org) used as text format.

## [1.0.5] - 2022-01-30
### Changed
- "Settings saved." message was missing when Network Activated on a multisite installation.
- Inputboxes with the `placeholder` attribute are now empty by default.
    - Custom error messages and action names are still possible.
- Misc. code tweaks and refactoring.
- Translations updated.

## [1.0.4] - 2021-11-24
### Changed
- Wrong text-domain was being used in 3 places.

## [1.0.3] - 2021-07-24
### Changed
- Tweaked the function that checks for new updates.
    - It now takes into account other plugins which are using the same hostname as Update URI.
- v3 settings for thresholds were being ignored.
    - A setting not displayed in the Admin page was being used.

## [1.0.2] - 2021-07-24
### Changed
- Norwegian Bokmål translation updated.

### Added
- Added a changelog.

## [1.0.1] - 2021-07-23
### Changed
- Turn off autocomplete on input fields.

## [1.0.0] - 2021-07-23
### First release!
- Supports reCAPTCHA versions v2 Checkbox, v2 Invisible, and v3. Customisation of all versions.
