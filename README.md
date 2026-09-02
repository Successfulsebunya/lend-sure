# KuLoan Ledger

**KuLoan Ledger** is a lightweight WordPress loan-management plugin for tracking borrowers, loan balances, monthly interest, repayments, extensions, penalties, acknowledgements, reminders, backups, corrections, and management reports from the WordPress dashboard.

- **Version:** 1.4.1
- **Default interest:** 20% per month, configurable
- **WordPress:** 6.4+
- **PHP:** 7.4+
- **Plugin URI:** https://github.com/Successfulsebunya/lend-sure
- **Author:** Moses Cursor
- **Author URI:** https://mosescursor.com/
- **License:** GPL-2.0-or-later

## Features

- Borrower creation and editing.
- Loan creation with automatic first-month interest.
- Configurable monthly interest and per-loan penalties.
- Partial payment allocation: **Interest → Penalty → Principal**.
- Loan extensions with administrator-selectable extension dates.
- Branded Loan Acknowledgement & Acceptance with two witness lines.
- Browser-native **Save PDF** acknowledgement workflow.
- Upload signed acknowledgement copies back to each loan.
- Due Today, Due This Week, Grace Period, Overdue, and Upcoming statuses.
- Manual borrower email reminders and optional daily lender/admin digest.
- Payment and transaction history.
- Dashboard **Total Expected Amount** and loan-register totals.
- Lightweight 12-month lending analytics.
- CSV loan-register export.
- Complete administrator backup and restore.
- Audit-safe **Void Payment** correction workflow.
- **Void / Cancel Loan** plus separately confirmed permanent deletion for invalid/test records.
- Management lending reports with date filters, CSV export, and **Print / Save PDF**.
- Explicit data-cleanup controls while normal uninstall preserves data by default.

## Installation

1. Download the release ZIP.
2. In WordPress, open **Plugins → Add New → Upload Plugin**.
3. Upload and activate KuLoan Ledger.
4. Open **KuLoan Ledger → Settings**.
5. Configure loan defaults, company/lender acknowledgement details, and reminder settings.
6. Add a borrower and create the first loan.

## Calculation Model

For **UGX 1,000,000 at 20% monthly interest**:

- Principal: UGX 1,000,000
- First-month interest: UGX 200,000
- Initial amount due: UGX 1,200,000

If the borrower pays UGX 400,000, the payment first clears UGX 200,000 interest and the remaining UGX 200,000 reduces principal. The resulting principal is UGX 800,000.

Payments are always allocated in this order:

**Interest → Penalty → Principal**

## Backup, Restore & Data Cleanup

Open **KuLoan Ledger → Tools**.

### Complete Backup

**Download Complete Backup** creates a portable JSON backup containing:

- borrowers;
- loans;
- payments;
- transactions;
- reminder history;
- KuLoan Ledger settings; and
- referenced company-logo and acknowledgement media when those files are readable.

Use a complete backup before migration, restore, permanent data cleanup, or uninstall.

### Restore

Restore is an administrator-only **replacement** operation, not a merge. It requires the administrator to type `RESTORE` exactly before the current ledger dataset is replaced with the selected backup.

### Uninstall safety

A normal uninstall preserves the existing `lendsure_*` database data. Administrators can explicitly opt in to database deletion on uninstall or use **Erase All Data & Deactivate** from Tools after making a backup.

Internal `LendSure_*` classes, `lendsure_*` database tables, options, actions, and stored identifiers are intentionally retained for backward compatibility with installations originally branded Lend Sure.

## Corrections and Audit History

### Void Payment

Payments are not silently removed from the ledger. The safe correction workflow is **Void Payment**.

A payment can be voided only when it is the latest eligible payment and no later balance-changing event depends on it. Voiding:

- restores the payment's principal component to outstanding principal;
- restores its interest component to accrued interest;
- restores its penalty component to accrued penalties;
- preserves the reason, administrator ID, original components, and timestamp in transaction history; and
- prevents the voided amount from inflating future totals and reports.

### Loan removal

Use **Void / Cancel Loan** when the record should remain available for audit history but should no longer count as an active loan.

For genuinely invalid or test records, the Tools page provides a separately confirmed **Permanent Delete Loan** action. It removes the loan and its related payments, transaction records, and reminders while retaining the borrower record. Optional acknowledgement-media deletion is separate.

## Business Reports

Open **KuLoan Ledger → Reports** and choose a From/To date range.

The management report includes:

- loans issued;
- borrowers served;
- principal issued;
- average loan size;
- total cash collected;
- principal collected;
- interest collected;
- penalties collected;
- **Lending Income**;
- active-loan exposure;
- current expected repayment;
- overdue loans and overdue exposure; and
- monthly lending and collection performance.

Reports can be exported to CSV or printed/saved as PDF for team discussions.

**Lending Income** means interest plus penalties actually collected. It is intentionally not labelled accounting profit because KuLoan Ledger does not track operating expenses, salaries, rent, taxes, write-offs, or other business costs.

## Loan Acknowledgements

Under **KuLoan Ledger → Settings**, configure:

- Company / Business Name
- Company Logo
- Company Details

Acknowledgements contain original principal, monthly interest, agreed late-payment penalty, due date, additional terms, borrower/lender signatures, and two witness signature areas.

The PDF workflow uses the browser's native print/PDF output rather than bundling a heavyweight PDF library.

## Reminders

Borrower reminders use WordPress `wp_mail()` and can send a lender/admin copy. An optional lender/admin digest is scheduled through WP-Cron. Reminder attempts are logged.

SMS is not bundled into v1.4.0 because reliable live SMS requires a provider account and usually incurs delivery or sender-ID costs.

## Security and Privacy

Administrative screens require the WordPress `manage_options` capability. State-changing actions use WordPress nonces, request data is sanitized, output is escaped, and plugin-owned database queries use WordPress database APIs and prepared statements where variables are present.

Loan records may contain personal and financial information. Secure the WordPress installation, restrict administrator access, maintain backups, and follow applicable data-protection requirements.

## Documentation

See [docs/USER-GUIDE.md](docs/USER-GUIDE.md) for the administrator guide.

## Development

### PHP syntax check

```bash
find . -name '*.php' -not -path './.git/*' -print0 | xargs -0 -n1 php -l
```

## Legal Notice

KuLoan Ledger is an administrative record-keeping tool and does not provide legal or financial advice. Review applicable lending, interest, penalty, tax, privacy, and document-enforceability requirements before using it for formal lending activity.

## Changelog

### 1.4.1

- Renamed the public plugin identity to **KuLoan Ledger**.
- Updated the text domain/permalink target to `kuloan-ledger`.
- Preserved existing `LendSure_*` classes, `lendsure_*` tables/options/actions, and stored data.
- No lending logic or database schema changes.

### 1.4.0

- Added complete administrator backup and restore.
- Added audit-safe payment void/reversal.
- Added loan void/cancel and protected permanent deletion controls.
- Added management lending reports with CSV and Print / Save PDF output.
- Added explicit uninstall cleanup controls while preserving data by default.

### 1.3.1

- Renamed the public plugin identity to KuLoan Ledger for WordPress.org review compliance.
- Changed the text domain to `kuloan-ledger` while preserving internal identifiers and stored data.
- Corrected WordPress.org contributor metadata.
- Moved acknowledgement styling and print behavior to WordPress-enqueued assets.

### 1.3.0

- Added borrower editing.
- Added Total Expected Amount to the dashboard.
- Added Loan Register Totals.
- Added lightweight 12-month lending analytics.

### 1.2.3

- Completed Plugin Check compliance cleanup.

### 1.2.0

- Added branded acknowledgements and per-loan penalty terms.

### 1.1.0

- Added due-date statuses and reminder workflows.

### 1.0.0

- Initial release.
