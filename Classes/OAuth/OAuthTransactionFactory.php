<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\OAuth;

final readonly class OAuthTransactionFactory
{
    public function create(string $returnPath, \DateTimeImmutable $now): OAuthTransaction
    {
        return new OAuthTransaction(
            bin2hex(random_bytes(32)),
            $returnPath,
            $now->modify('+5 minutes'),
        );
    }
}
