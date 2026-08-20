=== Lend Sure ===
Contributors: mocursor
Tags: loans, lending, payments, interest, ledger
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Track private loans, borrowers, repayments, interest, penalties, reminders, extensions, and signed acknowledgements in WordPress.

== Description ==

Lend Sure is a lightweight WordPress dashboard plugin for administrators who need to manage private loans without running a separate loan-management application.

It provides a clear loan ledger for borrowers, principal, monthly interest, due dates, partial repayments, extensions, late penalties, signed loan acknowledgements, due-date reminders, and transaction history.

The default monthly interest rate is 20%, but it can be changed in Settings or for an individual loan.

= Core features =

* Borrower records with contact and identification details.
* Editable borrower records for correcting or updating borrower details.
* Loan creation with automatic first-month interest.
* Default 20% monthly interest, configurable per loan.
* Per-loan late penalty terms: percentage or fixed amount.
* Editable one-off penalty when applying a charge.
* Partial payments allocated in the order: interest, penalty, principal.
* Loan extensions with optional capitalization of unpaid interest and penalties.
* Administrator-selectable extension date for retrospective record entry.
* Customizable acknowledgement header with company/business name, logo, and company details.
* Loan Acknowledgement & Acceptance containing the agreed interest and penalty terms.
* One primary Save PDF action for acknowledgement documents using the browser's native PDF output.
* Upload signed PDF, JPG/JPEG, or PNG acknowledgement copies back to the loan record.
* Due Today, Due This Week, Grace Period, Overdue, and Upcoming statuses.
* Manual borrower email reminders with a lender/admin copy and safer mail/SMTP failure handling.
* Optional daily lender/admin due-date digest using WordPress mail and WP-Cron.
* Reminder activity log.
* Full payment and transaction history.
* Dashboard Total Expected Amount across active loans.
* Loan Register Totals beneath the Loans table.
* Dependency-free 12-month performance chart for principal issued and lending income collected.
* CSV export of the loan register.
* Dedicated custom database tables for loan data.

Project source: https://github.com/Successfulsebunya/lend-sure

Author: https://mosescursor.com/

== Installation ==

1. In WordPress, go to **Plugins > Add New > Upload Plugin**.
2. Upload the Lend Sure ZIP file and click **Install Now**.
3. Activate **Lend Sure**.
4. Open **Lend Sure > Settings**.
5. Configure currency, default interest, grace period, default penalty, company/lender acknowledgement details, and reminder settings.
6. Go to **Lend Sure > Borrowers** and add a borrower.
7. Go to **Lend Sure > Loans > Add New** to create the first loan.

== Frequently Asked Questions ==

= What is the default interest rate? =

The default is 20% per month. It can be changed globally in Settings or changed when creating an individual loan.

= How are partial payments allocated? =

Payments are allocated to outstanding interest first, then outstanding penalties, then principal.

= Can each loan have a different penalty? =

Yes. Version 1.3.0 stores the agreed penalty type and value with each loan. The penalty is included in the acknowledgement terms. When applying a penalty, the administrator can also edit the value for an intentional one-off charge.

= Does Lend Sure automatically add penalties when a loan becomes overdue? =

No. Timing statuses are automatic, but penalty application remains an administrator-controlled action.

= How do I create a PDF acknowledgement? =

Open the loan and choose **Save Acknowledgement PDF**. On the acknowledgement screen click **Save PDF**, then select the browser's **Save as PDF** destination. This avoids adding a large PDF library to the plugin.

= Can I upload the signed acknowledgement? =

Yes. Signed PDF, JPG/JPEG, and PNG files can be attached to the corresponding loan record.

= How are reminders sent? =

Borrower reminders and the lender/admin daily digest currently use WordPress `wp_mail()`. The daily digest is scheduled with WP-Cron and therefore depends on site traffic for execution.

= Does the plugin send SMS? =

Not in version 1.3.0. SMS requires a live messaging provider and may involve per-message or sender-ID charges. The reminder architecture can be extended with an SMS provider in a later release.

== Screenshots ==

1. Lend Sure dashboard with active and due-date summaries.
2. Borrower management screen.
3. New loan form with interest and per-loan penalty terms.
4. Loan detail screen with payment, dated extension, penalty, reminder, and transaction panels.
5. Branded Loan Acknowledgement & Acceptance with centered logo and two witness lines.
6. Reminder follow-up queue.
7. Settings with company acknowledgement header and reminder configuration.

== Changelog ==

= 1.3.0 =
* Added Edit Borrower workflow for correcting incomplete or changed borrower details.
* Added borrower updated-at tracking.
* Added Total Expected Amount to the dashboard.
* Added Loan Register Totals beneath the Loans table.
* Added a lightweight dependency-free 12-month performance chart for principal issued and lending income collected.
* Clarified that lending income is not accounting profit because operating expenses, taxes, and write-offs are not tracked.
* Bumped the custom database schema to version 1.3.0.

= 1.2.3 =
* Completed the second WordPress Plugin Check compliance pass.
* Moved translator comments directly beside the placeholder translation calls flagged by Plugin Check.
* Documented cache invalidation for the remaining custom-table migration and update writes so Plugin Check no longer reports false-positive NoCaching warnings.
* No loan calculation or database schema changes.

= 1.2.2 =
* Improved WordPress Plugin Check compliance.
* Reworked custom-table queries to use prepared identifier placeholders and object caching.
* Hardened nonce verification and request sanitization in admin actions.
* Added missing translator comments and corrected translatable placeholder strings.
* Improved output escaping across admin screens.
* Removed hidden development files from the installable WordPress package.
* Shortened the WordPress.org short description to the supported length.
* Kept CSV streaming lightweight while removing the unnecessary direct fclose call.

= 1.2.1 =
* Hardened borrower email reminders so mail/SMTP failures are caught, logged, and shown as admin notices instead of causing a WordPress critical-error screen where possible.
* Added an Extension Date field for backdated extension records.
* Extension-related ledger entries now use the actual extension date entered by the administrator.
* Centered the acknowledgement logo and placed company details directly beneath it.
* Treated the company/business as the lender identity on the acknowledgement.
* Replaced the optional borrower thumbprint area with a second witness name/signature area.

= 1.2.0 =
* Added company/business name, company details, and company logo settings for acknowledgement headers.
* Added per-loan penalty type and penalty value fields.
* Added agreed penalty terms to the Loan Acknowledgement & Acceptance.
* Made the Apply Penalty type/value editable for intentional one-off charges.
* Replaced the acknowledgement's primary Print wording with a single Save PDF workflow.
* Added Plugin URI pointing to the GitHub repository.
* Added Author URI pointing to mosescursor.com.
* Manual borrower reminder emails now send a lender/admin copy where configured.
* Updated project and administrator documentation.

= 1.1.0 =
* Added Due Today, Due This Week, Grace Period, Overdue, and Upcoming timing states.
* Added Reminders admin module.
* Added manual borrower email reminders.
* Added optional daily admin due-date digest.
* Added reminder activity log.
* Added reminder settings and updated dashboard workflow.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.3.0 =
Adds borrower editing, dashboard/loan totals, and 12-month lending performance analytics. The database upgrade adds borrower update tracking automatically.

= 1.2.3 =
Completes the remaining Plugin Check cleanup from 1.2.2. No loan data migration is required.

= 1.2.2 =
Improves WordPress Plugin Check compliance and hardens query preparation, nonce handling, escaping, internationalization, and packaging. No loan data migration is required.

= 1.2.1 =
Improves reminder error handling, adds backdated extension dates, and refines the acknowledgement header and witness section. No loan data migration is required.

= 1.2.0 =
Adds branded acknowledgement headers and loan-specific penalty terms. Existing loans are retained and receive the configured default penalty values during the database upgrade.

== Legal Notice ==

Lend Sure is an administrative record-keeping tool, not legal or financial advice. Review the laws and regulations applicable to lending, interest, penalties, privacy, taxation, and document enforceability in your jurisdiction before relying on the plugin for formal lending activities.
