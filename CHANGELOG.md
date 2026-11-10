# Changelog

## 1.x (unreleased)

- 2026-11-10: the OpenHolidays API as a declared provider (provider.json, no class): public,
  bank and optional holidays (/PublicHolidays) and school holidays (/SchoolHolidays, called only
  when asked), one call per year, names in the language asked, regions, types, half days; its
  usage policy (policy.json: free, commercial use allowed, ODbL attribution, limits unknown).
  Tests on answers recorded that day, the policy enforced through the registry, discovery by
  `DeclarativeFactory::installed()`, the bare check, a live check of two calls.
