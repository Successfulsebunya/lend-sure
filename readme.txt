=== Your Loan Ledger ===
Contributors: mosescursor
Tags: loans, lending, payments, interest, ledger
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Track private loans, borrowers, repayments, interest, penalties, reminders, extensions, backups, and lending reports in WordPress.

== Description ==

Your Loan Ledger is a lightweight WordPress dashboard plugin for administrators who need to manage private loans without running a separate loan-management application.

It provides a loan ledger for borrowers, principal, monthly interest, due dates, partial repayments, extensions, late penalties, signed loan acknowledgements, due-date reminders, transaction history, backups, corrections, and management reporting.

The default monthly interest rate is 20%, but it can be changed in Settings or for an individual loan.

= Core features =

* Borrower records with editable contact and identification details.
* Loan creation with automatic first-month interest.
* Default 20% monthly interest, configurable per loan.
* Per-loan late penalty terms: percentage or fixed amount.
* Partial payments allocated in the order: interest, penalty, principal.
* Loan extensions with optional capitalization of unpaid interest and penalties.
* Administrator-selectable extension date for retrospective record entry.
* Customizable acknowledgement header with company/business name, logo, and company details.
* Loan Acknowledgement & Acceptance with two witness lines.
* Browser-native Save PDF workflow for acknowledgement documents.
* Upload signed PDF, JPG/JPEG, or PNG acknowledgement copies to the loan record.
* Due Today, Due This Week, Grace Period, Overdue, and Upcoming statuses.
* Manual borrower email reminders with a lender/admin copy.
* Optional daily lender/admin due-date digest using WordPress mail and WP-Cron.
* Reminder activity log.
* Full payment and transaction history.
* Dashboard Total Expected Amount across active loans.
* Loan Register Totals beneath the Loans table.
* Dependency-free 12-month performance chart.
* CSV export of the loan register.
* Complete administrator JSON backup and restore.
* Audit-safe payment reversal using a Void Payment workflow.
* Loan void/cancel and separately protected permanent deletion controls.
* Business lending reports with date filters, CSV export, and Print / Save PDF.
* Explicit uninstall cleanup controls; normal uninstall preserves data by default.
* Dedicated custom database tables for loan data.

Project source: https://github.com/Successfulsebunya/lend-sure

Author: https://mosescursor.com/

== Installation ==

1. In WordPress, go to **Plugins > Add New > Upload Plugin**.
2. Upload the Your Loan Ledger ZIP file and click **Install Now**.
3. Activate **Your Loan Ledger**.
4. Open **Your Loan Ledger > Settings**.
5. Configure currency, default interest, grace period, default penalty, company/lender details, and reminder settings.
6. Go to **Your Loan Ledger > Borrowers** and add a borrower.
7. Go to **Your Loan Ledger > Loans > Add New** to create the first loan.

== Frequently Asked Questions ==

= What is the default interest rate? =

The default is 20% per month. It can be changed globally in Settings or changed when creating an individual loan.

= How are partial payments allocated? =

Payments are allocated to outstanding interest first, then outstanding penalties, then principal.

= Can each loan have a different penalty? =

Yes. Each loan stores its agreed penalty type and value. The penalty is included in the acknowledgement terms.

= Does Your Loan Ledger automatically add penalties when a loan becomes overdue? =

No. Timing statuses are automatic, but penalty application remains an administrator-controlled action.

= How do I create a PDF acknowledgement? =

Open the loan and choose **Save Acknowledgement PDF**. On the acknowledgement screen choose **Save PDF**, then select the browser's **Save as PDF** destination.

= Can I upload the signed acknowledgement? =

Yes. Signed PDF, JPG/JPEG, and PNG files can be attached to the corresponding loan record.

= How are reminders sent? =

Borrower reminders and the lender/admin daily digest use WordPress `wp_mail()`. The daily digest is scheduled with WP-Cron and therefore depends on site traffic for execution.

= Does the plugin send SMS? =

No. SMS requires a live messaging provider and is not bundled into version 1.4.0.

= What is the difference between CSV export and Complete Backup? =

CSV export is intended for viewing loan-register data in spreadsheet software. Complete Backup creates a portable JSON package of the plugin's ledger tables, settings, and referenced acknowledgement/company-logo media when those files can be read. Use Complete Backup before migration, major maintenance, restore, or data cleanup.

= Does restoring a backup merge with current data? =

No. Restore is an administrator-only replacement operation. It replaces the current Your Loan Ledger dataset with the selected backup and requires the administrator to type `RESTORE` exactly.

= Can a payment be deleted? =

The normal correction workflow is **Void Payment**, not silent deletion. A safe void reverses the payment's principal, interest, and penalty allocation and stores the original payment details in the audit transaction history. To protect balances, only the latest payment can be voided when no later balance-changing activity depends on it.

= Can a loan be deleted? =

A loan can be **Void / Cancelled** while preserving its history. For invalid or test records, administrators also have a separately confirmed permanent-delete action that removes the loan and its related payment, transaction, and reminder records while retaining the borrower record.

= Will uninstalling the plugin delete my data? =

Not by default. Your Loan Ledger preserves its database data on a normal uninstall. Under **Your Loan Ledger > Tools**, an administrator can explicitly enable database deletion on uninstall or use the immediate **Erase All Data & Deactivate** control. Download a Complete Backup first.

= What does Lending Income mean in Reports? =

Lending Income is interest plus penalties actually collected. It is not accounting net profit because the plugin does not track operating expenses, taxes, write-offs, salaries, rent, or other business costs.

== Screenshots ==

1. Dashboard with active-loan and expected-repayment totals.
2. Borrower management and editing screen.
3. New loan form with interest and penalty terms.
4. Loan detail screen with payment, extension, penalty, reminder, and transaction panels.
5. Branded Loan Acknowledgement & Acceptance with centered logo and two witness lines.
6. Reminder follow-up queue.
7. Management lending report.
8. Backup, restore, corrections, and data-cleanup tools.

== Changelog ==

= 1.4.0 =
* Added complete administrator JSON backup and restore for ledger data and plugin settings.
* Backups include referenced company-logo and acknowledgement media when available.
* Added audit-safe Void Payment workflow that reverses payment allocation while preserving the correction in transaction history.
* Added loan Void / Cancel and separately confirmed permanent-delete controls.
* Added management business reports with date filters, lending/collection metrics, current portfolio exposure, monthly performance, CSV export, and Print / Save PDF.
* Added explicit uninstall cleanup policy and immediate Erase All Data & Deactivate control.
* Normal uninstall continues to preserve plugin data unless the administrator explicitly opts in to deletion.

= 1.3.1 =
* Renamed the public plugin identity from Lend Sure to Your Loan Ledger for WordPress.org review compliance.
* Changed the text domain to `your-loan-ledger` while preserving existing internal identifiers and stored data.
* Corrected WordPress.org contributor metadata.
* Moved acknowledgement style and print behavior to WordPress-enqueued assets.
* Completed review-compliance security, sanitization, escaping, SQL, and internationalization cleanup.

= 1.3.0 =
* Added Edit Borrower workflow.
* Added Total Expected Amount to the dashboard.
* Added Loan Register Totals beneath the Loans table.
* Added a lightweight 12-month performance chart for principal issued and lending income collected.

= 1.2.3 =
* Completed Plugin Check compliance fixes for internationalization and custom-table cache handling.

= 1.2.0 =
* Added branded acknowledgement header and per-loan penalty terms.
* Added Save PDF acknowledgement workflow and reminder-mail hardening.

= 1.1.0 =
* Added due-date statuses, reminder queue, manual borrower email reminders, and optional daily lender/admin digest.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.4.0 =
Adds complete backup/restore, audit-safe payment reversal, loan correction controls, management reports, and explicit uninstall data-cleanup controls. Existing borrowers, loans, payments, settings, and internal `lendsure_*` identifiers are preserved.
