<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Tests\Unit\EventListener;

use DocCheck\OAuth2DocCheckTypo3\Access\AuthenticatedSession;
use DocCheck\OAuth2DocCheckTypo3\Access\ProtectedAccessService;
use DocCheck\OAuth2DocCheckTypo3\EventListener\FilterProtectedContentListener;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\ContentObject\Event\ModifyRecordsAfterFetchingContentEvent;
use TYPO3\CMS\Frontend\Page\PageInformation;

final class FilterProtectedContentListenerTest extends TestCase
{
    #[Test]
    public function removesEveryContentRecordFromAnUnauthenticatedProtectedPage(): void
    {
        $event = $this->event([
            ['uid' => 1, 'tx_oauth2docchecktypo3_requires_auth' => 0],
            ['uid' => 2, 'tx_oauth2docchecktypo3_requires_auth' => 1],
        ]);

        $this->withRequest($this->request(true), function () use ($event): void {
            (new FilterProtectedContentListener(new ProtectedAccessService()))($event);
        });

        self::assertSame([], $event->getRecords());
    }

    #[Test]
    public function retainsPublicContentOnAnUnauthenticatedPublicPage(): void
    {
        $event = $this->event([
            ['uid' => 1, 'tx_oauth2docchecktypo3_requires_auth' => 0],
            ['uid' => 2, 'tx_oauth2docchecktypo3_requires_auth' => 1],
        ]);

        $this->withRequest($this->request(false), function () use ($event): void {
            (new FilterProtectedContentListener(new ProtectedAccessService()))($event);
        });

        self::assertSame([['uid' => 1, 'tx_oauth2docchecktypo3_requires_auth' => 0]], $event->getRecords());
    }

    #[Test]
    public function retainsEveryContentRecordForAnAuthenticatedProtectedPage(): void
    {
        $records = [
            ['uid' => 1, 'tx_oauth2docchecktypo3_requires_auth' => 0],
            ['uid' => 2, 'tx_oauth2docchecktypo3_requires_auth' => 1],
        ];
        $event = $this->event($records);

        $this->withRequest($this->request(true, true), function () use ($event): void {
            (new FilterProtectedContentListener(new ProtectedAccessService()))($event);
        });

        self::assertSame($records, $event->getRecords());
    }

    /** @param list<array<string, int>> $records */
    private function event(array $records): ModifyRecordsAfterFetchingContentEvent
    {
        return new ModifyRecordsAfterFetchingContentEvent($records, '', 0, 0, false, false, []);
    }

    private function request(bool $protected, bool $authenticated = false): ServerRequest
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord(['tx_oauth2docchecktypo3_requires_auth' => $protected ? 1 : 0]);
        $frontendUser = new InMemoryFrontendUserAuthentication();
        if ($authenticated) {
            (new AuthenticatedSession($frontendUser))->establishIdentity(1, 'test-user');
        }

        return (new ServerRequest('https://example.test/'))
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.user', $frontendUser);
    }

    /** @param \Closure(): void $assertion */
    private function withRequest(ServerRequest $request, \Closure $assertion): void
    {
        $hadRequest = array_key_exists('TYPO3_REQUEST', $GLOBALS);
        $previousRequest = $GLOBALS['TYPO3_REQUEST'] ?? null;
        $GLOBALS['TYPO3_REQUEST'] = $request;

        try {
            $assertion();
        } finally {
            if ($hadRequest) {
                $GLOBALS['TYPO3_REQUEST'] = $previousRequest;
            } else {
                unset($GLOBALS['TYPO3_REQUEST']);
            }
        }
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
