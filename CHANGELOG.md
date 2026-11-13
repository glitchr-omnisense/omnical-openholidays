# Changelog

## [Unreleased]

## [1.0.0] - 2026-11-13

The first stable version. It requires `omnisense/contracts` `^1.0`, `omnisense/omnical` `^1.0` with no stability flag, so `composer require omnical/openholidays` installs it in a project with Composer's default minimum-stability; the 1.x series follows semantic versioning. A tag `vX.Y.Z` is released by `.github/workflows/release.yml` once the package's CI passes on it.

- 2026-11-10: the OpenHolidays API as a declared provider (provider.json, no class): public,
  bank and optional holidays (/PublicHolidays) and school holidays (/SchoolHolidays, called only
  when asked), one call per year, names in the language asked, regions, types, half days; its
  usage policy (policy.json: free, commercial use allowed, ODbL attribution, limits unknown).
  Tests on answers recorded that day, the policy enforced through the registry, discovery by
  `DeclarativeFactory::installed()`, the bare check, a live check of two calls.
