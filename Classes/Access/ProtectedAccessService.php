<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Access;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Page\PageInformation;

final class ProtectedAccessService
{
    public function isAuthenticated(ServerRequestInterface $request): bool
    {
        $frontendUser = $request->getAttribute('frontend.user');

        return $frontendUser instanceof FrontendUserAuthentication
            && (new AuthenticatedSession($frontendUser))->isAuthenticated();
    }

    public function isCurrentPageProtected(ServerRequestInterface $request): bool
    {
        $pageInformation = $request->getAttribute('frontend.page.information');

        return $pageInformation instanceof PageInformation
            && (bool)($pageInformation->getPageRecord()['tx_oauth2docchecktypo3_requires_auth'] ?? false);
    }

    /**
     * @param array<array-key, mixed> $contentRecord
     */
    public function isContentVisible(array $contentRecord, ServerRequestInterface $request): bool
    {
        return empty($contentRecord['tx_oauth2docchecktypo3_requires_auth'])
            || $this->isAuthenticated($request);
    }
}
