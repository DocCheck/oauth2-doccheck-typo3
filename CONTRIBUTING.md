# Contributing

Thank you for improving DocCheck OAuth2 for TYPO3.

- Keep changes compatible with TYPO3 13.4/14.3 and PHP 8.2–8.5.
- Use `composer ci` before proposing a release-affecting change.
- Add or update unit/functional coverage for changed OAuth behaviour. Tests
  must use fakes and must not make live DocCheck calls.
- Preserve the licence rules: Basic omits `state` and `scope`; Economy/Business
  require a verified, single-use server-side `state` and minimum scopes.
- Never commit credentials, authorization codes, tokens, session data, profile
  data, or customer callback URIs.

Use [SECURITY.md](SECURITY.md) for security reports rather than public issues.
