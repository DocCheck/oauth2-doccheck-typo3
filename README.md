# DocCheck OAuth2 for TYPO3

TYPO3 OAuth 2.0 integration for DocCheck Access.

## Compatibility

- TYPO3 13.4 and 14.3
- PHP 8.2 through 8.5

## Documentation

- [Installation](docs/Installation.md)
- [Configuration](docs/Configuration.md)
- [Upgrade notes](UPGRADE.md)
- [Security policy](SECURITY.md)
- [Changelog](CHANGELOG.md)

## Development status

This extension is currently under development. It provides validated OAuth
configuration, the official login component, and protected pages/content.
Basic provides anonymous access after token verification. Economy and Business
can provision a minimal TYPO3 frontend-user record after consent.

The endpoints are `/doccheck/login`, `/doccheck/callback`, and
`/doccheck/logout`. Logout is a POST-only local action. It clears the
extension-owned Basic or paid session; it does not and cannot invalidate a
user’s central DocCheck login.

Responses for an authenticated extension session are excluded from TYPO3's
shared frontend page cache. This prevents protected content or an authenticated
navigation state from being served to another visitor.

Keep DocCheck client credentials exclusively in environment-specific TYPO3
configuration. Never commit credentials, authorization codes, or tokens.

## Configuration

Configure these safe values through **Settings → Extension Configuration →
oauth2_doccheck_typo3**:

- licence mode (`basic`, `economy`, or `business`)
- comma-separated minimum scopes (Economy/Business only; Basic rejects scopes)
- optional default frontend-user group UID
- enable frontend-user provisioning (Economy/Business only)
- explicit anonymous-session fallback (Economy/Business only; disabled by default)
- development diagnostic logging

Set `clientId`, `clientSecret`, and the exact registered HTTPS `redirectUri` in
environment-specific TYPO3 configuration, for example a local
`config/system/additional.php` file:

```php
$configuration = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_doccheck_typo3'] ?? [];
$configuration['clientId'] = getenv('DOCHECK_OAUTH_CLIENT_ID') ?: '';
$configuration['clientSecret'] = getenv('DOCHECK_OAUTH_CLIENT_SECRET') ?: '';
$configuration['redirectUri'] = getenv('DOCHECK_OAUTH_REDIRECT_URI') ?: '';
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_doccheck_typo3'] = $configuration;
```

The callback URI must exactly match the URI registered with DocCheck. The
extension does not provide a configurable authorization-server URL, audit-log
files, or retention settings.

## Login button

Use the included Fluid ViewHelper to render DocCheck's official web component:

```html
{namespace doccheck=DocCheck\\OAuth2DocCheckTypo3\\ViewHelpers}
<doccheck:loginButton size="medium" />
```

It loads DocCheck's documented `@latest` component script and passes only the
public client ID and the configured redirect URI. For Economy and Business it
also creates a high-entropy, server-side, single-use transaction and renders
its `state` value plus the configured minimum scopes. For Basic it renders
neither `state` nor `scope`.

Do not substitute a hand-written link, change the rendered attributes in
JavaScript, or reuse markup from another client. The redirect URI must be the
one registered for the active DocCheck client. The component script is
`https://dccdn.de/static.doccheck.com/components/login-button/@latest/main.js`.

**Important:** the button cannot work until all required client configuration is
present: a configured active DocCheck Access client, server-side `clientId` and
`clientSecret`, and an exactly matching HTTPS `redirectUri`.

The extension also registers an editor-facing **DocCheck Access login button**
content element. Include the extension's static TypoScript template before
using it. Any page or content element can be marked **Require DocCheck
authentication**; unauthenticated users receive a 403 page with the same login
component, while protected content is omitted from the response.

The supplied content element is deliberately rendered as TYPO3 `COA_INT`, so a
paid-licence `state` belongs to the browser that receives the button rather
than to the shared page cache. If the Fluid ViewHelper is embedded in a custom
site template, render that fragment as `USER_INT`/`COA_INT` as well. Do not
place a paid-licence login button in a cacheable Fluid page template.

## Local logout and session-status element

The extension includes Fluid ViewHelpers for an explicit local logout page:

```html
{namespace doccheck=DocCheck\\OAuth2DocCheckTypo3\\ViewHelpers}
<doccheck:sessionStatus />
<doccheck:logoutButton returnPath="/login" />
```

`sessionStatus` is an end-user-readable diagnostic element. It reports the
current licence configuration (Basic, Economy, Business, or incomplete) and
whether the current browser has an extension-owned DocCheck session, but never exposes
OAuth tokens or passwords. For a provisioned Economy/Business identity it may
show the small, allow-listed profile values returned for that consented login;
they exist only in the local session and are cleared by logout. Basic shows no
profile data. `logoutButton` submits a POST request with a one-time local
session token to clear that local session and redirects to the supplied safe
local path. When no local DocCheck session exists, it renders a link to that
login path instead of a logout form. A rejected logout POST returns to its
`failurePath` (default `/logout`), where `<doccheck:logoutNotice />` consumes a
short-lived local status flag and renders a safe, end-user-readable message.

All three session-aware ViewHelpers (`sessionStatus`, `logoutButton`, and
`logoutNotice`) must be rendered in a `USER_INT`/`COA_INT` fragment, or on a
page with TYPO3 page caching disabled. They must never be embedded directly in
a shared cached Fluid page template: the logout token and displayed local
session state belong to one browser only.

## Licence and session model

The intended first implementation distinguishes DocCheck licence products:

- **Basic** uses an anonymous, extension-owned DocCheck session after a
  successful token exchange. It sends neither `state` nor `scope`, requests no
  user data, and does not create a TYPO3 frontend user.
- **Economy/Business** provisions or maps a minimal TYPO3 frontend-user record
  from the permitted `unique_id` scope only when provisioning is explicitly
  enabled. It stores no OAuth token. The optional status element retains only a
  short, allow-listed profile summary in the local browser session until logout.
  An anonymous fallback is a separate opt-in setting and remains disabled by
  default.

The authorization-request foundation enforces the protocol distinction now:
Basic authorization URLs omit both `state` and `scope`; Economy/Business URLs
require caller-supplied state and include only the configured scopes. The latter
will be stored server-side as a high-entropy, single-use transaction with a
five-minute expiry in the anonymous TYPO3 frontend session. A transaction is
removed before validation, including on an invalid callback, so it cannot be
replayed.

## Licence

This extension is licensed under the GNU General Public License, version 2 or
later. Copyright © 2026, DocCheck agency AG, Köln, Deutschland. See
[LICENSE](LICENSE) and [NOTICE](NOTICE).
