# Changelog

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
## First release!
- Supports reCAPTCHA versions v2 Checkbox, v2 Invisible, and v3. Customisation of all versions.
