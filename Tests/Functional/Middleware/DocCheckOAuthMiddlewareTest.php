<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Tests\Functional\Middleware;

use DocCheck\OAuth2DocCheckTypo3\Access\AuthenticatedSession;
use DocCheck\OAuth2DocCheckTypo3\Configuration\DocCheckConfiguration;
use DocCheck\OAuth2DocCheckTypo3\Configuration\DocCheckConfigurationProvider;
use DocCheck\OAuth2DocCheckTypo3\Identity\IdentityEstablisher;
use DocCheck\OAuth2DocCheckTypo3\Identity\UserDataFetcher;
use DocCheck\OAuth2DocCheckTypo3\Middleware\DocCheckOAuthMiddleware;
use DocCheck\OAuth2DocCheckTypo3\OAuth\AuthorizationUrlGenerator;
use DocCheck\OAuth2DocCheckTypo3\OAuth\FrontendSessionOAuthTransactionStore;
use DocCheck\OAuth2DocCheckTypo3\OAuth\OAuthTransaction;
use DocCheck\OAuth2DocCheckTypo3\OAuth\OAuthTransactionFactory;
use DocCheck\OAuth2DocCheckTypo3\OAuth\TokenExchanger;
use DocCheck\OAuth2DocCheckTypo3\Presentation\InformationPageRenderer;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Exercises the callback boundary with a provider fake. No test contacts
 * DocCheck or carries credentials; the production provider remains isolated
 * behind the service interfaces.
 */
final class DocCheckOAuthMiddlewareTest extends TestCase
{
    #[Test]
    public function paidCallbackSuccessEstablishesIdentityAndUsesStoredReturnPath(): void
    {
        [$middleware, $provider, $identity, $frontendUser] = $this->middleware();
        $this->saveTransaction($frontendUser, 'success-state', '/protected-content');

        $response = $middleware->process($this->oauthCallback(['code' => 'functional-code', 'state' => 'success-state'], $frontendUser), new UnusedRequestHandler());

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/protected-content', $response->getHeaderLine('Location'));
        self::assertSame(1, $provider->tokenExchangeCalls);
        self::assertSame(1, $provider->userDataCalls);
        self::assertSame(1, $identity->establishCalls);
        self::assertTrue((new AuthenticatedSession($frontendUser))->isAuthenticated());
    }

    #[Test]
    public function deniedConsentDoesNotExchangeTokenAndConsumesState(): void
    {
        [$middleware, $provider, , $frontendUser] = $this->middleware();
        $this->saveTransaction($frontendUser, 'denied-state', '/');

        $response = $middleware->process($this->oauthCallback(['error' => 'access_denied', 'state' => 'denied-state'], $frontendUser), new UnusedRequestHandler());

        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString('cancelled', (string)$response->getBody());
        self::assertStringContainsString('doccheck-info-box', (string)$response->getBody());
        self::assertSame(0, $provider->tokenExchangeCalls);
        self::assertNull((new FrontendSessionOAuthTransactionStore($frontendUser))->consume('denied-state'));
    }

    #[Test]
    public function missingStateClearsPendingTransactions(): void
    {
        [$middleware, $provider, , $frontendUser] = $this->middleware();
        $this->saveTransaction($frontendUser, 'pending-state', '/');

        $response = $middleware->process($this->oauthCallback(['code' => 'functional-code'], $frontendUser), new UnusedRequestHandler());

        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString('required security state', (string)$response->getBody());
        self::assertSame(0, $provider->tokenExchangeCalls);
        self::assertNull((new FrontendSessionOAuthTransactionStore($frontendUser))->consume('pending-state'));
    }

    #[Test]
    public function replayedStateIsRejectedBeforeANewTokenExchange(): void
    {
        [$middleware, $provider, , $frontendUser] = $this->middleware();
        $this->saveTransaction($frontendUser, 'single-use-state', '/');
        $request = $this->oauthCallback(['code' => 'functional-code', 'state' => 'single-use-state'], $frontendUser);

        self::assertSame(302, $middleware->process($request, new UnusedRequestHandler())->getStatusCode());
        $replayResponse = $middleware->process($request, new UnusedRequestHandler());

        self::assertSame(400, $replayResponse->getStatusCode());
        self::assertStringContainsString('expired or was replaced', (string)$replayResponse->getBody());
        self::assertSame(1, $provider->tokenExchangeCalls);
    }

    #[Test]
    public function tokenExchangeFailureDoesNotEstablishAnIdentity(): void
    {
        [$middleware, $provider, $identity, $frontendUser] = $this->middleware();
        $provider->failTokenExchange = true;
        $this->saveTransaction($frontendUser, 'token-failure-state', '/');

        $response = $middleware->process($this->oauthCallback(['code' => 'invalid-code', 'state' => 'token-failure-state'], $frontendUser), new UnusedRequestHandler());

        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString('could not verify the authorization response', (string)$response->getBody());
        self::assertSame(1, $provider->tokenExchangeCalls);
        self::assertSame(0, $identity->establishCalls);
        self::assertFalse((new AuthenticatedSession($frontendUser))->isAuthenticated());
    }

    #[Test]
    public function provisioningFailureReturnsSafeErrorWithoutLocalSession(): void
    {
        [$middleware, $provider, $identity, $frontendUser] = $this->middleware();
        $identity->failEstablishingIdentity = true;
        $this->saveTransaction($frontendUser, 'provisioning-failure-state', '/');

        $response = $middleware->process($this->oauthCallback(['code' => 'functional-code', 'state' => 'provisioning-failure-state'], $frontendUser), new UnusedRequestHandler());

        self::assertSame(500, $response->getStatusCode());
        self::assertStringContainsString('could not establish the local DocCheck session', (string)$response->getBody());
        self::assertSame(1, $provider->userDataCalls);
        self::assertSame(1, $identity->establishCalls);
        self::assertFalse((new AuthenticatedSession($frontendUser))->isAuthenticated());
    }

    #[Test]
    public function loginNormalizesAnUnsafeReturnPathBeforeItCanReachTheCallback(): void
    {
        [$middleware, , , $frontendUser, $authorizationUrlGenerator] = $this->middleware();

        $startResponse = $middleware->process(
            $this->request('/doccheck/login', ['return' => 'https://attacker.example/'], $frontendUser),
            new UnusedRequestHandler(),
        );

        self::assertSame(302, $startResponse->getStatusCode());
        self::assertNotNull($authorizationUrlGenerator->receivedState);
        $callbackResponse = $middleware->process(
            $this->oauthCallback(['code' => 'functional-code', 'state' => $authorizationUrlGenerator->receivedState], $frontendUser),
            new UnusedRequestHandler(),
        );

        self::assertSame(302, $callbackResponse->getStatusCode());
        self::assertSame('/', $callbackResponse->getHeaderLine('Location'));
    }

    /**
     * @return array{DocCheckOAuthMiddleware, FakeDocCheckProvider, FakeIdentityEstablisher, InMemoryFrontendUserAuthentication, FakeAuthorizationUrlGenerator}
     */
    private function middleware(): array
    {
        $provider = new FakeDocCheckProvider();
        $identity = new FakeIdentityEstablisher();
        $authorizationUrlGenerator = new FakeAuthorizationUrlGenerator();
        $configuration = DocCheckConfiguration::fromArray([
            'clientId' => 'functional-client',
            'clientSecret' => 'functional-secret',
            'redirectUri' => 'https://functional.test/doccheck/callback',
            'licenseMode' => 'economy',
            'requestedScopes' => 'unique_id',
            'enableFrontendUserProvisioning' => true,
        ]);

        return [
            new DocCheckOAuthMiddleware(
                new FixedConfigurationProvider($configuration),
                $authorizationUrlGenerator,
                new OAuthTransactionFactory(),
                $provider,
                $provider,
                $identity,
                new InformationPageRenderer(),
            ),
            $provider,
            $identity,
            new InMemoryFrontendUserAuthentication(),
            $authorizationUrlGenerator,
        ];
    }

    /** @param array<string, string> $parameters */
    private function oauthCallback(array $parameters, FrontendUserAuthentication $frontendUser): ServerRequestInterface
    {
        return $this->request('/doccheck/callback', $parameters, $frontendUser);
    }

    /** @param array<string, string> $parameters */
    private function request(string $path, array $parameters, FrontendUserAuthentication $frontendUser): ServerRequestInterface
    {
        return (new ServerRequest('https://functional.test' . $path))
            ->withQueryParams($parameters)
            ->withAttribute('frontend.user', $frontendUser);
    }

    private function saveTransaction(FrontendUserAuthentication $frontendUser, string $state, string $returnPath): void
    {
        (new FrontendSessionOAuthTransactionStore($frontendUser))->save(
            new OAuthTransaction($state, $returnPath, new \DateTimeImmutable('+5 minutes')),
        );
    }
}

final class FixedConfigurationProvider implements DocCheckConfigurationProvider
{
    public function __construct(private readonly DocCheckConfiguration $configuration) {}

    public function create(): DocCheckConfiguration
    {
        return $this->configuration;
    }
}

final class FakeAuthorizationUrlGenerator implements AuthorizationUrlGenerator
{
    public ?string $receivedState = null;

    public function createAuthorizationUrl(DocCheckConfiguration $configuration, ?string $state = null): string
    {
        $this->receivedState = $state;

        return 'https://provider.invalid/authorize';
    }
}

final class FakeDocCheckProvider implements TokenExchanger, UserDataFetcher
{
    public int $tokenExchangeCalls = 0;
    public int $userDataCalls = 0;
    public bool $failTokenExchange = false;

    public function exchange(DocCheckConfiguration $configuration, string $code): AccessToken
    {
        ++$this->tokenExchangeCalls;
        if ($this->failTokenExchange) {
            throw new \RuntimeException('Fake provider rejected the code.');
        }

        return new AccessToken(['access_token' => 'functional-access-token']);
    }

    public function fetch(DocCheckConfiguration $configuration, AccessToken $accessToken): array
    {
        ++$this->userDataCalls;

        return ['unique_id' => 'functional-user', 'profession_name' => 'Physician'];
    }
}

final class FakeIdentityEstablisher implements IdentityEstablisher
{
    public int $establishCalls = 0;
    public bool $failEstablishingIdentity = false;

    public function establish(array $userData, DocCheckConfiguration $configuration, FrontendUserAuthentication $frontendUser, int $storagePid): void
    {
        ++$this->establishCalls;
        if ($this->failEstablishingIdentity) {
            throw new \RuntimeException('Fake provisioner failed.');
        }

        (new AuthenticatedSession($frontendUser))->establishIdentity(1, 'functional-user', ['profession_name' => 'Physician']);
    }
}

final class InMemoryFrontendUserAuthentication extends FrontendUserAuthentication
{
    /** @var array<string, mixed> */
    private array $sessionData = [];

    public function getSessionData($key): mixed
    {
        return $this->sessionData[$key] ?? '';
    }

    public function setAndSaveSessionData($key, $data): void
    {
        $this->sessionData[$key] = $data;
    }
}

final class UnusedRequestHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse('Unexpected request handler invocation.', 500);
    }
}
