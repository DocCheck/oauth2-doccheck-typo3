# Changelog

All notable changes to this extension are documented in this file. The format
follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and versions
follow [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

## 0.8.0-beta - 2026-09-08

### Added

- Composer installation and configuration documentation.
- Basic, Economy, and Business licence-mode handling.
- Official DocCheck login-button integration, protected pages/content, local
- Editor-facing diagnostic session-status content element with token-free licence and local-session details.
  logout, and token-free session-status diagnostics.
- Callback functional tests using a fake provider.
- GitHub Actions compatibility matrix for TYPO3 13.4/14.3 and PHP 8.2–8.5.

### Security

- Server-side, single-use state validation for Economy and Business callbacks.
- Safe local redirect handling and token-free local session storage.
