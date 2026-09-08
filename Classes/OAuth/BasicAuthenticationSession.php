<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\OAuth;

use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Marks an anonymous TYPO3 session as DocCheck-authenticated without creating a fe_user.
 */
final readonly class BasicAuthenticationSession
{
    private const SESSION_KEY = 'oauth2_doccheck_typo3.basic_authenticated';

    public function __construct(private FrontendUserAuthentication $frontendUser) {}

    public function establish(): void
    {
        $this->frontendUser->setAndSaveSessionData(self::SESSION_KEY, true);
    }

    public function isAuthenticated(): bool
    {
        return $this->frontendUser->getSessionData(self::SESSION_KEY) === true;
    }

    public function clear(): void
    {
        $this->frontendUser->setAndSaveSessionData(self::SESSION_KEY, null);
    }
}
