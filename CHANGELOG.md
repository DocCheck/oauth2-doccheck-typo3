# Changelog

All notable changes to this extension are documented in this file. The format
follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and versions
follow [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

## 0.8.0-beta2 - 2026-09-16

### Added

- A TYPO3 Site Set that loads the DocCheck content-element TypoScript for
  site-based configuration.
- Optional, Business-only create-time mappings of consented `name` and `email`
  values to newly provisioned TYPO3 frontend users. Existing local profile
  fields are never overwritten and email is never used as an identity key.

### Fixed

- Render unauthenticated protected-page notices inside the site's normal Fluid
  layout and navigation, while removing every protected-page content record.
- Map every supported DocCheck scope to its documented user-data fields instead
  of treating scope identifiers as response-field names. Incomplete, partial,
  and invalid responses can be diagnosed without logging profile values.

## 0.8.0-beta1 - 2026-09-08

### Added

- Composer installation and configuration documentation.
- Basic, Economy, and Business licence-mode handling.
- Official DocCheck login-button integration, protected pages/content, local
  logout, and token-free session-status diagnostics.
- Editor-facing diagnostic session-status content element with token-free licence
  and local-session details.

### Security

- Server-side, single-use state validation for Economy and Business callbacks.
- Safe local redirect handling and token-free local session storage.
