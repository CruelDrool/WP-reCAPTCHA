# Changelog

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
