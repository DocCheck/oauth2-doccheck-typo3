# Configuration

Configure non-secret extension settings in **Settings → Extension Configuration
→ oauth2_doccheck_typo3**. Configure secrets only in environment-specific
TYPO3 configuration; never place them in TypoScript, Fluid templates, source
control, or browser-visible markup.

## Environment-specific client settings

For example, load values from the deployment environment in
`config/system/additional.php`:

```php
$configuration = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_doccheck_typo3'] ?? [];
$configuration['clientId'] = getenv('DOCHECK_OAUTH_CLIENT_ID') ?: '';
$configuration['clientSecret'] = getenv('DOCHECK_OAUTH_CLIENT_SECRET') ?: '';
$configuration['redirectUri'] = getenv('DOCHECK_OAUTH_REDIRECT_URI') ?: '';
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_doccheck_typo3'] = $configuration;
```

`redirectUri` must be an absolute HTTPS URI and exactly match the value
registered at DocCheck. The extension routes callbacks through
`/doccheck/callback`.

## Settings

| Setting | Default | Meaning |
| --- | --- | --- |
| `licenseMode` | `basic` | Selects `basic`, `economy`, or `business` behaviour. |
| `requestedScopes` | empty | Comma-separated minimum scopes for Economy/Business. Leave empty for Basic. |
| `defaultFrontendUserGroup` | `0` | Optional TYPO3 frontend-user group for provisioned identities; `0` assigns none. |
| `enableFrontendUserProvisioning` | `0` | Enables Economy/Business frontend-user provisioning. Requires `unique_id`. |
| `allowAnonymousSessionFallback` | `0` | Allows a paid anonymous session only when provisioning cannot complete. Keep disabled unless this fallback is intentional. |
| `debugLogging` | `0` | Enables development diagnostics. OAuth credentials and tokens are not logged by the extension. |

## Licence rules

Basic is authentication-only. It sends neither `state` nor `scope`, retrieves
no profile data, and does not create a TYPO3 frontend user.

Economy and Business use a high-entropy, single-use server-side `state` value.
Request only the scopes needed by the feature:

- Economy: `unique_id`, `profession`, `country`, `language`
- Business: the Economy scopes plus `name`, `email`, `address`, and
  `occupation_detail`

Provisioning requires `unique_id`. Profile data is requested only after a
successful token exchange and the user's consent. The extension does not store
OAuth access or refresh tokens.

## Diagnostic session-status content element

The extension provides a **DocCheck session status (diagnostic)** content
element. It is intended for an administrator-controlled debug page and renders
only the active licence mode, local DocCheck session state, and allow-listed
consented profile values. It never renders OAuth credentials, tokens, callback
parameters, or unrecognised provider data. It includes a link to [DocCheck user-data endpoint return values](https://docs.doccheck.com/login-access/oauth/endpoints/user_data_endpoint_return_values.html) for interpreting returned fields.

To prevent ordinary editors from adding it, remove
`oauth2docchecktypo3_sessionstatus` from their `tt_content.CType` choices using
Page or User TSconfig. For example, add this to the User TSconfig assigned to non-maintainer editor groups:

```typoscript
TCEFORM.tt_content.CType.removeItems := addToList(oauth2docchecktypo3_sessionstatus)
```

The element must still be placed only on an administrator-controlled, non-public debug page; editor visibility alone is not frontend access control.

## Validation checklist

Before exposing a login button, verify all of the following:

1. The correct licence mode is selected.
2. The deployed client ID and secret are present only in environment-specific
   configuration.
3. The callback URI is HTTPS and exactly matches the registered DocCheck URI.
4. Basic has no requested scopes; paid modes use only permitted minimum scopes.
5. Paid-mode login and logout are tested after switching the active profile, which clears local frontend sessions in the supplied testbeds.
