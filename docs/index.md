# omnical/openholidays

A declared provider: `provider.json` and `policy.json`, no class. See the
[README](../README.md) for the calls, the mapping and the policy, and omnical's
[declarative providers](https://github.com/glitchr-omnisense/omnical/blob/1.x/docs/declarative.md)
for the mechanism.

```php
$factory = DeclarativeFactory::fromFile('vendor/omnical/openholidays/provider.json', $http);
// or every installed declared provider at once:
$factories = DeclarativeFactory::installed($http);
```

Tests: `docker compose run --rm omnical test --testsuite openholidays` in omnical's harness
(recorded answers), `live` for the two real calls.
