# Plugin Check remediation — Lend Sure 1.2.3

The second Plugin Check scan of Lend Sure 1.2.2 reported only four findings:

- Two `WordPress.WP.I18n.MissingTranslatorsComment` errors in `class-lendsure-reminders.php`.
- Two `WordPress.DB.DirectDatabaseQuery.NoCaching` warnings in `class-lendsure-db.php`.

## Fixes

### Translation comments

The translator comments already described the placeholders correctly, but Plugin Check requires them to sit immediately adjacent to the `__()` call when the translation call is nested inside `sprintf()`. Version 1.2.3 moves those comments into the nested call position.

### Database cache warnings

Both remaining database findings are write operations, not uncached reads:

1. A one-time migration `UPDATE` whose surrounding activation routine flushes the Lend Sure cache.
2. The centralized `update()` helper, which calls `flush_cache()` immediately after every successful write.

The PHPCS annotations now explicitly document both `DirectQuery` and `NoCaching` for those intentional plugin-owned custom-table writes.

## Functional impact

No loan calculations, database schema, loan terms, payment allocation, extension behavior, penalties, acknowledgements, or reminder behavior changed in this release.
