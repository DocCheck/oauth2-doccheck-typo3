<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\ViewHelpers;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/** Renders a safe, styled-page logout failure notice after a rejected POST. */
final class LogoutNoticeViewHelper extends AbstractViewHelper
{
    public function render(): string
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        $frontendUser = $request instanceof ServerRequestInterface ? $request->getAttribute('frontend.user') : null;
        if (!$frontendUser instanceof \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication
            || !(new \DocCheck\OAuth2DocCheckTypo3\Access\AuthenticatedSession($frontendUser))->consumeLogoutFailure()
        ) {
            return '';
        }

        return '<p class="doccheck-logout-notice" role="alert">DocCheck logout could not be completed. Please try again.</p>';
    }
}
