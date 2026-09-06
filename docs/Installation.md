# Installation

## Requirements

- TYPO3 13.4 or 14.3
- PHP 8.2 through 8.5
- A DocCheck Access client with an HTTPS callback URI registered for the site

Install the extension in a Composer-managed TYPO3 project:

```bash
composer require doccheck/oauth2-doccheck-typo3
vendor/bin/typo3 extension:setup
```

Apply the extension database schema with the TYPO3 Install Tool's **Database
Analyzer**, then flush caches:

```bash
vendor/bin/typo3 cache:flush
```

## TYPO3 setup

1. Include the static TypoScript template **DocCheck OAuth2 for TYPO3** in the
   site template.
2. Configure the licence-specific safe settings in **Settings → Extension
   Configuration → oauth2_doccheck_typo3**.
3. Provide the client ID, client secret, and exact HTTPS callback URI through
   environment-specific TYPO3 configuration, as described in
   [Configuration](Configuration.md).
4. Register the same callback URI with the DocCheck Access client. The route is
   `/doccheck/callback`; its full scheme, host, path, and trailing slash usage
   must match exactly.
5. Add either the **DocCheck Access login button** content element or the
   `doccheck:loginButton` Fluid ViewHelper to an accessible login page.

Use a fresh browser session for the first smoke test. The login page must be
served over HTTPS and must not be shared-cached when it renders a paid-licence
login button.

## Protecting content

After the login page works:

- Enable **Require DocCheck authentication** on a page to require a local
  DocCheck session for the whole page.
- Enable the same field on an individual content element to omit that element
  for unauthenticated visitors.

For a local logout page, use the session-aware ViewHelpers shown in the main
README. Render that fragment as `USER_INT` or `COA_INT`, or disable page caching
for that page.
