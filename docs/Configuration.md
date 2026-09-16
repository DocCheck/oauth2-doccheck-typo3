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
| `requestedScopes` | empty | Comma-separated minimum scopes for Economy/Business. Leave empty for Basic; provisioning requires `unique_id`. |
| `defaultFrontendUserGroup` | `0` | Optional TYPO3 frontend-user group for provisioned identities; `0` assigns none. |
| `enableFrontendUserProvisioning` | `0` | Enables Economy/Business frontend-user provisioning. Requires `unique_id`. |
| `profileFieldMapping` | empty | Optional Business-only list: `name`, `email`. Requires provisioning and the corresponding requested scopes. |
| `profileFieldSync` | `create_only` | The only supported policy. Writes approved fields only while creating a new FE user; never overwrites an existing local field. |
| `allowAnonymousSessionFallback` | `0` | Allows a paid anonymous session only when provisioning cannot complete. Keep disabled unless this fallback is intentional. |
| `debugLogging` | `0` | Enables development-only scope-response diagnostics. OAuth credentials, tokens, and profile values are not logged by the extension. |

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

## Scope reference

`requestedScopes` contains DocCheck scope identifiers, not response field
names. The extension has one central scope catalogue that validates the licence
mode and maps each requested scope to its documented DocCheck user-data fields.
Do not configure `profession_id`, `first_name`, or any other response field as
a scope.

| Scope | DocCheck user-data fields | Typed extension value | Economy | Business |
| --- | --- | --- | --- | --- |
| `unique_id` | `unique_id` | `uniqueId` | yes | yes |
| `profession` | `profession_id` | `professionId` | yes | yes |
| `country` | `country_iso_code`, `country_id` | `countryIsoCode`, `countryId` | yes | yes |
| `language` | `user_language` | `language` | yes | yes |
| `name` | `first_name`, `last_name` | `firstName`, `lastName` | no | yes |
| `email` | `email` | `email` | no | yes |
| `address` | `area_code`, `street`, `city`, `country_iso_code` | `address` | no | yes |
| `occupation_detail` | `discipline_id`, `activity_id` | `disciplineId`, `activityId` | no | yes |

The scope catalogue is implemented in `DocCheckScope`; this table is the
administrator-facing reference for that catalogue. `country_iso_code` is
intentionally shared by the `country` and `address` mappings.

Only `unique_id` is persisted by default as the stable DocCheck key on a
provisioned `fe_users` record. Other values are typed only for the current,
consented response and may be included in the token-free local session
diagnostic. They are not automatically mapped to TYPO3 standard frontend-user
fields.

## Optional FE-user profile fields

`profileFieldMapping` is a separate persistence decision; requesting a scope
alone never stores its value in `fe_users`. Its accepted values are semantic
mapping names, not TYPO3 field names:

| Mapping | Required scope | FE-user fields written for a newly created record |
| --- | --- | --- |
| `name` | `name` | `first_name`, `last_name` |
| `email` | `email` | `email` |

The setting is available only with a Business licence, enabled frontend-user
provisioning, and the corresponding requested scope. For example:

```php
'requestedScopes' => 'unique_id,name,email',
'enableFrontendUserProvisioning' => true,
'profileFieldMapping' => 'name,email',
'profileFieldSync' => 'create_only',
```

`create_only` is intentionally the only synchronization policy. Existing FE
users are always found by `unique_id`, never email, and their locally maintained
name or email is never overwritten by a later DocCheck login. Enabling this
setting does not backfill existing records. Values such as address, country,
language, profession, and occupation detail are not persistable through this
setting and are rejected as configuration values.

When `debugLogging` is enabled, an incomplete scope response produces a TYPO3
log warning containing only the scope identifier, status (`missing`, `partial`,
or `invalid`), and missing API field names. It never logs response values,
OAuth tokens, credentials, or callback parameters. A diagnostic is not a
consent decision; the login can continue when its required identity data is
available. Missing `unique_id` prevents provisioning.

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
4. Basic has no requested scopes; paid modes use only permitted minimum scopes
   from the scope reference and match the scopes enabled for the DocCheck client.
5. If `profileFieldMapping` is enabled, it uses only `name` and/or `email`, and
   their matching Business scopes are requested.
6. Verify paid-mode login and logout in a non-production environment after a
   configuration change.
