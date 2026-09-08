<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Middleware;

use DocCheck\OAuth2DocCheckTypo3\Access\ProtectedAccessService;
use DocCheck\OAuth2DocCheckTypo3\Login\LoginButtonRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;

final readonly class ProtectedPageMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ProtectedAccessService $protectedAccessService,
        private LoginButtonRenderer $loginButtonRenderer,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->protectedAccessService->isCurrentPageProtected($request)
            || $this->protectedAccessService->isAuthenticated($request)) {
            return $handler->handle($request);
        }

        return new HtmlResponse(sprintf(
            '<!doctype html><html lang="en"><body><main><h1>DocCheck authentication required</h1><p>Please sign in to view this page.</p>%s</main></body></html>',
            $this->loginButtonRenderer->render($request),
        ), 403);
    }
}
