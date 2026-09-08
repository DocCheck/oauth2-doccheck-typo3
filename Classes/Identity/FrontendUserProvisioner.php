<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Identity;

use DocCheck\OAuth2DocCheckTypo3\Configuration\DocCheckConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Creates the smallest possible local FE-user record keyed by DocCheck unique_id.
 */
final readonly class FrontendUserProvisioner
{
    public function __construct(private ConnectionPool $connectionPool) {}

    /**
     * @param array<string, mixed> $userData
     */
    public function provision(array $userData, DocCheckConfiguration $configuration, int $storagePid): int
    {
        $uniqueIdValue = $userData['unique_id'] ?? '';
        $uniqueId = is_string($uniqueIdValue) ? trim($uniqueIdValue) : '';
        if ($uniqueId === '') {
            throw new \RuntimeException('DocCheck did not return the required unique_id.');
        }

        $connection = $this->connectionPool->getConnectionForTable('fe_users');
        $existingUser = $this->findUser($uniqueId);
        $groups = $this->groups($existingUser['usergroup'] ?? '', $configuration->defaultFrontendUserGroup());
        $fields = [
            'tx_oauth2docchecktypo3_unique_id' => $uniqueId,
            'usergroup' => $groups,
            'tstamp' => time(),
        ];

        if ($existingUser !== null) {
            $connection->update('fe_users', $fields, ['uid' => $existingUser['uid']]);

            return $existingUser['uid'];
        }

        $fields += [
            'pid' => $storagePid,
            'username' => 'doccheck_' . substr(hash('sha256', $uniqueId), 0, 32),
            'password' => password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
            'disable' => 0,
            'crdate' => time(),
        ];
        $connection->insert('fe_users', $fields);

        return (int)$connection->lastInsertId();
    }

    /**
     * @return array{uid: int, usergroup: string}|null
     */
    private function findUser(string $uniqueId): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('fe_users');
        $user = $queryBuilder
            ->select('uid', 'usergroup')
            ->from('fe_users')
            ->where($queryBuilder->expr()->eq(
                'tx_oauth2docchecktypo3_unique_id',
                $queryBuilder->createNamedParameter($uniqueId),
            ))
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if (!is_array($user) || !is_numeric($user['uid'] ?? null)) {
            return null;
        }

        return [
            'uid' => (int)$user['uid'],
            'usergroup' => is_string($user['usergroup'] ?? null) ? $user['usergroup'] : '',
        ];
    }

    private function groups(string $existingGroups, int $defaultGroup): string
    {
        $groups = array_filter(array_map(
            static fn(string $group): int => (int)$group,
            explode(',', $existingGroups),
        ), static fn(int $group): bool => $group > 0);
        if ($defaultGroup > 0) {
            $groups[] = $defaultGroup;
        }

        return implode(',', array_unique($groups));
    }
}
