# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.2]  - 2020-09-27

### Fixed

- Fixed missing Storage::write type declaration for value

## [2.0.0] - 2020-09-26

### Changed

- Changed `list` return data, `name` now represents the `filename`, and added `path` which includes the directory and filename. Use `path` instead of `name`. (Breaking Change)

## [1.4.0] - 2020-09-26

### Added

- Added FileObject::__toString which returns the path e.g. /folder/example.png

## [1.3.1] - 2020-09-03

### Fixed

- Fixed clearstatcache arguments

## [1.3.0] - 2020-09-03

### Added

- Added better lock support using flock for `LocalEngine`, this can also be disabled through config.

### Changed

- Changed directory mask when creating from 744 to 755

## [1.2.1] - 2020-07-23

### Fixed

- Fixed operators

## [1.2.0] - 2020-07-07

### Added

- Added S3 Engine

## [1.1.0] - 2019-12-15

### Added

- Added Memory Engine
- Added Zip Engine

## [1.0.1] - 2019-10-31

### Changed

- Changed Storage::list file result can be accessed as both array and object (FileObject)

### Fixed

- Fixed tests

## [1.0.0] - 2019-10-25

This component has been decoupled from the [OriginPHP framework](https://www.originphp.com/).