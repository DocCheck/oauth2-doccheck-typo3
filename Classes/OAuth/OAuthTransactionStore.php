<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\OAuth;

interface OAuthTransactionStore
{
    public function save(OAuthTransaction $transaction): void;

    /**
     * Removes the transaction matching the received state before returning it,
     * making that transaction single-use.
     */
    public function consume(string $state): ?OAuthTransaction;

    /**
     * Removes all pending transactions for the current browser session.
     */
    public function clear(): void;
}
