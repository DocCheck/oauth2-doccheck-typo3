<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\ViewHelpers;

use DocCheck\OAuth2DocCheckTypo3\Login\LoginButtonRenderer;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Renders the official DocCheck Access login web component.
 *
 * GeneralUtility is used only to bridge Fluid's ViewHelper lifecycle to the
 * TYPO3 dependency-injection container.
 */
final class LoginButtonViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('size', 'string', 'Button size: small, medium, or large.', false, 'medium');
        $this->registerArgument('language', 'string', 'Two-letter DocCheck button language; defaults to the site language.', false, '');
        $this->registerArgument('returnPath', 'string', 'Safe local path to open after login; defaults to the current path.', false, '');
    }

    public function render(): string
    {
        /** @var LoginButtonRenderer $renderer */
        $renderer = GeneralUtility::getContainer()->get(LoginButtonRenderer::class);
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if (!$request instanceof ServerRequestInterface) {
            return '';
        }

        $size = $this->arguments['size'] ?? 'medium';
        $language = $this->arguments['language'] ?? '';
        $returnPath = $this->arguments['returnPath'] ?? '';

        return $renderer->render($request, [
            'size' => is_string($size) ? $size : 'medium',
            'language' => is_string($language) ? $language : '',
            'returnPath' => is_string($returnPath) ? $returnPath : '',
        ]);
    }
}
