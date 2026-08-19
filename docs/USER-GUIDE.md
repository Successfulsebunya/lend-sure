# Lend Sure Administrator User Guide

## 1. Introduction

Lend Sure is a WordPress dashboard plugin for managing private loans. It helps the administrator track who borrowed money, how much was lent, the monthly interest, the payment due date, partial payments, loan extensions, penalties, signed acknowledgement documents, and the complete transaction history of each loan.

The default monthly interest rate is **20%**, although the rate can be changed in Settings or changed for an individual loan when it is created.

---

## 2. Installing Lend Sure

1. Log in to WordPress as an Administrator.
2. Go to **Plugins → Add New**.
3. Click **Upload Plugin**.
4. Select the Lend Sure ZIP file.
5. Click **Install Now**.
6. After installation, click **Activate Plugin**.
7. A new **Lend Sure** menu appears in the WordPress dashboard.

After activation, configure the plugin before entering the first loan.

---

## 3. First-Time Setup: Settings Module

Open **Lend Sure → Settings**.

### Loan Defaults

#### Currency
Sets the currency label displayed throughout the plugin.

Example:

`UGX`

#### Default Monthly Interest (%)
Sets the interest rate automatically entered when a new loan is created.

The default value is:

`20% per month`

You can still change the interest rate for an individual loan before saving it.

#### Default Duration (months)
Sets the normal length of a new loan.

For example, a value of `1` means the default due date is approximately one month after the start date.

#### Grace Period (days)
Stores the preferred grace period for your lending policy.

> Note: In version 1.2.1, the grace-period setting controls the **Grace Period** timing label. Penalties remain administrator-controlled and are not automatically charged after the grace period.

#### Penalty Type
Choose how the default penalty is calculated:

- **Percentage of outstanding principal** — for example, 5% of the remaining principal.
- **Fixed amount** — for example, UGX 20,000.

#### Penalty Value
Enter the default percentage or fixed amount. In version 1.2.1 this is used as the starting penalty when creating a new loan; each loan stores its own agreed penalty terms.

### Acknowledgement Header / Company

These fields create a proper document header for the Loan Acknowledgement & Acceptance:

- Company / Business Name
- Company Details — for example registration information, email, website, or address
- Company Logo

The company/business is treated as the lender on the acknowledgement. The logo is centered, with the company name and company details directly beneath it. A compact horizontal or square logo works best.

Click **Save Settings** after making changes.

---

## 4. Dashboard Module

Open **Lend Sure → Dashboard**.

The Dashboard gives a quick summary of the lending position.

### Dashboard Cards

#### Active Loans
Shows the number of loans that have not yet been fully paid.

#### Outstanding Principal
Shows the total remaining principal across active loans.

#### Outstanding Interest
Shows the total currently accrued interest across active loans.

#### Overdue
Shows the total amount due on active loans whose due dates have passed.

### Due-Date Workflow Table

This table lists active loans ordered by due date. It shows:

- Borrower
- Due Date
- Total Due
- Status
- Open link

A loan whose due date has passed is marked **Overdue**.

Use **Open** to go directly to the full loan record.

### Quick Actions

The Dashboard includes buttons for:

- **Add Loan**
- **Add Borrower**

---

## 5. Borrowers Module

Open **Lend Sure → Borrowers**.

This module stores the people who receive loans.

### Adding a Borrower

1. Click **Add New**.
2. Enter the borrower's information.
3. Click **Save Borrower**.

Available fields are:

- **Full Name** — required.
- **Phone** — borrower's contact number.
- **Email** — optional email address.
- **Address** — physical/contact address.
- **National ID / Identification** — identification reference.
- **Notes** — any useful administrative notes.

### Borrower List

The Borrowers screen displays:

- Name
- Phone
- Email
- National ID

A borrower must exist before a loan can be created for them.

---

## 6. Loans Module

Open **Lend Sure → Loans**.

The Loans screen is the main loan register. It displays:

- Loan ID
- Borrower
- Current Principal
- Interest Rate
- Due Date
- Total Due
- Status

Click **Open** on a loan to manage it.

### Creating a New Loan

1. Go to **Lend Sure → Loans**.
2. Click **Add New**.
3. Select a borrower.
4. Enter the principal amount.
5. Confirm or change the monthly interest rate.
6. Confirm the **Late Penalty Type**.
7. Confirm or change the **Late Penalty Value**.
8. Confirm the start date.
9. Confirm the due date.
10. Enter the loan purpose if required.
11. Add any additional terms that should appear on the acknowledgement.
12. Click **Create Loan & Generate Acknowledgement**.

### Loan Fields

#### Borrower
Selects the person receiving the loan.

#### Principal Amount
The original amount being lent before interest.

#### Monthly Interest (%)
The interest rate applied to the loan. The default is 20%.

#### Late Penalty Type
Choose whether this particular loan uses a percentage of outstanding principal or a fixed late-payment amount.

#### Late Penalty Value
The agreed penalty amount/rate for this loan. It is stored with the loan and printed in the acknowledgement terms.

#### Start Date
The date the loan begins.

#### Due Date
The date payment is expected.

#### Purpose
Optional reason for the loan.

#### Additional Terms
Optional terms printed on the acknowledgement document.

---

## 7. Understanding a Loan Record

Open any loan from **Lend Sure → Loans**.

At the top of the loan screen, Lend Sure shows four current figures:

- **Principal** — remaining unpaid principal.
- **Interest Due** — currently accrued unpaid interest.
- **Penalty Due** — penalties that have been applied and not yet cleared.
- **Total Due** — principal + interest + penalty.

### Loan Details Panel

The panel also shows:

- Borrower
- Phone
- Original principal
- Interest rate per month
- Projected next-month interest
- Start date
- Current due date
- Status

### Projected Next-Month Interest

This figure is calculated from the **current remaining principal**, not the original principal.

Example:

If the remaining principal is UGX 800,000 and the interest rate is 20%:

`UGX 800,000 × 20% = UGX 160,000`

The projected next-month interest is therefore UGX 160,000.

---

## 8. Loan Acknowledgement Module

Every loan includes a Loan Acknowledgement section.

The acknowledgement is intended to create a branded PDF record of the original loan terms for signing. It can include your company/business name, logo, company details, lender details, interest, due date, agreed penalty, and additional terms.

### Creating and Signing the Acknowledgement

1. Create the loan.
2. Open the loan record.
3. Find **Loan Acknowledgement**.
4. Click **Save Acknowledgement PDF**.
5. Review the document.
6. Click the single **Save PDF** action.
7. In the browser dialog choose **Save as PDF**. Printing is still available from that same browser dialog if you need a paper copy.
8. Have the borrower, lender/authorized signatory, and one or both witnesses complete the required signature areas.
9. Scan the signed document or take a clear photograph.
10. Return to the same loan record.
11. Choose the signed file.
12. Click **Upload Signed Copy**.

Accepted file types are:

- PDF
- JPG/JPEG
- PNG

Once uploaded, **Open uploaded document** appears in the loan record.

### Replacing a Signed Copy

If the wrong document was uploaded or a clearer signed copy becomes available:

1. Open the loan.
2. Select the replacement file.
3. Click **Replace Signed Copy**.

The loan record will point to the new attachment.

### Record-Keeping Principle

The acknowledgement represents the original loan agreement. Later payments or extensions update the live loan record and transaction history rather than rewriting the original signed terms.

---

## 9. Recording Payments

Open the loan and find **Record Payment**.

Enter:

- Amount
- Payment Date
- Method
- Reference

Examples of payment methods:

- Cash
- Mobile Money
- Bank

The Reference field can contain a Mobile Money transaction ID, bank reference, receipt number, or another identifying reference.

Click **Record Payment**.

### How Payments Are Allocated

Lend Sure automatically allocates each payment in this order:

1. Interest
2. Penalty
3. Principal

### Example

Loan position:

- Principal: UGX 1,000,000
- Interest Due: UGX 200,000
- Penalty: UGX 0
- Total Due: UGX 1,200,000

Borrower pays UGX 400,000.

Allocation:

- UGX 200,000 → Interest
- UGX 0 → Penalty
- UGX 200,000 → Principal

New principal:

`UGX 800,000`

Projected next-month interest at 20%:

`UGX 160,000`

### Full Payment

When principal, interest, and penalties have all been cleared, the loan status becomes **Paid** and payment/extension/penalty controls are no longer shown for that loan.

---

## 10. Payments History

The **Payments** section at the bottom of a loan shows every recorded payment.

For each payment it shows:

- Date
- Amount
- Breakdown

The breakdown identifies how much of that payment went to:

- Interest
- Penalty
- Principal

This helps explain why the principal changed after a partial payment.

---

## 11. Extending a Loan

If the borrower needs more time, open the loan and use **Extend Loan**.

### Steps

1. Open the loan.
2. Set **Extension Date** to the actual date the extension was agreed. If you are updating the record later, you may choose that earlier date.
3. Enter the number of additional months.
4. Decide whether to keep **Capitalize unpaid interest and penalty into the new principal** checked.
5. Click **Extend Loan**.

The Extension Date is used for the extension, capitalization, and extension-interest entries in the transaction history. The new due date still moves forward from the loan's existing due date by the number of months selected.

### With Capitalization Enabled

If capitalization is checked:

1. Outstanding interest and penalties are added to the principal.
2. Existing accrued interest and penalty are reset after capitalization.
3. Fresh interest is calculated on the new principal for the extension period.
4. The due date is moved forward by the selected number of months.

Example:

Before extension:

- Principal: UGX 1,000,000
- Interest: UGX 200,000
- Penalty: UGX 0

After capitalization:

- New principal: UGX 1,200,000
- New one-month interest at 20%: UGX 240,000
- New total due: UGX 1,440,000

### Without Capitalization

If capitalization is not checked:

- Existing principal remains principal.
- Existing interest and penalty remain outstanding.
- New extension-period interest is added to the interest due.
- The due date is extended.

### Multiple-Month Extension

If the administrator extends a loan by more than one month, the plugin calculates extension interest for the selected number of months using the current/new principal and the loan's monthly interest rate.

---

## 12. Applying a Penalty

Open the loan and find **Apply Penalty**.

The screen starts with the **penalty terms agreed for that specific loan**. The type and value are editable before applying the charge, which allows an intentional one-off adjustment without changing the original acknowledgement terms.

### Steps

1. Open the loan.
2. Review the agreed penalty type/value shown on screen.
3. If necessary, edit the type or value for this one penalty application.
4. Edit the note if necessary.
5. Click **Apply Penalty**.

The penalty is added to **Penalty Due** and recorded in Transaction History.

### Percentage Penalty

If the penalty is configured as a percentage, it is calculated from the outstanding principal.

Example:

- Outstanding principal: UGX 800,000
- Penalty: 5%
- Penalty added: UGX 40,000

### Fixed Penalty

If configured as fixed:

- Penalty value: UGX 20,000
- Each time the administrator applies that fixed penalty, UGX 20,000 is added unless the value is intentionally edited before applying it.

> Important: Version 1.2.1 does not automatically apply penalties. The administrator decides when a penalty should be applied. The transaction record stores the applied penalty type/value.

---

## 13. Transaction History

Every loan contains a **Transaction History** table.

This is the audit trail for the loan.

It records events such as:

- Loan creation
- Interest charged
- Payment received
- Loan extension
- Interest/penalty capitalization
- Penalty charged
- Signed acknowledgement upload

Each record includes:

- Date
- Type
- Amount
- Note

Use Transaction History when you need to understand how the current loan balance was reached.

---

## 14. Exporting the Loan Register

Go to **Lend Sure → Loans** and click **Export CSV**.

The plugin downloads a CSV file containing the loan register.

The export includes information such as:

- Loan ID
- Borrower
- Phone
- Email
- Original Principal
- Current Principal
- Interest Rate
- Interest Due
- Penalty Due
- Start Date
- Due Date
- Status
- Whether a signed acknowledgement exists

### Recommended Use

Export the register periodically and store a secure backup outside the WordPress installation.

---

## 15. Recommended Daily Workflow

A simple routine for using Lend Sure is:

### When Lending Money

1. Add the borrower if they do not already exist.
2. Create the loan.
3. Confirm principal, interest, and due date.
4. Generate and print the acknowledgement.
5. Obtain signatures.
6. Upload the signed copy.

### When Money Is Received

1. Open the relevant loan.
2. Record the payment immediately.
3. Add the payment method and transaction reference.
4. Confirm the new principal, interest, penalty, and total due.

### When a Loan Reaches Its Due Date

1. Open the Dashboard.
2. Check the Due-Date Workflow table and the Due Today / Due This Week / Overdue cards.
3. Open the relevant loan.
4. Record payment if received.
5. If an extension is agreed, use **Extend Loan**.
6. If your lending terms permit a penalty, review the agreed values and use **Apply Penalty**.

### Periodically

1. Review overdue loans.
2. Check that signed acknowledgements are uploaded.
3. Export the loan register as CSV.
4. Maintain a secure WordPress/database backup.

---

## 16. Understanding Loan Statuses

### Active
The loan still has an outstanding balance.

### Grace Period
An active loan enters Grace Period after its due date passes but before the configured grace days expire.

### Overdue
An active loan becomes Overdue after both the due date and configured grace period have passed.

### Paid
The principal, accrued interest, and accrued penalty have been fully cleared.

---

## 17. Worked Example

### Initial Loan

Borrower receives:

`UGX 1,000,000`

Interest:

`20% per month`

First-month interest:

`UGX 200,000`

Initial amount due:

`UGX 1,200,000`

### Partial Payment

Borrower pays:

`UGX 400,000`

Allocation:

- Interest: UGX 200,000
- Principal: UGX 200,000

Remaining principal:

`UGX 800,000`

Projected next-month interest:

`UGX 160,000`

### One-Month Extension With Capitalization

Assume UGX 160,000 has become the outstanding interest when the extension is made.

Capitalized principal:

`UGX 800,000 + UGX 160,000 = UGX 960,000`

Fresh monthly interest:

`UGX 960,000 × 20% = UGX 192,000`

New amount due:

`UGX 1,152,000`

The Transaction History preserves each step so the administrator can trace the calculation.

---

## 18. Data Protection and Security

Lend Sure may hold personally identifiable borrower information and financial records.

Recommended safeguards:

- Give WordPress administrator access only to trusted people.
- Use strong passwords and two-factor authentication where possible.
- Keep WordPress, themes, and plugins updated.
- Use HTTPS.
- Maintain regular database and file backups.
- Protect exported CSV files because they may contain borrower information.
- Avoid storing unnecessary sensitive information in Notes.
- Follow applicable data-protection and privacy requirements.

---

## 19. Troubleshooting

### I cannot create a loan
Confirm that at least one borrower has already been created under **Lend Sure → Borrowers**.

### The interest rate is not 20%
Open **Lend Sure → Settings** and confirm **Default Monthly Interest (%)** is set to `20`. Also remember that an individual loan can have a different rate if its value was changed during loan creation.

### I uploaded the wrong acknowledgement
Open the loan and use **Replace Signed Copy**.

### A loan is overdue but no penalty was added
This is expected in version 1.2.1. Timing labels are automatic, but penalties are still applied manually from the individual loan screen using the loan-specific agreed penalty as the starting value.

### Why did a partial payment reduce interest before principal?
Payments intentionally follow the order **Interest → Penalty → Principal**.

### Why is the next month's interest lower after a partial payment?
Projected next-month interest is calculated from the remaining principal. If principal falls, the projected interest also falls.

### Why did the principal increase after an extension?
If **Capitalize unpaid interest and penalty into the new principal** was enabled, those unpaid amounts became part of the new principal before fresh extension interest was calculated.

---

## 20. Reminders and Due-Date Workflow

Lend Sure retains the v1.1.0 dedicated follow-up workflow so you do not have to manually remember which borrowers need attention.

### Timing Labels

Active loans are classified automatically from the current date and the loan due date:

- **Due Today** — the due date is today.
- **Due This Week** — the due date is within the next seven days.
- **Grace Period** — the due date has passed, but the configured grace period has not yet expired.
- **Overdue** — the due date and grace period have both passed.
- **Upcoming** — the loan is active but is more than seven days away from its due date.
- **Paid** — the loan balance has been cleared.

These labels do not change the financial balance. They are operational labels used to help you follow up on loans.

### Reminders Module

Go to **Lend Sure → Reminders**.

The screen lists active loans that are due within seven days, due today, in grace, or overdue. For each loan you can see:

- Borrower name
- Due date
- Timing status
- Current amount due
- Borrower email address
- A link to manage the loan
- An **Email Reminder** button when the borrower has a valid email address

### Sending a Borrower Email Reminder

You can send a reminder from either the **Reminders** screen or the individual loan record.

1. Confirm the borrower's email address is correct.
2. Click **Email Reminder** or **Send Email Reminder**.
3. Lend Sure sends a plain-text email through WordPress `wp_mail()`.
4. The message includes the current amount due, due date and timing status.
5. The attempt is added to **Recent Reminder Activity** as sent or failed.

The reminder does not change the loan, charge a penalty or extend the due date. When the borrower reminder is sent, version 1.2.1 also sends a copy to the configured lender/admin digest email when that address is valid and different from the borrower email.

### Daily Admin Due-Date Digest

Go to **Lend Sure → Settings → Due-Date Reminders**.

You can configure:

- **Enable daily admin due-date digest** — switches the daily digest on or off.
- **Digest Email** — the email address that receives the summary.
- **Start Reminding Before Due Date (days)** — how many days before a due date a loan should enter the digest.

The daily digest includes qualifying active loans and all loans already in grace or overdue.

Lend Sure uses WP-Cron only to trigger the reminder digest. Loan balances and due-date calculations do not depend on WP-Cron. Because normal WP-Cron runs when the WordPress site receives traffic, the digest should be treated as daily rather than guaranteed at an exact clock time.

### Recommended Daily Routine

1. Open **Lend Sure → Dashboard**.
2. Review **Due Today**, **Due This Week**, **Grace Period**, and **Overdue** cards.
3. Open **Lend Sure → Reminders**.
4. Contact borrowers who require follow-up.
5. Send an email reminder where appropriate.
6. Record any payment immediately when received.
7. If you agree to an extension, use **Extend Loan** rather than manually changing the balance.
8. Apply penalties only according to your agreed terms and applicable rules.

## 21. Version 1.2.1 Notes

Version 1.2.1 is a maintenance and usability update. It improves email-reminder error handling, adds a historical **Extension Date**, centers the acknowledgement logo with company/lender details underneath, and replaces the thumbprint field with a second witness signature area. The financial workflow remains administrator-controlled. The following are not automated in version 1.2.1:

- Automatic SMS/WhatsApp reminders (email reminders remain available)
- Automatic penalty application
- Automatic borrower reminder emails (borrower emails are sent manually by the administrator)
- Borrower self-service portal
- Mobile Money payment reconciliation
- Scheduled daily interest accrual

The administrator remains in control of payments, extensions, and penalties.

---

## 22. Legal Notice

Lend Sure provides administrative calculations and record keeping. It does not determine whether an interest rate, penalty, acknowledgement, or lending arrangement is legally enforceable.

Before using the plugin for formal lending activities, obtain appropriate advice regarding lending, consumer protection, taxation, privacy, interest, penalties, and document enforceability in the applicable jurisdiction.


## Version 1.2.2 compliance update

Version 1.2.2 is a maintenance release focused on WordPress security, internationalization, database-query, output-escaping, and packaging compliance. It does not change the core loan calculation rules.


## Version 1.2.3 compliance update

Version 1.2.3 completes the remaining items reported by the second WordPress Plugin Check scan after version 1.2.2. It adjusts translator-comment placement and documents cache invalidation for two intentional custom-table write operations. There are no changes to loan calculations, repayment allocation, penalties, extensions, acknowledgements, or reminder behavior.
