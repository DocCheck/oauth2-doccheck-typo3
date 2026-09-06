<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Middleware;

use DocCheck\OAuth2DocCheckTypo3\Access\AuthenticatedSession;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;

/**
 * Prevents an authenticated DocCheck response from entering TYPO3's shared page cache.
 */
final class DisableAuthenticatedSessionCacheMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $frontendUser = $request->getAttribute('frontend.user');
        if ($frontendUser instanceof FrontendUserAuthentication && (new AuthenticatedSession($frontendUser))->isAuthenticated()) {
            $cacheInstruction = $request->getAttribute('frontend.cache.instruction', new CacheInstruction());
            if ($cacheInstruction instanceof CacheInstruction) {
                $cacheInstruction->disableCache('EXT:oauth2_doccheck_typo3: Authenticated DocCheck responses must not enter the shared frontend cache.');
                $request = $request->withAttribute('frontend.cache.instruction', $cacheInstruction);
            }
        }

        return $handler->handle($request);
    }
}
