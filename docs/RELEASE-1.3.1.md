# Your Loan Ledger 1.3.1 — WordPress.org Review Compliance Release

## Scope

Version 1.3.1 is a compliance and maintenance release only. It introduces no new lending features and does not change loan calculation rules.

## Public identity changes

- Public plugin name: **Your Loan Ledger**
- Proposed WordPress.org slug: `your-loan-ledger`
- Text domain: `your-loan-ledger`
- WordPress.org contributor: `mosescursor`
- Plugin version: `1.3.1`

The GitHub project URL remains `https://github.com/Successfulsebunya/lend-sure` because that is the existing source repository.

## Backward compatibility

Existing internal identifiers are intentionally preserved. This includes:

- `LendSure_*` PHP classes
- `lendsure_*` database tables
- `lendsure_*` options/settings
- `lendsure_*` admin page/action/cron identifiers
- the database cache group and existing schema version

This allows existing borrowers, loans, payments, transaction history, reminders, penalties, extensions, acknowledgements, and settings to continue using the same stored data.

## WordPress.org review fixes

- Removed direct `<style>` output from the acknowledgement template.
- Added `assets/acknowledgement.css` and enqueued it with `wp_register_style()` / `wp_enqueue_style()`.
- Replaced the acknowledgement button's inline JavaScript event with `assets/acknowledgement.js`, enqueued through WordPress.
- Added `assets/admin.js` so dashboard graph heights no longer require inline `style` attributes.
- Corrected the WordPress.org contributor username from `mocursor` to `mosescursor`.
- Updated public-facing branding and documentation to Your Loan Ledger.
- Updated all translation calls to the `your-loan-ledger` text domain.
- Reviewed translated placeholder strings and added/retained translator comments.
- Added an extra loan existence check before accepting a signed acknowledgement upload.
- Kept existing nonce, capability, sanitization, escaping, prepared-query and safe-redirect protections intact.

## Data/schema

There is **no database schema change** in 1.3.1. `LendSure_DB::DB_VERSION` remains `1.3.0` because the schema itself is unchanged.

## Functional regression scope

The compliance work intentionally leaves the existing business logic unchanged, including:

- borrower creation/editing
- loan creation
- interest and principal calculations
- partial-payment allocation
- extensions and extension dates
- penalties
- transaction/payment history
- acknowledgement data and witnesses
- company/lender settings and logo
- email reminders
- dashboard and loan-list totals
- 12-month lending analytics

