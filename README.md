# Your Loan Ledger

**Your Loan Ledger** is a lightweight WordPress loan-management plugin for tracking borrowers, loan balances, monthly interest, repayments, extensions, penalties, signed acknowledgements, due dates, reminders, and transaction history from the WordPress dashboard.

- **Version:** 1.3.1
- **Default interest:** 20% per month, configurable
- **WordPress:** 6.4+
- **PHP:** 7.4+
- **Plugin URI:** https://github.com/Successfulsebunya/lend-sure
- **Author:** Moses Cursor
- **Author URI:** https://mosescursor.com/
- **License:** GPL-2.0-or-later

## Features

- Borrower records with contact and identification details.
- Editable borrower profiles for correcting incomplete information or updating changed contact/identification details.
- Loan creation with automatic first-month interest.
- Configurable monthly interest per loan.
- Per-loan late penalty terms: fixed amount or percentage of outstanding principal.
- Editable one-off penalty application while retaining the agreed penalty on the loan record.
- Partial payment allocation: **Interest → Penalty → Principal**.
- Loan extensions with optional capitalization.
- Administrator-selectable extension date for retrospective loan updates.
- Custom acknowledgement header with **company name, logo, and company details**.
- Branded Loan Acknowledgement & Acceptance containing interest, due date, and agreed penalty terms.
- Single **Save PDF** acknowledgement workflow using the browser's native PDF output.
- Upload signed acknowledgement copies back to each loan.
- Due Today, Due This Week, Grace Period, Overdue, and Upcoming statuses.
- Manual borrower email reminders with a lender/admin copy and safer mail/SMTP failure handling.
- Optional daily admin/lender due-date digest through `wp_mail()` and WP-Cron.
- Reminder activity logging.
- Payment and transaction history.
- Dashboard **Total Expected Amount** across active loans.
- Loan-register totals for principal issued, outstanding balances, interest, penalties, and expected amount.
- Dependency-free 12-month performance chart showing loan volume and lending income collected.
- CSV export.
- Dedicated database tables rather than custom posts.

## Installation

1. Download the release ZIP.
2. In WordPress, open **Plugins → Add New → Upload Plugin**.
3. Upload and activate Your Loan Ledger.
4. Open **Your Loan Ledger → Settings**.
5. Configure loan defaults, company/lender acknowledgement details, and reminder settings.
6. Add a borrower and create the first loan.

## Recommended Settings

For the current lending workflow:

- Currency: `UGX`
- Default monthly interest: `20%`
- Default duration: `1 month`
- Grace period: your preferred number of days
- Default penalty: percentage or fixed amount
- Company/business name, logo, and header details

Defaults are only starting values. Interest and penalty terms can be set on each new loan.

## Loan Calculation Example

For **UGX 1,000,000 at 20% monthly interest**:

- Principal: UGX 1,000,000
- First-month interest: UGX 200,000
- Initial amount due: UGX 1,200,000

If the borrower pays UGX 400,000:

1. UGX 200,000 clears interest.
2. UGX 200,000 reduces principal.
3. New principal: UGX 800,000.
4. Projected next-month interest at 20%: UGX 160,000.

## Penalty Terms

Each loan stores its own:

- penalty type (`percentage` or `fixed`), and
- penalty value.

These are captured when the loan is created and displayed in the acknowledgement. When an administrator applies a penalty, the agreed values are prefilled but may be intentionally adjusted for a one-off charge. The actual applied type/value is retained in transaction metadata.

## Loan Acknowledgements

Version 1.3.0 retains the business header and acknowledgement workflow. Under **Your Loan Ledger → Settings**, you can configure:

- Company / Business Name
- Company Logo
- Company Details

The acknowledgement contains the original principal, monthly interest, agreed late penalty, original due date, additional terms, borrower/lender signatures, and two witness signature areas.

### PDF workflow

1. Open a loan.
2. Click **Save Acknowledgement PDF**.
3. Click the single **Save PDF** action.
4. Choose **Save as PDF** in the browser destination dialog.
5. Obtain signatures as required.
6. Upload the signed PDF/image back to the same loan record.

This keeps the plugin lightweight by avoiding a large bundled PDF-rendering library.

## Reminders

Version 1.3.0 retains the reminder workflow and hardened mail failure handling:

- borrower reminders are sent manually via WordPress `wp_mail()` and a lender/admin copy is sent to the configured digest email;
- an optional daily lender/admin digest is scheduled using WP-Cron;
- reminder attempts are logged.

SMS is not built into v1.3.1 because reliable live SMS requires a provider account and usually incurs delivery or sender-ID costs. The reminder layer is intentionally separated so an SMS provider can be added later without changing the loan ledger.

## Documentation

See [docs/USER-GUIDE.md](docs/USER-GUIDE.md) for the complete administrator guide.

## Development

The repository contains the plugin source directly at the repository root so it can be managed easily with GitHub Desktop or Git.

### PHP syntax check

```bash
find . -name '*.php' -not -path './.git/*' -print0 | xargs -0 -n1 php -l
```

## Security and Privacy

Your Loan Ledger administration screens require the WordPress `manage_options` capability. Actions use WordPress nonces and input sanitization. Signed acknowledgement files are stored through the WordPress Media Library.

Loan records may contain personal and financial information. Secure the WordPress installation, restrict administrator accounts, maintain backups, and follow applicable data-protection requirements.

## Legal Notice

Your Loan Ledger is an administrative record-keeping tool and does not provide legal or financial advice. Review applicable lending, interest, penalty, tax, privacy, and document-enforceability requirements before using it for formal lending activity.

## Changelog

### 1.3.0

- Added borrower editing with secure update handling.
- Added borrower `updated_at` tracking.
- Added Total Expected Amount to the dashboard.
- Added Loan Register Totals beneath the Loans table.
- Added a dependency-free 12-month performance chart for principal issued and lending income collected.
- Clarified that lending income is interest and penalties collected, not accounting profit.
- Bumped the database schema to 1.3.0.

### 1.2.3

- Completed the second WordPress Plugin Check cleanup pass.
- Corrected translator-comment placement for multiline placeholder strings.
- Documented cache invalidation on the remaining migration/update database writes.
- No loan calculation or database schema changes.

### 1.2.2

- WordPress Plugin Check compliance maintenance release.
- Prepared custom-table identifiers with `%i` placeholders and centralized data access.
- Added object caching and cache invalidation for custom-table reads.
- Hardened nonce verification and request sanitization.
- Corrected output escaping and translation placeholder handling.
- Cleaned installable package metadata and WordPress.org short description.


### 1.2.1

- Hardened email reminder sending and failure logging.
- Added administrator-selectable Extension Date for retrospective updates.
- Centered acknowledgement logo with company details beneath it.
- Treated company/business details as the lender identity on acknowledgement documents.
- Replaced borrower thumbprint area with a second witness signature area.

### 1.2.0

- Added company name, logo, and company details to acknowledgement settings/header.
- Added per-loan penalty type/value.
- Added agreed penalty terms to acknowledgement documents.
- Made penalty application values editable.
- Changed the primary acknowledgement action to Save PDF.
- Added GitHub Plugin URI and mosescursor.com Author URI.
- Manual borrower reminder emails now send a lender/admin copy where configured.
- Updated WordPress-style `readme.txt` and administrator documentation.

### 1.1.0

- Added due-date workflow statuses.
- Added Reminders module.
- Added borrower email reminders.
- Added daily admin digest.
- Added reminder activity logging.

### 1.0.0

- Initial release.
