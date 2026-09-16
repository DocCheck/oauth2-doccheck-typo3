<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\ViewHelpers;

use DocCheck\OAuth2DocCheckTypo3\Access\ProtectedAccessService;
use DocCheck\OAuth2DocCheckTypo3\Login\LoginButtonRenderer;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/** Renders an in-layout login notice for an unauthenticated protected page. */
final class ProtectedPageNoticeViewHelper extends AbstractViewHelper
{
    public function __construct(
        private readonly ProtectedAccessService $protectedAccessService,
        private readonly LoginButtonRenderer $loginButtonRenderer,
    ) {}

    public function render(): string
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if (!$request instanceof ServerRequestInterface
            || !$this->protectedAccessService->isCurrentPageProtected($request)
            || $this->protectedAccessService->isAuthenticated($request)) {
            return '';
        }

        return sprintf(
            '<section class="login-panel doccheck-access-notice" role="alert"><h1>DocCheck authentication required</h1><p>Please sign in to view this page.</p>%s</section>',
            $this->loginButtonRenderer->render($request),
        );
    }
}
