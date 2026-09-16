<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Tests\Unit\Identity;

use DocCheck\OAuth2DocCheckTypo3\Configuration\DocCheckConfiguration;
use DocCheck\OAuth2DocCheckTypo3\Identity\DocCheckProfile;
use DocCheck\OAuth2DocCheckTypo3\Identity\FrontendUserProfileFieldMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FrontendUserProfileFieldMapperTest extends TestCase
{
    #[Test]
    public function mapsOnlyConfiguredNameAndEmailForANewUser(): void
    {
        $fields = (new FrontendUserProfileFieldMapper())->forNewUser(
            new DocCheckProfile('user-42', null, null, null, null, 'Ada', 'Lovelace', 'ada@example.test', null, null, null),
            $this->configuration('name,email'),
        );

        self::assertSame([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.test',
        ], $fields);
    }

    #[Test]
    public function skipsMissingOrInvalidProfileValues(): void
    {
        $fields = (new FrontendUserProfileFieldMapper())->forNewUser(
            new DocCheckProfile('user-42', null, null, null, null, 'Ada', null, 'not-an-email', null, null, null),
            $this->configuration('name,email'),
        );

        self::assertSame(['first_name' => 'Ada'], $fields);
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
}
