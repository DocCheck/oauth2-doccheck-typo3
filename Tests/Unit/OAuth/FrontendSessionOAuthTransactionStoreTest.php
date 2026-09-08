<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Tests\Unit\OAuth;

use DocCheck\OAuth2DocCheckTypo3\OAuth\FrontendSessionOAuthTransactionStore;
use DocCheck\OAuth2DocCheckTypo3\OAuth\OAuthTransaction;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

final class FrontendSessionOAuthTransactionStoreTest extends TestCase
{
    #[Test]
    public function consumingOneStateKeepsAnotherPendingTransactionAndPreventsReplay(): void
    {
        /** @var array<string, mixed> $sessionData */
        $sessionData = [];
        $frontendUser = $this->createMock(FrontendUserAuthentication::class);
        $frontendUser->method('getSessionData')->willReturnCallback(static function (string $key) use (&$sessionData): mixed {
            return $sessionData[$key] ?? null;
        });
        $frontendUser->method('setAndSaveSessionData')->willReturnCallback(static function (string $key, mixed $value) use (&$sessionData): void {
            $sessionData[$key] = $value;
        });
        $store = new FrontendSessionOAuthTransactionStore($frontendUser);
        $expiresAt = new \DateTimeImmutable('+4 minutes');
        $first = new OAuthTransaction('first-state', '/first', $expiresAt);
        $second = new OAuthTransaction('second-state', '/second', $expiresAt);

        $store->save($first);
        $store->save($second);

        self::assertSame('/first', $store->consume('first-state')?->returnPath());
        self::assertSame('/second', $store->consume('second-state')?->returnPath());
        self::assertNull($store->consume('first-state'));
    }
}
