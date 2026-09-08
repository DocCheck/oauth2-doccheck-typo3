<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\ViewHelpers;

use DocCheck\OAuth2DocCheckTypo3\Access\AuthenticatedSession;
use DocCheck\OAuth2DocCheckTypo3\Configuration\DocCheckConfigurationProvider;
use DocCheck\OAuth2DocCheckTypo3\Login\SessionStatusRenderer;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/** Renders a token-free diagnostic summary for the current local session. */
final class SessionStatusViewHelper extends AbstractViewHelper
{
    public function __construct(
        private readonly DocCheckConfigurationProvider $configurationProvider,
        private readonly SessionStatusRenderer $renderer,
    ) {}

    public function render(): string
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        $frontendUser = $request instanceof ServerRequestInterface ? $request->getAttribute('frontend.user') : null;
        $status = $frontendUser instanceof FrontendUserAuthentication
            ? (new AuthenticatedSession($frontendUser))->status()
            : ['authenticated' => false, 'mode' => 'none'];

        $licenseMode = null;
        try {
            $licenseMode = $this->configurationProvider->create()->licenseMode();
        } catch (\InvalidArgumentException) {
            // The status element must still explain an incomplete local setup.
        }

        return $this->renderer->render($status, $licenseMode);
    }
}
