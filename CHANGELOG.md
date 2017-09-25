Changelog
=========
## Unreleased
### Changed
- Registering twig variables using latest Craft event.
- Explicitly calling `->all()` on Query objects
- Removed task in favor of queueing job for localization relationships

## 1.0.0-beta.9 - 2017-7-27
### Fixed
- Issue where field layout would get deleted without saving a new one.
- Issue where type specific field layout was not getting saved.

## 1.0.0-beta.8 - 2017-7-25
### Fixed
- Bug with User validator not returning a proper response.

## 1.0.0-beta.7 - 2017-7-13
### Added
- Logging when unable to save Organization

## 1.0.0-beta.6 - 2017-7-5
### Fixed
- Issues where an organization could not be deleted via the element index action
- Issues where an organization could not be deleted via the element action
- Issues where an organization status could not be changed via the element index action

### Added
- Prompt to transfer organization user to new organization upon deletion via element index action

## 1.0.0-beta.5 - 2017-6-5
### Changed
- Changed icons
- Required owner is determined via settings

## 1.0.0-beta.4 - 2017-05-17
### Changed
- Changed base plugin class to 'Organization' in favor of semantic usage
- Cleaned up controller references to modules and sub-modules

## 1.0.0-beta.3 - 2017-05-15
### Changed
- Fixed issue w/ status migration not loading

## 1.0.0-beta.2 - 2017-05-15
### Changed
- Updated Spark library to v2.0.0

## 1.0.0-beta.1 - 2017-05-15
### Changed
- Updated Spark library to v1.1.0

## 1.0.0-beta - 2017-04-12
Initial release.
