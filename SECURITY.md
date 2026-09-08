# Security policy

## Supported versions

Security fixes are provided for the current 0.8.x beta release line while it
remains the documented public release line. Install the latest compatible patch release
promptly.

## Reporting a vulnerability

Use GitHub's private vulnerability-reporting facility for this repository when
it is available. If it is not enabled, contact DocCheck through an established
security contact rather than opening a public issue.

Do not include client secrets, authorization codes, access tokens, refresh
tokens, session cookies, personal profile data, or customer callback URIs in a
report. Include the extension version, TYPO3/PHP versions, impact, and concise
reproduction steps instead.

## Deployment responsibilities

Keep the client secret in deployment-specific TYPO3 configuration. Register an
exact HTTPS callback URI, request only necessary paid-licence scopes, and do
not cache session-aware Fluid fragments. The extension does not persist OAuth
tokens; deployments must keep this property intact.
