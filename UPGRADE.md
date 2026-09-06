# Upgrade notes

## Upgrading to 0.1.x

This is the first public release line; there is no supported upgrade path from
an earlier version of this package.

Before deploying a new 0.1.x version:

1. Back up the TYPO3 database and deployed environment configuration.
2. Run `composer update doccheck/oauth2-doccheck-typo3`.
3. Apply database changes with the TYPO3 Install Tool's **Database Analyzer**.
4. Flush TYPO3 caches.
5. Confirm that the configured callback URI still exactly matches the active
   DocCheck client registration.
6. Test Basic or the selected paid licence mode in a fresh browser session:
   login, callback, protected content, and local logout.

Do not migrate or copy OAuth transactions, local DocCheck sessions, access
tokens, refresh tokens, or browser cookies between environments. A deployment
may invalidate an in-flight paid-mode transaction; users should start a new
login from the login page in that case.

## Configuration changes

Review [docs/Configuration.md](docs/Configuration.md) at every upgrade.
In particular, do not enable anonymous-session fallback merely to hide a
provisioning error: it intentionally changes the access decision for paid
licences.
