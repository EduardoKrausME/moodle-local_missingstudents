# Changelog

This changelog documents only the two latest published releases of `local_missingstudents`, verified on October 24, 2026. Each entry reflects the published Git tag compared against the immediately preceding release tag. The subsequent October 20, 2026 `Mustache` and `Build` commits are excluded.

## [1.1.7] - 2026-10-15

### Release metadata
- Updated the version and release metadata in `version.php` from `1.1.6` to `1.1.7` (Moodle plugin version `2026101501`).
- No other files changed between the published `1.1.6` and `1.1.7` tags.

**Full comparison:** [1.1.6...1.1.7](https://github.com/EduardoKrausME/moodle-local_missingstudents/compare/1.1.6...1.1.7)

## [1.1.6] - 2026-10-15

### Fixed
- Normalized the course's Separate groups mode value to an integer before comparing it with `SEPARATEGROUPS` in `classes/report_service.php`. This retains group-based restrictions when a database driver returns the group mode as a numeric string.
- Updated `templates/course_list.mustache` to render report-link URLs through the triple-Mustache form `{{{url}}}`, preserving the provided URL without HTML escaping.

### Tests
- Expanded Separate groups regression coverage in `tests/report_service_test.php` to test string-typed group mode, verify that the reporting user lacks `moodle/site:accessallgroups`, validate the student identity included in the CSV export, and ensure a user without permitted group memberships receives no report or export rows.

### CI maintenance
- Updated the PostgreSQL CI service image from PostgreSQL 16 to 17.
- Updated the MariaDB CI service from version 10 to 11.4, adjusting database environment variables and using the MariaDB `healthcheck.sh --connect --innodb_initialized` health check.

### Release metadata
- Updated the version and release metadata in `version.php` from `1.1.5` to `1.1.6` (Moodle plugin version `2026101500`).

**Full comparison:** [1.1.5...1.1.6](https://github.com/EduardoKrausME/moodle-local_missingstudents/compare/1.1.5...1.1.6)
