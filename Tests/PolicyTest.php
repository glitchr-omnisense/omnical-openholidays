<?php

namespace Omnical\Openholidays\Tests;

use Omnical\Declarative\DeclarativeFactory;
use Omnical\Exception\UsageLimitReachedException;
use Omnical\Model\HolidayQuery;
use Omnical\Registry;
use Omnisense\Contracts\Policy\Permission;
use Omnisense\Contracts\Policy\PolicyExport;
use PHPUnit\Framework\TestCase;

/** OpenHolidays' usage policy as published beside the definition, enforced through the registry, tightened by a site. */
final class PolicyTest extends TestCase
{
    public function testThePolicyIsTheOneOpenHolidaysPublishes(): void
    {
        $policy = (new Registry([DeclarativeFactory::fromFile(OpenholidaysTest::DEFINITION, OpenholidaysTest::http())], ['h' => ['factory' => 'openholidays']]))->policy('h');

        self::assertSame('openholidays', $policy->provider);
        self::assertNull($policy->rateLimits, 'no limit stated anywhere: unknown, not none');
        self::assertSame(Permission::Allowed, $policy->rights->commercial);
        self::assertSame('ODbL-1.0', $policy->rights->licence);
        self::assertTrue($policy->rights->attribution->required);
        self::assertSame('https://www.openholidaysapi.org', $policy->rights->attribution->url);
        self::assertFalse($policy->requirements->key);
        self::assertFalse($policy->requirements->userAgent);
        self::assertSame('2026-11-10', $policy->provenance->verifiedAt);
    }

    public function testComposerNamesThePolicyAndTheDefinition(): void
    {
        $composer = json_decode((string) file_get_contents(__DIR__.'/../composer.json'), true);
        self::assertIsArray($composer);
        self::assertSame(['omnical' => ['provider' => 'provider.json'], 'omnisense' => ['policy' => 'policy.json'], 'branch-alias' => ['dev-1.x' => '1.x-dev']], $composer['extra'] ?? null);
        self::assertArrayNotHasKey('autoload', $composer, 'a package of data: no class');
        $entries = (new PolicyExport())->folders(__DIR__.'/..')->entries();
        self::assertSame(['omnical/openholidays', 'openholidays'], [$entries[0]['package'] ?? null, $entries[0]['policy']['provider'] ?? null], 'bin/omnisense-policies lists it');
    }

    public function testASitesLimitIsCountedAndTheCallOverItNeverMade(): void
    {
        $http = OpenholidaysTest::http();
        $registry = new Registry([DeclarativeFactory::fromFile(OpenholidaysTest::DEFINITION, $http)], ['h' => ['factory' => 'openholidays', 'policy' => ['rateLimits' => [['max' => 1, 'window' => 'hour', 'operations' => ['holidays']]]]]]);
        $registry->holidays('h')->holidays(HolidayQuery::year('FR', 2027));
        try {
            $registry->holidays('h')->holidays(HolidayQuery::year('FR', 2027));
            self::fail('A second call went through.');
        } catch (UsageLimitReachedException $e) {
            self::assertSame('1/hour/application/holidays', $e->limit?->id());
        }
        self::assertCount(1, $http->requests);
    }
}
