<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\ViewHelpers;

use DocCheck\OAuth2DocCheckTypo3\Access\AuthenticatedSession;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/** Renders a POST-only action that clears the local extension session. */
final class LogoutButtonViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('returnPath', 'string', 'Safe local path to open after logout.', false, '/login');
        $this->registerArgument('failurePath', 'string', 'Safe local path to open when logout cannot be completed.', false, '/logout');
    }

    public function render(): string
    {
        $returnPath = $this->arguments['returnPath'] ?? '/login';
        $returnPath = is_string($returnPath) && $this->safeLocalPath($returnPath) ? $returnPath : '/login';
        $failurePath = $this->arguments['failurePath'] ?? '/logout';
        $failurePath = is_string($failurePath) && $this->safeLocalPath($failurePath) ? $failurePath : '/logout';
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        $frontendUser = $request instanceof ServerRequestInterface ? $request->getAttribute('frontend.user') : null;
        if (!$frontendUser instanceof FrontendUserAuthentication) {
            return '<p role="alert">DocCheck logout is unavailable because the local session could not be loaded.</p>';
        }
        $session = new AuthenticatedSession($frontendUser);
        if (!$session->isAuthenticated()) {
            return sprintf(
                '<p><a class="doccheck-login-link" href="%s">Sign in with DocCheck</a></p>',
                htmlspecialchars($returnPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            );
        }
        $logoutToken = $session->logoutToken();

        return sprintf(
            '<form method="post" action="/doccheck/logout?return=%s&amp;failure=%s"><input type="hidden" name="logoutToken" value="%s"><button class="doccheck-logout-button" type="submit">Log out</button></form>',
            rawurlencode($returnPath),
            rawurlencode($failurePath),
            htmlspecialchars($logoutToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        );
    }

    private function safeLocalPath(string $path): bool
    {
        return str_starts_with($path, '/') && !str_starts_with($path, '//') && !str_contains($path, "\r") && !str_contains($path, "\n");
    }
}
