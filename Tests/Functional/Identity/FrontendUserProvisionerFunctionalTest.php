<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Tests\Functional\Identity;

use DocCheck\OAuth2DocCheckTypo3\Configuration\DocCheckConfiguration;
use DocCheck\OAuth2DocCheckTypo3\Identity\DocCheckProfile;
use DocCheck\OAuth2DocCheckTypo3\Identity\FrontendUserProfileFieldMapper;
use DocCheck\OAuth2DocCheckTypo3\Identity\FrontendUserProvisioner;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FrontendUserProvisionerFunctionalTest extends FunctionalTestCase
{
    private Connection $connection;

    public static function setUpBeforeClass(): void
    {
        if (getenv('typo3DatabaseDriver') === false) {
            putenv('typo3DatabaseDriver=pdo_sqlite');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('fe_users');
        $columns = $this->connection->createSchemaManager()->listTableColumns('fe_users');
        if (!isset($columns['tx_oauth2docchecktypo3_unique_id'])) {
            $this->connection->executeStatement("ALTER TABLE fe_users ADD tx_oauth2docchecktypo3_unique_id VARCHAR(255) NOT NULL DEFAULT ''");
        }
    }

    #[Test]
    public function createsMappedFieldsOnceAndPreservesLaterLocalEdits(): void
    {
        $provisioner = $this->provisioner();
        $configuration = $this->configuration('name,email');

        $uid = $provisioner->provision($this->profile('Ada', 'Lovelace', 'ada@example.test'), $configuration, 1);
        self::assertSame([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.test',
        ], $this->profileFields($uid));

        $this->connection->update('fe_users', [
            'first_name' => 'Locally maintained',
            'email' => 'local@example.test',
        ], ['uid' => $uid]);
        $secondUid = $provisioner->provision($this->profile('Provider', 'Changed', 'provider@example.test'), $configuration, 1);

        self::assertSame($uid, $secondUid);
        self::assertSame([
            'first_name' => 'Locally maintained',
            'last_name' => 'Lovelace',
            'email' => 'local@example.test',
        ], $this->profileFields($uid));
    }

    #[Test]
    public function doesNotUseEmailAsAnIdentityKey(): void
    {
        $this->connection->insert('fe_users', [
            'pid' => 1,
            'username' => 'locally-created',
            'password' => 'local-password',
            'disable' => 0,
            'crdate' => 1,
            'tstamp' => 1,
            'usergroup' => '',
            'tx_oauth2docchecktypo3_unique_id' => '',
            'first_name' => '',
            'last_name' => '',
            'email' => 'ada@example.test',
        ]);

        $uid = $this->provisioner()->provision(
            $this->profile('Ada', 'Lovelace', 'ada@example.test'),
            $this->configuration('name,email'),
            1,
        );

        self::assertCount(2, $this->connection->fetchFirstColumn('SELECT uid FROM fe_users'));
        self::assertSame('doccheck-user', $this->connection->fetchOne('SELECT tx_oauth2docchecktypo3_unique_id FROM fe_users WHERE uid = ?', [$uid]));
    }

    #[Test]
    public function leavesStandardProfileFieldsEmptyWhenMappingIsDisabled(): void
    {
        $uid = $this->provisioner()->provision(
            $this->profile('Ada', 'Lovelace', 'ada@example.test'),
            $this->configuration(''),
            1,
        );

        self::assertSame([
            'first_name' => '',
            'last_name' => '',
            'email' => '',
        ], $this->profileFields($uid));
    }

    private function configuration(string $profileFieldMapping): DocCheckConfiguration
    {
        return DocCheckConfiguration::fromArray([
            'clientId' => 'test-client-id',
            'clientSecret' => 'test-client-secret',
            'redirectUri' => 'https://example.test/doccheck/callback',
            'licenseMode' => 'business',
            'requestedScopes' => 'unique_id,name,email',
            'enableFrontendUserProvisioning' => true,
            'profileFieldMapping' => $profileFieldMapping,
        ]);
    }

    private function provisioner(): FrontendUserProvisioner
    {
        return new FrontendUserProvisioner(
            GeneralUtility::makeInstance(ConnectionPool::class),
            new FrontendUserProfileFieldMapper(),
        );
    }

    private function profile(string $firstName, string $lastName, string $email): DocCheckProfile
    {
        return new DocCheckProfile('doccheck-user', null, null, null, null, $firstName, $lastName, $email, null, null, null);
    }

    /** @return array{first_name: string, last_name: string, email: string} */
    private function profileFields(int $uid): array
    {
        /** @var array{first_name: string, last_name: string, email: string}|false $fields */
        $fields = $this->connection->fetchAssociative('SELECT first_name, last_name, email FROM fe_users WHERE uid = ?', [$uid]);
        self::assertIsArray($fields);

        return $fields;
    }
}
