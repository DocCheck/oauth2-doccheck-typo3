<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Tests\Unit\Identity;

use Doccheck\OAuth2\Client\Provider\DoccheckResourceOwner;
use DocCheck\OAuth2DocCheckTypo3\Identity\DocCheckProfileMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DocCheckProfileMapperTest extends TestCase
{
    #[Test]
    public function mapsAllPublicScopeFieldsToTypedProfileProperties(): void
    {
        $result = (new DocCheckProfileMapper())->map(new DoccheckResourceOwner([
            'unique_id' => 'user-42',
            'profession_id' => '7',
            'country_iso_code' => 'DE',
            'country_id' => '276',
            'user_language' => 'de',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ADA@EXAMPLE.TEST',
            'area_code' => '50667',
            'street' => 'Vogelsanger Str. 66',
            'city' => 'Köln',
            'discipline_id' => '8',
            'activity_id' => '9',
        ]), [
            'unique_id', 'profession', 'country', 'language', 'name', 'email', 'address', 'occupation_detail',
        ]);

        $profile = $result->profile();
        self::assertSame('user-42', $profile->uniqueId);
        self::assertSame(7, $profile->professionId);
        self::assertSame('DE', $profile->countryIsoCode);
        self::assertSame(276, $profile->countryId);
        self::assertSame('de', $profile->language);
        self::assertSame('Ada', $profile->firstName);
        self::assertSame('Lovelace', $profile->lastName);
        self::assertSame('ada@example.test', $profile->email);
        self::assertNotNull($profile->address);
        self::assertSame('50667', $profile->address->areaCode);
        self::assertSame('Vogelsanger Str. 66', $profile->address->street);
        self::assertSame('Köln', $profile->address->city);
        self::assertSame('DE', $profile->address->countryIsoCode);
        self::assertSame(8, $profile->disciplineId);
        self::assertSame(9, $profile->activityId);
        self::assertSame(array_fill(0, 8, 'received'), array_map(static fn($diagnostic): string => $diagnostic->status(), $result->scopeDiagnostics()));
    }

    #[Test]
    public function reportsMissingPartialAndInvalidScopesWithoutTreatingThemAsConsentDecisions(): void
    {
        $result = (new DocCheckProfileMapper())->map(new DoccheckResourceOwner([
            'first_name' => 'Ada',
            'email' => ['unexpected'],
        ]), ['country', 'name', 'email']);

        $diagnostics = $result->scopeDiagnostics();
        self::assertSame('missing', $diagnostics[0]->status());
        self::assertSame(['country_iso_code', 'country_id'], $diagnostics[0]->missingFields());
        self::assertSame('partial', $diagnostics[1]->status());
        self::assertSame(['last_name'], $diagnostics[1]->missingFields());
        self::assertSame('invalid', $diagnostics[2]->status());
        self::assertNull($result->profile()->email);
    }

    #[Test]
    public function doesNotMapDataFromAnUnrequestedScope(): void
    {
        $result = (new DocCheckProfileMapper())->map(new DoccheckResourceOwner([
            'unique_id' => 'user-42',
            'email' => 'ada@example.test',
        ]), ['unique_id']);

        self::assertSame('user-42', $result->profile()->uniqueId);
        self::assertNull($result->profile()->email);
    }
}
