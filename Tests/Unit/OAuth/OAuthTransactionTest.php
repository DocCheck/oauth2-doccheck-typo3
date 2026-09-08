<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Tests\Unit\OAuth;

use DocCheck\OAuth2DocCheckTypo3\OAuth\OAuthTransaction;
use DocCheck\OAuth2DocCheckTypo3\OAuth\OAuthTransactionFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OAuthTransactionTest extends TestCase
{
    #[Test]
    public function transactionUsesHighEntropyStateAndExpires(): void
    {
        $now = new \DateTimeImmutable('2026-09-03T12:00:00+00:00');
        $transaction = (new OAuthTransactionFactory())->create('/protected', $now);

        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $transaction->state());
        self::assertTrue($transaction->matches($transaction->state(), $now));
        self::assertFalse($transaction->matches($transaction->state(), $now->modify('+5 minutes')));
        self::assertFalse($transaction->matches('different-state', $now));
    }

    #[Test]
    public function transactionRejectsExternalReturnTarget(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('local absolute path');

        new OAuthTransaction('state', 'https://attacker.example/', new \DateTimeImmutable('+5 minutes'));
    }
}
