<?php

namespace Omnical\Openholidays\Tests;

use Omnical\Declarative\DeclarativeFactory;
use Omnical\Declarative\DeclarativeProvider;
use Omnical\Model\Holiday;
use Omnical\Model\HolidayQuery;
use Omnical\Model\HolidayType;
use Omnical\Registry;
use Omnical\Testing\RecordedHttpClient;
use PHPUnit\Framework\TestCase;

/**
 * OpenHolidays' answers recorded on 2026-11-10 at 04:21 UTC (GET
 * /PublicHolidays FR and DE, /SchoolHolidays DE-BY, 2027; kept whole), read
 * through provider.json alone: no class of this package runs, there is none.
 */
final class OpenholidaysTest extends TestCase
{
    public const DEFINITION = __DIR__.'/../provider.json';
    private const UA = 'omnical-tests/1.0 (+https://github.com/glitchr-omnisense/omnical; tests@example.org)';

    public function testFrancesHolidaysInFrenchEveryRegionTold(): void
    {
        $http = self::http();
        $holidays = self::registry($http)->holidays('holidays')->holidays(HolidayQuery::year('FR', 2027, language: 'fr'));

        self::assertCount(19, $holidays);
        self::assertSame(['2027-01-01', 'Jour de l\'an', 'fr', true, [], HolidayType::Public], self::row($holidays[0]));
        $alsace = array_values(array_filter($holidays, static fn (Holiday $h) => '2027-12-26' === $h->first->format('Y-m-d')))[0];
        self::assertSame(['2027-12-26', '2ème jour de Noël', 'fr', false, ['FR-GE-BR', 'FR-GE-HR', 'FR-GE-MO'], HolidayType::Public], self::row($alsace));
        self::assertSame('openholidays', $alsace->source);
        self::assertMatchesRegularExpression('~^[0-9a-f-]{36}$~', (string) $alsace->id, 'OpenHolidays\' own identifier');

        self::assertCount(1, $http->requests, 'one year, the school endpoint not asked');
        self::assertSame('https://openholidaysapi.org/PublicHolidays', $http->requests[0]['url']);
        self::assertSame(['countryIsoCode' => 'FR', 'languageIsoCode' => 'FR', 'validFrom' => '2027-01-01', 'validTo' => '2027-12-31'], $http->requests[0]['options']['query'] ?? null);
        self::assertSame([self::UA, 'application/json'], [$http->header('User-Agent'), $http->header('Accept')]);
    }

    public function testARegionKeepsTheNationwideDaysAndItsOwn(): void
    {
        $http = self::http();
        $names = array_map(static fn (Holiday $h) => $h->first->format('m-d').' '.$h->name, self::registry($http)->holidays('holidays')->holidays(HolidayQuery::year('DE', 2027, 'DE-BY', 'de')));

        self::assertContains('01-06 Heilige Drei Könige', $names);
        self::assertContains('08-08 Friedensfest', $names, 'Augsburg (DE-BY-AU) is in Bavaria');
        self::assertContains('10-03 Tag der Deutschen Einheit', $names);
        self::assertNotContains('03-08 Internationaler Frauentag', $names, 'Berlin and Mecklenburg-Vorpommern only');
        self::assertSame('DE-BY', self::query($http, 0)['subdivisionCode'] ?? null);
    }

    public function testSchoolHolidaysAreAskedOnlyWhenWanted(): void
    {
        $http = self::http();
        $holidays = self::registry($http)->holidays('holidays')->holidays(new HolidayQuery('DE', new \DateTimeImmutable('2027-01-01'), new \DateTimeImmutable('2027-03-31'), 'DE-BY', 'de', [HolidayType::School]));

        self::assertSame([['Weihnachtsferien', '2026-12-24', '2027-01-08'], ['Frühjahrsferien', '2027-02-08', '2027-02-12'], ['Osterferien', '2027-03-22', '2027-04-02']], array_map(static fn (Holiday $h) => [$h->name, $h->first->format('Y-m-d'), $h->last->format('Y-m-d')], $holidays));
        self::assertTrue($holidays[0]->is(HolidayType::School));
        self::assertSame(['https://openholidaysapi.org/SchoolHolidays'], array_map(static fn (array $r) => $r['url'], $http->requests));
        self::assertSame(['countryIsoCode' => 'DE', 'languageIsoCode' => 'DE', 'validFrom' => '2027-01-01', 'validTo' => '2027-03-31', 'subdivisionCode' => 'DE-BY'], $http->requests[0]['options']['query'] ?? null);
    }

    public function testTwoYearsAreTwoCallsAndAnUnknownCountryIsEmpty(): void
    {
        $http = self::http();
        $registry = self::registry($http);
        $registry->holidays('holidays')->holidays(new HolidayQuery('FR', new \DateTimeImmutable('2026-12-01'), new \DateTimeImmutable('2027-01-31')));
        self::assertSame([['2026-12-01', '2026-12-31'], ['2027-01-01', '2027-01-31']], [[self::query($http, 0)['validFrom'] ?? null, self::query($http, 0)['validTo'] ?? null], [self::query($http, 1)['validFrom'] ?? null, self::query($http, 1)['validTo'] ?? null]]);
        self::assertSame([], $registry->holidays('holidays')->holidays(HolidayQuery::year('JP', 2027)), 'OpenHolidays answers [] for a country it does not cover');
    }

    public function testThePackageIsFoundAmongTheInstalledOnes(): void
    {
        $names = array_map(static fn (DeclarativeFactory $f) => $f->getName(), DeclarativeFactory::installed(self::http()));
        if (!\in_array('openholidays', $names, true)) {
            self::markTestSkipped('omnical/openholidays is not installed by Composer here.');
        }
        $provider = (new Registry(DeclarativeFactory::installed(self::http()), ['holidays' => ['factory' => 'openholidays']]))->get('holidays');
        self::assertInstanceOf(DeclarativeProvider::class, $provider);
        self::assertSame(['holidays'], $provider->capabilities()->contracts());
        self::assertCount(36, $provider->capabilities()->countries);
        self::assertTrue($provider->capabilities()->coversCountry('fr'));
    }

    public static function http(): RecordedHttpClient
    {
        return RecordedHttpClient::routes([
            '~^GET https://openholidaysapi\.org/PublicHolidays\?countryIsoCode=FR&~' => [200, '@'.__DIR__.'/Fixtures/public-fr-2027.json'],
            '~^GET https://openholidaysapi\.org/PublicHolidays\?countryIsoCode=DE&~' => [200, '@'.__DIR__.'/Fixtures/public-de-2027.json'],
            '~^GET https://openholidaysapi\.org/SchoolHolidays\?countryIsoCode=DE&.*subdivisionCode=DE-BY~' => [200, '@'.__DIR__.'/Fixtures/school-de-by-2027.json'],
            '~^GET https://openholidaysapi\.org/PublicHolidays\?countryIsoCode=JP&~' => [200, '[]'],
        ]);
    }

    /** @return array<mixed> a request's query */
    private static function query(RecordedHttpClient $http, int $request): array
    {
        $query = $http->requests[$request]['options']['query'] ?? [];

        return \is_array($query) ? $query : [];
    }

    private static function registry(RecordedHttpClient $http): Registry
    {
        return new Registry([DeclarativeFactory::fromFile(self::DEFINITION, $http)], ['holidays' => ['factory' => 'openholidays', 'options' => ['user_agent' => self::UA]]]);
    }

    /** @return array{0: string, 1: string, 2: ?string, 3: bool, 4: list<string>, 5: HolidayType} */
    private static function row(Holiday $holiday): array
    {
        return [$holiday->first->format('Y-m-d'), $holiday->name, $holiday->language, $holiday->nationwide, $holiday->regions, $holiday->type()];
    }
}
