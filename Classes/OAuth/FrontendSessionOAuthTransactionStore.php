<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\OAuth;

use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Stores only CSRF/redirect transaction data in TYPO3's anonymous FE session.
 */
final readonly class FrontendSessionOAuthTransactionStore implements OAuthTransactionStore
{
    private const SESSION_KEY = 'oauth2_doccheck_typo3.transaction';

    /** A small allowance for separate tabs without retaining stale transactions. */
    private const MAX_PENDING_TRANSACTIONS = 5;

    public function __construct(private FrontendUserAuthentication $frontendUser) {}

    public function save(OAuthTransaction $transaction): void
    {
        $transactions = $this->pendingTransactions();
        $transactions[$transaction->state()] = $this->serialize($transaction);
        uasort($transactions, static function (array $left, array $right): int {
            $leftExpiresAt = $left['expiresAt'] ?? null;
            $rightExpiresAt = $right['expiresAt'] ?? null;

            return strcmp(is_string($leftExpiresAt) ? $leftExpiresAt : '', is_string($rightExpiresAt) ? $rightExpiresAt : '');
        });
        $transactions = array_slice($transactions, -self::MAX_PENDING_TRANSACTIONS, null, true);
        $this->savePendingTransactions($transactions);
    }

    public function consume(string $state): ?OAuthTransaction
    {
        $transactions = $this->pendingTransactions();
        $data = $transactions[$state] ?? null;
        unset($transactions[$state]);
        $this->savePendingTransactions($transactions);

        if (!is_array($data)) {
            return null;
        }

        return $this->deserialize($data);
    }

    public function clear(): void
    {
        $this->frontendUser->setAndSaveSessionData(self::SESSION_KEY, null);
    }

    /** @return array<string, array<mixed>> */
    private function pendingTransactions(): array
    {
        $data = $this->frontendUser->getSessionData(self::SESSION_KEY);
        if (!is_array($data)) {
            return [];
        }

        // Accept an already-created single transaction from an older release.
        if (isset($data['state'])) {
            $transaction = $this->deserialize($data);
            return $transaction === null ? [] : [$transaction->state() => $this->serialize($transaction)];
        }

        $transactions = [];
        foreach ($data as $state => $entry) {
            if (!is_string($state) || !is_array($entry)) {
                continue;
            }
            $transaction = $this->deserialize($entry);
            if ($transaction !== null && $transaction->expiresAt() > new \DateTimeImmutable()) {
                $transactions[$transaction->state()] = $this->serialize($transaction);
            }
        }

        return $transactions;
    }

    /** @param array<mixed> $data */
    private function deserialize(array $data): ?OAuthTransaction
    {
        if (!is_string($data['state'] ?? null) || !is_string($data['returnPath'] ?? null) || !is_string($data['expiresAt'] ?? null)) {
            return null;
        }

        try {
            return new OAuthTransaction($data['state'], $data['returnPath'], new \DateTimeImmutable($data['expiresAt']));
        } catch (\Exception) {
            return null;
        }
    }

    /** @return array{state: string, returnPath: string, expiresAt: string} */
    private function serialize(OAuthTransaction $transaction): array
    {
        return [
            'state' => $transaction->state(),
            'returnPath' => $transaction->returnPath(),
            'expiresAt' => $transaction->expiresAt()->format(DATE_ATOM),
        ];
    }

    /** @param array<string, array<mixed>> $transactions */
    private function savePendingTransactions(array $transactions): void
    {
        $this->frontendUser->setAndSaveSessionData(self::SESSION_KEY, $transactions === [] ? null : $transactions);
    }
}
