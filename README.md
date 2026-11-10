# omnical/openholidays

The [OpenHolidays API](https://www.openholidaysapi.org) for
[omnisense/omnical](https://github.com/glitchr-omnisense/omnical), under any PHP host, with no key:
the public and school holidays of 36 countries by region, in the country's languages, from open
data (ODbL).

```
composer require omnical/openholidays
```

**A package of data, no PHP class**: `provider.json` declares the provider (its endpoints, how
each answer's fields map onto omnical's `Holiday`), `policy.json` its usage policy. omnical's
declarative mechanism loads both at run time. Why declared rather than written: two GET
endpoints answering plain JSON, no key, no paging, no writes - exactly what a definition says
well, and nothing a class would add.

```php
use Omnical\Declarative\DeclarativeFactory;
use Omnical\Model\{HolidayQuery, HolidayType};
use Omnical\Registry;

$registry = new Registry(DeclarativeFactory::installed($http), [
    'holidays' => ['factory' => 'openholidays', 'options' => ['user_agent' => 'MySite/1.0 (https://example.org; contact@example.org)']],
]);
$holidays = $registry->holidays('holidays');

$public = $holidays->holidays(HolidayQuery::year('DE', 2027, 'DE-BY', 'de'));       // Bavaria's, in German
$school = $holidays->holidays(HolidayQuery::year('DE', 2027, 'DE-BY', 'de', [HolidayType::School]));
```

## Options

| Option | |
|---|---|
| `user_agent` | sent on every request (not required by OpenHolidays; name the site and a contact) |
| `base_uri` | `https://openholidaysapi.org`, or a self-hosted instance (the server is AGPL-3.0) |
| `timeout` | seconds |
| `policy`, `usage` | the site's tightening of the policy, what the site is |

## What is called, how it is read

| Query | Call |
|---|---|
| public, bank, optional holidays (the default: every type but school) | `GET /PublicHolidays?countryIsoCode&languageIsoCode&validFrom&validTo&subdivisionCode`, one call per calendar year (the API allows three years at most) |
| school holidays (`HolidayType::School` asked) | `GET /SchoolHolidays` with the same parameters |

| Holiday | From |
|---|---|
| first, last | `startDate`, `endDate` |
| name, language | the `name` in the language asked, the first one otherwise |
| nationwide, regions | `nationwide`, each `subdivisions[].code` |
| types | `type`: Public, Bank, Optional, School; BackToSchool and EndOfLessons read as observances |
| halfDay | `temporalScope` HalfDay |
| id, comment | `id`, the `comment` in the language asked |

Regions are OpenHolidays' subdivision codes: ISO 3166-2 where the country has them (`DE-BY`),
the API's own otherwise (`FR-GE-BR`, `FR-GE-HR`, `FR-GE-MO` for Alsace and Moselle, `DE-BY-AU`
for Augsburg); a query for `DE-BY` keeps `DE-BY-AU`'s days. An unknown country is answered with
an empty list.

## Usage policy (read on 2026-11-10)

Free of charge, commercial use allowed (the [FAQ](https://www.openholidaysapi.org/en/faq/)). The
data is under the [Open Database License](https://github.com/openpotato/openholidaysapi.data/blob/main/LICENSE):
credit it ("Holidays: OpenHolidays API, ODbL", `$registry->policy('holidays')->rights->attribution`),
and a database made from it is shared alike. No rate limit, caching rule or User-Agent rule is
stated anywhere: the policy says the limits are unknown (`null`), not absent.

## Verified, and not

- Verified: the OpenAPI document (`/swagger/v1/swagger.json`, OpenAPI 3.0.4), the FAQ and the
  data's licence on 2026-11-10; three answers recorded that day at 04:21 UTC (France and Germany's
  public holidays of 2027, Bavaria's school holidays) are the tests' fixtures, kept whole; the
  live check (two calls) passed at 04:26 UTC.
- Not verified: the behaviour over a three-year span (never asked: one year per call), the
  `groupCode` of school holidays (not used), `text/calendar` answers.

## Licence

MIT (the package). The data: ODbL-1.0.
