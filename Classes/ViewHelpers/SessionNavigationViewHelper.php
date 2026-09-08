<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\ViewHelpers;

use DocCheck\OAuth2DocCheckTypo3\Access\AuthenticatedSession;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/** Renders Login or Logout based solely on the extension-owned local session. */
final class SessionNavigationViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('loginPath', 'string', 'Safe local login path.', false, '/login');
        $this->registerArgument('logoutPath', 'string', 'Safe local logout-page path.', false, '/logout');
    }

    public function render(): string
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        $frontendUser = $request instanceof ServerRequestInterface ? $request->getAttribute('frontend.user') : null;
        $authenticated = $frontendUser instanceof FrontendUserAuthentication && (new AuthenticatedSession($frontendUser))->isAuthenticated();
        $path = $authenticated ? $this->arguments['logoutPath'] : $this->arguments['loginPath'];
        $path = is_string($path) && $this->safeLocalPath($path) ? $path : ($authenticated ? '/logout' : '/login');

        return sprintf('<a href="%s">%s</a>', htmlspecialchars($path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), $authenticated ? 'Logout' : 'Login');
    }

    private function safeLocalPath(string $path): bool
    {
        return str_starts_with($path, '/') && !str_starts_with($path, '//') && !str_contains($path, "\r") && !str_contains($path, "\n");
    }
}
