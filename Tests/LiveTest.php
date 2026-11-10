<?php

namespace Omnical\Openholidays\Tests;

use Omnical\Declarative\DeclarativeFactory;
use Omnical\Model\Holiday;
use Omnical\Model\HolidayQuery;
use Omnical\Model\HolidayType;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;

/**
 * OpenHolidays itself, two requests one after the other with a User-Agent
 * naming the application and a contact: France's public holidays of 2027,
 * Bavaria's school holidays of its first quarter. `docker compose run --rm
 * omnical live`; never in the default run.
 */
#[Group('live')]
final class LiveTest extends TestCase
{
    private const UA = 'omnical-tests/1.0 (+https://github.com/glitchr-omnisense/omnical; marco.meyer@glitchr.io)';

    public function testFranceAndBavaria(): void
    {
        $provider = DeclarativeFactory::fromFile(OpenholidaysTest::DEFINITION, HttpClient::create(['timeout' => 30]))->create(['user_agent' => self::UA]);
        self::assertInstanceOf(\Omnical\HolidaysInterface::class, $provider);

        $names = array_map(static fn (Holiday $h) => $h->name, $provider->holidays(HolidayQuery::year('FR', 2027, language: 'fr')));
        self::assertContains('Fête nationale', $names);

        $school = $provider->holidays(new HolidayQuery('DE', new \DateTimeImmutable('2027-01-01'), new \DateTimeImmutable('2027-03-31'), 'DE-BY', 'de', [HolidayType::School]));
        self::assertNotEmpty($school);
        self::assertTrue($school[0]->is(HolidayType::School));
    }
}
