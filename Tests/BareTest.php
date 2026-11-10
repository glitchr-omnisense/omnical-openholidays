<?php

namespace Omnical\Openholidays\Tests;

use Omnical\Testing\DeclaredBareTestCase;

/** omnical/openholidays in plain PHP: its definition loaded and asked, nothing loaded but omnisense/omnical, omnisense/contracts and the HTTP client contracts. */
final class BareTest extends DeclaredBareTestCase
{
    protected static function definition(): string
    {
        return OpenholidaysTest::DEFINITION;
    }

    protected static function routes(): array
    {
        return ['~/PublicHolidays\?countryIsoCode=FR&~' => [200, '@'.__DIR__.'/Fixtures/public-fr-2027.json']];
    }

    protected static function query(): array
    {
        return ['country' => 'FR', 'from' => '2027-07-01', 'to' => '2027-08-31', 'language' => 'fr'];
    }

    public function testTheDefinitionAloneGivesHolidays(): void
    {
        $declared = self::declared();

        self::assertSame([['2027-07-14', 'Fête nationale'], ['2027-08-15', 'Assomption']], $declared['holidays']);
        self::assertSame(['GET https://openholidaysapi.org/PublicHolidays'], $declared['requests']);
    }
}
