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

> Note: In version 1.0.0, the grace-period setting is stored for your policy configuration but penalties are applied manually from the loan screen. Lend Sure does not automatically add a penalty after the grace period.

#### Penalty Type
Choose how the default penalty is calculated:

- **Percentage of outstanding principal** — for example, 5% of the remaining principal.
- **Fixed amount** — for example, UGX 20,000.

#### Penalty Value
Enter the percentage or fixed amount used when you click **Apply Default Penalty** on a loan.

### Acknowledgement / Lender Details

These details appear on the printable Loan Acknowledgement & Acceptance document:

- Lender Name
- Lender Phone
- Lender Address

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

### Due & Active Loans Table

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
6. Confirm the start date.
7. Confirm the due date.
8. Enter the loan purpose if required.
9. Add any additional terms that should appear on the acknowledgement.
10. Click **Create Loan & Generate Acknowledgement**.

### Loan Fields

#### Borrower
Selects the person receiving the loan.

#### Principal Amount
The original amount being lent before interest.

#### Monthly Interest (%)
The interest rate applied to the loan. The default is 20%.

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

The acknowledgement is intended to create a printable record of the original loan terms for signing.

### Creating and Signing the Acknowledgement

1. Create the loan.
2. Open the loan record.
3. Find **Loan Acknowledgement**.
4. Click **View / Print Acknowledgement**.
5. Review the document before printing.
6. Print the acknowledgement.
7. Have the borrower, lender, and witness complete the required signature areas.
8. Scan the signed document or take a clear photograph.
9. Return to the same loan record.
10. Choose the signed file.
11. Click **Upload Signed Copy**.

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
2. Enter the number of additional months.
3. Decide whether to keep **Capitalize unpaid interest and penalty into the new principal** checked.
4. Click **Extend Loan**.

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

The screen displays the default penalty configured under **Lend Sure → Settings**.

### Steps

1. Open the loan.
2. Confirm the configured penalty shown on screen.
3. Edit the note if necessary.
4. Click **Apply Default Penalty**.

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
- Each time the administrator applies the default penalty, UGX 20,000 is added.

> Important: Version 1.0.0 does not automatically apply penalties. The administrator decides when a penalty should be applied.

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
2. Check the Due & Active Loans table.
3. Open the relevant loan.
4. Record payment if received.
5. If an extension is agreed, use **Extend Loan**.
6. If your lending terms permit a penalty, use **Apply Default Penalty**.

### Periodically

1. Review overdue loans.
2. Check that signed acknowledgements are uploaded.
3. Export the loan register as CSV.
4. Maintain a secure WordPress/database backup.

---

## 16. Understanding Loan Statuses

### Active
The loan still has an outstanding balance.

### Overdue
In the Dashboard, an active loan is visually shown as overdue when its due date is earlier than the current date.

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
This is expected in version 1.0.0. Penalties are applied manually from the individual loan screen.

### Why did a partial payment reduce interest before principal?
Payments intentionally follow the order **Interest → Penalty → Principal**.

### Why is the next month's interest lower after a partial payment?
Projected next-month interest is calculated from the remaining principal. If principal falls, the projected interest also falls.

### Why did the principal increase after an extension?
If **Capitalize unpaid interest and penalty into the new principal** was enabled, those unpaid amounts became part of the new principal before fresh extension interest was calculated.

---

## 20. Version 1.0.0 Notes

The current release focuses on lightweight manual administration from WordPress. The following are not automated in version 1.0.0:

- Automatic SMS/WhatsApp reminders
- Automatic penalty application
- Automatic borrower notifications
- Borrower self-service portal
- Mobile Money payment reconciliation
- Scheduled daily interest accrual

The administrator remains in control of payments, extensions, and penalties.

---

## 21. Legal Notice

Lend Sure provides administrative calculations and record keeping. It does not determine whether an interest rate, penalty, acknowledgement, or lending arrangement is legally enforceable.

Before using the plugin for formal lending activities, obtain appropriate advice regarding lending, consumer protection, taxation, privacy, interest, penalties, and document enforceability in the applicable jurisdiction.
