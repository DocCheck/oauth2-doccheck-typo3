<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Middleware;

use DocCheck\OAuth2DocCheckTypo3\Access\AuthenticatedSession;
use DocCheck\OAuth2DocCheckTypo3\Configuration\DocCheckConfigurationProvider;
use DocCheck\OAuth2DocCheckTypo3\Identity\IdentityEstablisher;
use DocCheck\OAuth2DocCheckTypo3\Identity\UserDataFetcher;
use DocCheck\OAuth2DocCheckTypo3\OAuth\AuthorizationUrlGenerator;
use DocCheck\OAuth2DocCheckTypo3\OAuth\FrontendSessionOAuthTransactionStore;
use DocCheck\OAuth2DocCheckTypo3\OAuth\OAuthTransactionFactory;
use DocCheck\OAuth2DocCheckTypo3\OAuth\TokenExchanger;
use DocCheck\OAuth2DocCheckTypo3\Presentation\InformationPageRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

final readonly class DocCheckOAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private DocCheckConfigurationProvider $configurationFactory,
        private AuthorizationUrlGenerator $authorizationService,
        private OAuthTransactionFactory $transactionFactory,
        private TokenExchanger $tokenExchangeService,
        private UserDataFetcher $userDataService,
        private IdentityEstablisher $identityService,
        private InformationPageRenderer $informationPageRenderer,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        if (!in_array($path, ['/doccheck/login', '/doccheck/callback', '/doccheck/logout'], true)) {
            return $handler->handle($request);
        }

        $frontendUser = $request->getAttribute('frontend.user');
        if (!$frontendUser instanceof FrontendUserAuthentication) {
            return $this->informationPageRenderer->render('DocCheck Login is unavailable.', 503);
        }

        try {
            $configuration = $this->configurationFactory->create();
            return match ($path) {
                '/doccheck/login' => $this->startLogin($request, $configuration, $frontendUser),
                '/doccheck/logout' => $this->logout($request, $frontendUser),
                default => $this->handleCallback($request, $configuration, $frontendUser),
            };
        } catch (\Throwable) {
            return $this->informationPageRenderer->render('DocCheck Login could not be completed. Please try again later.', 400);
        }
    }

    private function startLogin(ServerRequestInterface $request, \DocCheck\OAuth2DocCheckTypo3\Configuration\DocCheckConfiguration $configuration, FrontendUserAuthentication $frontendUser): ResponseInterface
    {
        if ($configuration->licenseMode() === 'basic') {
            return new RedirectResponse($this->authorizationService->createAuthorizationUrl($configuration));
        }

        $returnPath = $this->safeReturnPath($request->getQueryParams()['return'] ?? '/');
        $transaction = $this->transactionFactory->create($returnPath, new \DateTimeImmutable());
        (new FrontendSessionOAuthTransactionStore($frontendUser))->save($transaction);

        return new RedirectResponse($this->authorizationService->createAuthorizationUrl($configuration, $transaction->state()));
    }

    private function handleCallback(ServerRequestInterface $request, \DocCheck\OAuth2DocCheckTypo3\Configuration\DocCheckConfiguration $configuration, FrontendUserAuthentication $frontendUser): ResponseInterface
    {
        $parameters = $request->getQueryParams();
        $returnPath = '/';
        if ($configuration->licenseMode() !== 'basic') {
            $transactionStore = new FrontendSessionOAuthTransactionStore($frontendUser);
            if (!is_string($parameters['state'] ?? null) || $parameters['state'] === '') {
                $transactionStore->clear();
                return $this->informationPageRenderer->render('DocCheck Login callback did not include the required security state. Start a new login from the login page.', 400);
            }
            $transaction = $transactionStore->consume($parameters['state']);
            if ($transaction === null) {
                return $this->informationPageRenderer->render('The DocCheck login transaction expired or was replaced. Start a new login from the login page.', 400);
            }
            if (isset($parameters['error']) || !is_string($parameters['code'] ?? null) || $parameters['code'] === '' || !$transaction->matches($parameters['state'], new \DateTimeImmutable())) {
                return $this->informationPageRenderer->render('DocCheck Login was cancelled or could not be completed.', 400);
            }
            $returnPath = $transaction->returnPath();
        } elseif (isset($parameters['error']) || !is_string($parameters['code'] ?? null) || $parameters['code'] === '') {
            return $this->informationPageRenderer->render('DocCheck Login was cancelled or could not be completed.', 400);
        }

        try {
            $accessToken = $this->tokenExchangeService->exchange($configuration, $parameters['code']);
        } catch (\Throwable) {
            return $this->informationPageRenderer->render('DocCheck Login could not verify the authorization response. Check the active client and exact callback URI, then try again.', 400);
        }
        if ($configuration->licenseMode() !== 'basic') {
            if (!$configuration->isFrontendUserProvisioningEnabled()) {
                if (!$configuration->isAnonymousSessionFallbackAllowed()) {
                    return $this->informationPageRenderer->render('DocCheck Login requires enabled frontend-user provisioning.', 503);
                }
                (new AuthenticatedSession($frontendUser))->establishAnonymousPaidSession();

                return new RedirectResponse($returnPath);
            }

            try {
                $userData = $this->userDataService->fetch($configuration, $accessToken);
            } catch (\Throwable) {
                return $this->informationPageRenderer->render('DocCheck Login could not retrieve the consented profile data. Check the selected licence scopes and consent, then try again.', 400);
            }
            $site = $request->getAttribute('site');
            $storagePid = $site instanceof \TYPO3\CMS\Core\Site\Entity\Site ? $site->getRootPageId() : 0;
            try {
                $this->identityService->establish($userData, $configuration, $frontendUser, $storagePid);
            } catch (\Throwable) {
                return $this->informationPageRenderer->render('DocCheck Login verified your profile but could not establish the local DocCheck session. Please try again.', 500);
            }

            return new RedirectResponse($returnPath);
        }

        (new \DocCheck\OAuth2DocCheckTypo3\OAuth\BasicAuthenticationSession($frontendUser))->establish();
        return new RedirectResponse($returnPath);
    }

    private function safeReturnPath(mixed $value): string
    {
        if (!is_string($value) || !str_starts_with($value, '/') || str_starts_with($value, '//') || str_contains($value, "\r") || str_contains($value, "\n")) {
            return '/';
        }

        return $value;
    }

    private function logout(ServerRequestInterface $request, FrontendUserAuthentication $frontendUser): ResponseInterface
    {
        if (strtoupper($request->getMethod()) !== 'POST') {
            return new RedirectResponse('/logout');
        }

        $parsedBody = $request->getParsedBody();
        $logoutToken = is_array($parsedBody) ? ($parsedBody['logoutToken'] ?? null) : null;
        $session = new AuthenticatedSession($frontendUser);
        if (!$session->hasValidLogoutToken($logoutToken)) {
            $failurePath = $this->safeReturnPath($request->getQueryParams()['failure'] ?? '/logout');
            $session->recordLogoutFailure();

            return new RedirectResponse($failurePath);
        }

        $session->clear();
        return new RedirectResponse($this->safeReturnPath($request->getQueryParams()['return'] ?? '/'));
    }
}
